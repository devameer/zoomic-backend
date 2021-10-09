<?php

namespace App\Models;

use App\Casts\Json;
use App\Models\Translations\PageTranslation;
use App\Utils\CurrentLanguage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $withCount = ['items'];
    protected $hidden = ['created_at', 'updated_at', 'translation'];
    protected $casts = ['is_published' => 'boolean'];
    protected $appends = ['title', 'description', 'content', 'additionals'];

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
    public function getAdditionalsAttribute()
    {
        return @$this->translation->additionals;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PageItem::class);
    }

    public function translation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PageTranslation::class)->whereHas('language', function($query){ $query->where('short', '=', CurrentLanguage::get()); });
    }
}
