<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class InstagramPost extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public array $translatable = ['caption'];

    protected function casts(): array
    {
        return [
            'is_video' => 'boolean',
            'is_active' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
