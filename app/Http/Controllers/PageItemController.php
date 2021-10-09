<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Page;
use App\Models\PageItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageItemController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_admin')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Page $page)
    {
        if (!$page->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        if (@Auth::guard('api')->user()->is_admin) {
            return $page->items()->with('translations')->paginate(15);
        }
        return $page->items()->where('is_published', '=', '1')->paginate(15);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Page $page
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Page $page)
    {
        if($request->input('slug') == null){
            $request->merge(['slug' => Str::limit(Str::slug($request->input('en.name')!=null?$request->input('en.name'):$request->input('sv.name')), 255)]);
        }
        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['required', 'string', 'min:5', 'max:255', Rule::unique('page_items', 'slug')->where('page_id', $page->id)],
                'is_published' => ['nullable', 'boolean'],
                'image' => ['nullable', 'string'],
            ], [
                'title' => ['required', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string', 'min:3'],
                'content' => ['nullable', 'string', 'min:10'],
                'additionals' => ['nullable', 'array'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $pageItem = new PageItem();
        $pageItem->page_id = $page->id;
        $pageItem->slug = $request->input('slug');
        $pageItem->is_published = $request->input('is_published') == 1;
        $pageItem->image = $request->input('image');
        $pageItem->save();

        foreach (Language::all()->all() as $language) {
            $pageItem->translations()->insert([
                'page_item_id' => $pageItem->id,
                'language_id' => $language->id,
                'title' => @$inputs[$language->short]['title'],
                'description' => @$inputs[$language->short]['description'],
                'content' => @$inputs[$language->short]['content'],
                'additionals' => json_encode(@$inputs[$language->short]['additionals']),
            ]);
        }

        return $pageItem->find($pageItem->id);
    }

    /**
     * Display the specified resource.
     *
     * @param Page $page
     * @param string $item
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page, string $item)
    {
        /** @var PageItem $pageItem */
        $pageItem = $page->items()->where('slug', '=', $item)->first();
        if (($pageItem == null) || ((!$page->is_published || !$pageItem->is_published) && !@Auth::guard('api')->user()->is_admin)) {
            abort(404);
        }

        if (@Auth::guard('api')->user()->is_admin) {
            return $pageItem->with(['translations'])->find($pageItem->id);
        }

        return $pageItem;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Page $page
     * @param string $item
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page, string $item)
    {
        /** @var PageItem $pageItem */
        $pageItem = $page->items()->where('slug', '=', $item)->first();
        if (($pageItem == null) || ((!$page->is_published || !$pageItem->is_published) && !@Auth::guard('api')->user()->is_admin)) {
            abort(404);
        }

        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['nullable', 'string', 'min:5', 'max:255', Rule::unique('page_items', 'slug')->where('page_id', $page->id)->ignore($pageItem->id)],
                'is_published' => ['nullable', 'boolean'],
                'image' => ['nullable', 'string'],
            ], [
                'title' => ['nullable', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string', 'min:3', 'max:255'],
                'content' => ['nullable', 'string', 'min:10'],
                'additionals' => ['nullable', 'array'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }
        $updated = false;

        if ($request->has('slug') && $request->input('slug') != null && $pageItem->slug != $request->input('slug')) {
            $pageItem->slug = $request->input('slug');
            $updated = true;
        }
        if ($request->has('is_published') && $request->input('is_published') != null && $pageItem->is_published != $request->input('is_published')) {
            $pageItem->is_published = $request->input('is_published');
            $updated = true;
        }
        if ($request->has('image') && $pageItem->image != $request->input('image')) {
            $pageItem->image = $request->input('image');
            $updated = true;
        }

        $translations = array();
        foreach ($pageItem->translations->all() as $value) {
            $translations[$value['language']['short']] = $value;
        }
        foreach (Language::all()->all() as $language) {
            if (isset($inputs[$language->short]['title'])
                && $inputs[$language->short]['title'] != null
                && $translations[$language->short]['title'] != $inputs[$language->short]['title']) {
                $pageItem->translations()
                    ->where('page_item_id', '=', $pageItem->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['title' => $inputs[$language->short]['title']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['description'])
                && $translations[$language->short]['description'] != $inputs[$language->short]['description']) {
                $pageItem->translations()
                    ->where('page_item_id', '=', $pageItem->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['description' => $inputs[$language->short]['description']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['content'])
                && $translations[$language->short]['content'] != $inputs[$language->short]['content']) {
                $pageItem->translations()
                    ->where('page_item_id', '=', $pageItem->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['content' => $inputs[$language->short]['content']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['additionals'])
                && json_encode($translations[$language->short]['additionals']) != json_encode($inputs[$language->short]['additionals'])) {
                $pageItem->translations()
                    ->where('page_item_id', '=', $pageItem->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['additionals' => json_encode($inputs[$language->short]['additionals'])]);
                $updated = true;
            }
        }

        if ($updated) {
            $pageItem->update();
            return $pageItem;
        }
        return response(null, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Page $page
     * @param string $item
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Page $page, string $item)
    {
        /** @var PageItem $pageItem */
        $pageItem = $page->items()->where('slug', '=', $item)->first();
        if (($pageItem == null) || ((!$page->is_published || !$pageItem->is_published) && !@Auth::guard('api')->user()->is_admin)) {
            abort(404);
        }

        $pageItem->delete();
        return response(null, 204);
    }


    public function rate(Request $request, Page $page, string $item)
    {
        if(!$page->is_rateable){
            abort(404);
        }
        /** @var PageItem $pageItem */
        $pageItem = $page->items()->where('slug', '=', $item)->first();
        if (($pageItem == null) || ((!$page->is_published || !$pageItem->is_published) && !@Auth::guard('api')->user()->is_admin)) {
            abort(404);
        }

        $ip = $request->getClientIp();
        if(DB::table("page_items_rates")->where('page_item_id', '=', $pageItem->id)->where('ip', '=', $ip)->count() > 0){
            return response(['status' => false, 'errors' => "You have already rated this item."]);
        }

        try {
            $inputs = $this->validate($request, [
                'rate' => ['required', 'integer', 'in:1,2,3,4,5'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        if(DB::table("page_items_rates")->insert(['page_item_id' => $pageItem->id, 'ip' => $ip, 'rate' => $request->input('rate')]))
        {
            return $pageItem;
        }

        return response(null, 204);
    }
}
