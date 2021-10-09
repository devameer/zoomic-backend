<?php

namespace App\Models;

use App\Models\Translations\CategoryTranslation;
use App\Utils\CurrentLanguage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $with = ['parent'];
    protected $hidden = ['parent_id', 'created_at', 'updated_at', 'translation'];
    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return @$this->translation->name;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function translation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CategoryTranslation::class)->whereHas('language', function($query){ $query->where('short', '=', CurrentLanguage::get()); });
    }


}
