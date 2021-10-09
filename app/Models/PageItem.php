<?php

namespace App\Models;

use App\Casts\Json;
use App\Models\Translations\PageItemTranslation;
use App\Utils\CurrentLanguage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PageItem extends Model
{
    use HasFactory;
    protected $hidden = ['created_at', 'updated_at', 'translation', 'page_id'];
    protected $casts = ['is_published' => 'boolean'];
    protected $appends = ['title', 'description', 'content', 'additionals', 'rates', 'your_rating'];

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
    public function getRatesAttribute()
    {
        return number_format(DB::table("page_items_rates")->where(['page_item_id' => $this->id])->avg('rate'), 2);
    }
    public function getYourRatingAttribute()
    {
        $rate = DB::table("page_items_rates")->where(['page_item_id' => $this->id, 'ip' => request()->getClientIp()])->first();
        if($rate == null){
            return null;
        }
        return number_format($rate->rate, 2);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function page(){
        return $this->belongsTo(Page::class);
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PageItemTranslation::class);
    }

    public function translation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PageItemTranslation::class)->whereHas('language', function($query){ $query->where('short', '=', CurrentLanguage::get()); });
    }
}
