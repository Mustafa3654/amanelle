<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $theme): void {
            if ($theme->is_active) {
                static::whereKeyNot($theme->id)->update(['is_active' => false]);
            }
        });
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
