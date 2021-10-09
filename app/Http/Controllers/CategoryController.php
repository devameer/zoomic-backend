<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Language;
use App\Models\Translations\CategoryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
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
            return Category::query()->with('translations')->paginate(15);
        }
        return Category::query()->paginate(15);
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
            $request->merge(['slug' => Str::limit(Str::slug($request->input('en.name')!=null?$request->input('en.name'):$request->input('ar.name')), 255)]);
        }
        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['required', 'string', 'min:5', 'max:255', 'unique:categories,slug'],
                'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            ], [
                'name' => ['required', 'string', 'min:3', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $category = new Category;
        $category->slug = $request->input('slug');
        $category->parent_id = $request->input('parent_id');
        $category->save();

        foreach (Language::all()->all() as $language) {
            $category->translations()->insert([
                'category_id' => $category->id,
                'language_id' => $language->id,
                'name' => @$inputs[$language->short]['name'],
            ]);
        }

        return $category->find($category->id);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Category $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        if (@Auth::guard('api')->user()->is_admin) {
            return $category->with('translations')->find($category->id);
        }
        return $category;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Category $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['nullable', 'string', 'min:5', 'max:255', 'unique:categories,slug,' . $category->id],
                'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            ], [
                'name' => ['nullable', 'string', 'min:3', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }
        $updated = false;

        if ($request->has('slug') && $request->input('slug') != null && $category->slug != $request->input('slug')) {
            $category->slug = $request->input('slug');
            $updated = true;
        }
        if ($request->has('parent_id') && $request->input('parent_id') != null && $category->parent_id != $request->input('parent_id')) {
            $category->parent_id = $request->input('parent_id');
            $updated = true;
        }

        $translations = array();
        foreach($category->translations->all() as $value){
            $translations[$value['language']['short']] = $value;
        }
        foreach (Language::all()->all() as $language) {
            if (isset($inputs[$language->short]['name'])
                && $inputs[$language->short]['name'] != null
                && $translations[$language->short]['name'] != $inputs[$language->short]['name']) {
                $category->translations()
                    ->where('category_id', '=', $category->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['name' => $inputs[$language->short]['name']]);
                $updated = true;
            }
        }

        if ($updated) {
            $category->update();
            return $category;
        }
        return response(null, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Category $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response(null, 204);
    }

    public function checkSlug(Request $request){
        try {
            $this->validate($request, [
                'slug' => ['required', 'string', 'min:5', 'max:255', 'unique:categories,slug'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        return response(['status' => true, 'message' => 'GOOD_SLUG']);
    }
}
