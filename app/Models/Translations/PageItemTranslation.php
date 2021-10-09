<?php

namespace App\Models\Translations;

use App\Casts\Json;
use App\Models\Language;
use App\Utils\CurrentLanguage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageItemTranslation extends Model
{
    use HasFactory;
    protected $table = 'page_items_translations';
    public $timestamps = false;

    protected $with = ['language'];
    protected $hidden = ['page_item_id', 'language_id'];
    protected $casts = ['additionals' => Json::class];

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
