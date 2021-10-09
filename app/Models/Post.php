<?php

namespace App\Models;

use App\Models\Translations\PostTranslation;
use App\Utils\CurrentLanguage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use HasFactory;

    protected $with = ['user:id,name'];
    protected $withCount = ['comments'];
    protected $hidden = ['updated_at', 'user_id', 'category_id', 'translation'];
    protected $casts = ['is_published' => 'boolean'];
    protected $appends = ['title', 'description', 'content'];

    public function getTitleAttribute()
    {
        return @$this->translation->title;
    }
    public function getDescriptionAttribute()
    {
        return @$this->translation->description;
    }
    public function getContentAttribute()
    {
        return @$this->translation->content;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        if(! @Auth::guard('api')->user()->is_admin ){
            return $this->hasMany(Comment::class)->where('accepted', '=', '1');
        }
        return $this->hasMany(Comment::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

    public function translation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PostTranslation::class)->whereHas('language', function($query){ $query->where('short', '=', CurrentLanguage::get()); });
    }
}
