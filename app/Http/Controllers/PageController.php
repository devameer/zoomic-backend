<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
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
    public function index()
    {
        if (@Auth::guard('api')->user()->is_admin) {
            return Page::query()->with('translations')->paginate(15);
        }
        return Page::query()->where('is_published', '=', '1')->paginate(15);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->input('slug') == null){
            $request->merge(['slug' => Str::limit(Str::slug($request->input('en.name')!=null?$request->input('en.name'):$request->input('sv.name')), 255)]);
        }
        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['required', 'string', 'min:5', 'max:255', 'unique:pages,slug'],
                'is_published' => ['nullable', 'boolean'],
                'is_rateable' => ['nullable', 'boolean'],
                'image' => ['nullable', 'string'],
            ], [
                'title' => ['required', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string', 'min:3', 'max:255'],
                'content' => ['nullable', 'string', 'min:10'],
                'additionals' => ['nullable', 'array'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $page = new Page;
        $page->slug = $request->input('slug');
        $page->is_published = $request->input('is_published') == 1;
        $page->is_rateable = $request->input('is_rateable') == 1;
        $page->image = $request->input('image');
        $page->save();

        foreach (Language::all()->all() as $language) {
            $page->translations()->insert([
                'page_id' => $page->id,
                'language_id' => $language->id,
                'title' => @$inputs[$language->short]['title'],
                'description' => @$inputs[$language->short]['description'],
                'content' => @$inputs[$language->short]['content'],
                'additionals' => json_encode(@$inputs[$language->short]['additionals']),
            ]);
        }

        return $page->find($page->id);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Page $page
     * @return \Illuminate\Http\Response
     */
    public function show(Page $page)
    {
        if (!$page->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }

        if (@Auth::guard('api')->user()->is_admin) {
            return $page->with(['items', 'translations'])->find($page->id);
        }

        return $page->with('items')->find($page->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Page $page
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Page $page)
    {
        if (!$page->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['nullable', 'string', 'min:5', 'max:255', 'unique:pages,slug,'.$page->id],
                'is_published' => ['nullable', 'boolean'],
                'is_rateable' => ['nullable', 'boolean'],
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

        if ($request->has('slug') && $request->input('slug') != null && $page->slug != $request->input('slug')) {
            $page->slug = $request->input('slug');
            $updated = true;
        }
        if ($request->has('is_published') && $request->input('is_published') != null && $page->is_published != $request->input('is_published')) {
            $page->is_published = $request->input('is_published');
            $updated = true;
        }
        if ($request->has('is_rateable') && $request->input('is_rateable') != null && $page->is_rateable != $request->input('is_rateable')) {
            $page->is_rateable = $request->input('is_rateable');
            $updated = true;
        }
        if ($request->has('image') && $page->image != $request->input('image')) {
            $page->image = $request->input('image');
            $updated = true;
        }

        $translations = array();
        foreach ($page->translations->all() as $value) {
            $translations[$value['language']['short']] = $value;
        }
        foreach (Language::all()->all() as $language) {
            if (isset($inputs[$language->short]['title'])
                && $inputs[$language->short]['title'] != null
                && $translations[$language->short]['title'] != $inputs[$language->short]['title']) {
                $page->translations()
                    ->where('page_id', '=', $page->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['title' => $inputs[$language->short]['title']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['description'])
                && $translations[$language->short]['description'] != $inputs[$language->short]['description']) {
                $page->translations()
                    ->where('page_id', '=', $page->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['description' => $inputs[$language->short]['description']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['content'])
                && $translations[$language->short]['content'] != $inputs[$language->short]['content']) {
                $page->translations()
                    ->where('page_id', '=', $page->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['content' => $inputs[$language->short]['content']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['additionals'])
                && json_encode($translations[$language->short]['additionals']) != json_encode($inputs[$language->short]['additionals'])) {
                $page->translations()
                    ->where('page_id', '=', $page->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['additionals' => json_encode($inputs[$language->short]['additionals'])]);
                $updated = true;
            }
        }

        if ($updated) {
            $page->update();
            return $page;
        }
        return response(null, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Page $page
     * @return \Illuminate\Http\Response
     */
    public function destroy(Page $page)
    {
        if (!$page->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        $page->delete();
        return response(null, 204);
    }

    public function checkSlug(Request $request, Page $page){
        try {
            $this->validate($request, [
                'slug' => ['required', 'string', 'min:5', 'max:255', Rule::unique('page_items', 'slug')->where('page_id', $page->id)],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        return response(['status' => true, 'message' => 'GOOD_SLUG']);
    }
}
