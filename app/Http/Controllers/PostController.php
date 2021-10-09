<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Language;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_admin')->only(['store', 'update', 'destroy', 'latestComments']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (@Auth::guard('api')->user()->is_admin) {
            return Post::query()->with('translations')->paginate(15);
        }
        return Post::query()->where('is_published', '=', '1')->paginate(15);
    }

    public function latestComments(){
        return Comment::query()
            ->orderByDesc('created_at')
            ->with(['post:id,slug,user_id', 'user:id,name'])
            ->paginate(15);
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
                'slug' => ['required', 'string', 'min:5', 'max:255', 'unique:posts,slug'],
                'is_published' => ['nullable', 'boolean'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'image' => ['nullable', 'string'],
            ], [
                'title' => ['required', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string', 'min:3', 'max:255'],
                'content' => ['nullable', 'string', 'min:10'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $post = new Post;
        $post->user_id = Auth::guard('api')->id();
        $post->slug = $request->input('slug');
        $post->is_published = $request->input('is_published') == 1;
        $post->category_id = $request->input('category_id');
        $post->image = $request->input('image');
        $post->save();

        foreach (Language::all()->all() as $language) {
            $post->translations()->insert([
                'post_id' => $post->id,
                'language_id' => $language->id,
                'title' => @$inputs[$language->short]['title'],
                'description' => @$inputs[$language->short]['description'],
                'content' => @$inputs[$language->short]['content'],
            ]);
        }

        return $post->find($post->id);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        if (!$post->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }

        if (@Auth::guard('api')->user()->is_admin) {
            return $post->with(['translations', 'comments', 'category'])->find($post->id);
        }

        return $post->with(['comments', 'category'])->find($post->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        if (!$post->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        try {
            $inputs = $this->validateMultiLanguages($request, [
                'slug' => ['nullable', 'string', 'min:5', 'max:255', 'unique:posts,slug,'.$post->id],
                'is_published' => ['nullable', 'boolean'],
                'category_id' => ['nullable', 'integer', 'exists:categories,id'],
                'image' => ['nullable', 'string'],
            ], [
                'title' => ['nullable', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string', 'min:3', 'max:255'],
                'content' => ['nullable', 'string', 'min:10'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }
        $updated = false;

        if ($request->has('slug') && $request->input('slug') != null && $post->slug != $request->input('slug')) {
            $post->slug = $request->input('slug');
            $updated = true;
        }
        if ($request->has('is_published') && $request->input('is_published') != null && $post->is_published != $request->input('is_published')) {
            $post->is_published = $request->input('is_published');
            $updated = true;
        }
        if ($request->has('category_id') && $post->category_id != $request->input('category_id')) {
            $post->category_id = $request->input('category_id');
            $updated = true;
        }
        if ($request->has('image') && $post->image != $request->input('image')) {
            $post->image = $request->input('image');
            $updated = true;
        }

        $translations = array();
        foreach ($post->translations->all() as $value) {
            $translations[$value['language']['short']] = $value;
        }
        foreach (Language::all()->all() as $language) {
            if (isset($inputs[$language->short]['title'])
                && $inputs[$language->short]['title'] != null
                && $translations[$language->short]['title'] != $inputs[$language->short]['title']) {
                $post->translations()
                    ->where('post_id', '=', $post->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['title' => $inputs[$language->short]['title']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['description'])
                && $translations[$language->short]['description'] != $inputs[$language->short]['description']) {
                $post->translations()
                    ->where('post_id', '=', $post->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['description' => $inputs[$language->short]['description']]);
                $updated = true;
            }
            if (isset($inputs[$language->short]['content'])
                && $translations[$language->short]['content'] != $inputs[$language->short]['content']) {
                $post->translations()
                    ->where('post_id', '=', $post->id)
                    ->where('language_id', '=', $language->id)
                    ->update(['content' => $inputs[$language->short]['content']]);
                $updated = true;
            }
        }

        if ($updated) {
            $post->update();
            return $post;
        }
        return response(null, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        if (!$post->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        $post->delete();
        return response(null, 204);
    }

    public function comment(Request $request, Post $post)
    {
        if (!$post->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        try {
            $this->validate($request, [
                'comment' => ['required', 'string', 'min:5'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }
        Comment::query()->insert([
            'user_id' => Auth::guard('api')->id(),
            'post_id' => $post->id,
            'comment' => $request->input('comment'),
            'accepted' => 0,
        ]);
        return "ADDED";
    }

    public function updateComment(Request $request, Post $post, Comment $comment)
    {
        $is_admin = @Auth::guard('api')->user()->is_admin;
        if (!$post->is_published && !$is_admin) {
            abort(404);
        }
        if($post->id != $comment->post->id){
            return response(['status' => false, 'errors' => ['post' => 'This comment doesn\'t belongs to this post.']], 422);
        }
        if(Auth::guard('api')->id() != $comment->user->id && !$is_admin){
            return response(['status' => false, 'errors' => ['comment' => 'This comment doesn\'t belong to the current user.']], 422);
        }

        try {
            $this->validate($request, [
                'comment' => ['required', 'string', 'min:5'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $updated = false;

        if($is_admin && $request->has('accepted') && is_bool($request->input('accepted')) && $comment->accepted != $request->input('accepted')){
            $comment->accepted = $request->input('accepted');
            $updated = true;
        }

        if ( $request->has('comment') && $comment->comment != $request->input('comment') ) {
            $comment->comment = $request->input('comment');
            $updated = true;
        }
        if($updated){
            $comment->update();
            return "UPDATED";
        }
        return response(null, 204);
    }

    public function deleteComment(Post $post, Comment $comment)
    {
        if (!$post->is_published && !@Auth::guard('api')->user()->is_admin) {
            abort(404);
        }
        if($post->id != $comment->post->id){
            return response(['status' => false, 'errors' => ['post' => 'This comment doesn\'t belongs to this post.']], 422);
        }
        if(Auth::guard('api')->id() != $comment->user->id && !@Auth::guard('api')->user()->is_admin){
            return response(['status' => false, 'errors' => ['comment' => 'This comment doesn\'t belong to the current user.']], 422);
        }

        $comment->delete();
        return "DELETED";
    }

    public function checkSlug(Request $request){
        try {
            $this->validate($request, [
                'slug' => ['required', 'string', 'min:5', 'max:255', 'unique:posts,slug'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        return response(['status' => true, 'message' => 'GOOD_SLUG']);
    }

}
