<?php

namespace App\Models\Translations;

use App\Casts\Json;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    use HasFactory;
    protected $table = 'pages_translations';
    public $timestamps = false;

    protected $with = ['language'];
    protected $hidden = ['page_id', 'language_id', 'is_published'];
    protected $casts = ['additionals' => Json::class];

    public function language(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
