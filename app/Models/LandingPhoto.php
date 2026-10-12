<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class LandingPhoto extends Model
{
    protected $fillable = [
        'path',
        'disk',
        'caption',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function url(): string
    {
        return route('landing-photos.show', ['landingPhoto' => $this, 'v' => $this->updated_at?->timestamp]);
    }

    /**
     * Background for the landing and sign-in pages: the photo Ate Jackie
     * picked, or the default night-house image.
     */
    public static function backgroundUrl(): string
    {
        try {
            $id = (int) Setting::get('landing_background_photo_id');
            $photo = $id ? static::find($id) : null;

            return $photo ? $photo->url() : asset('images/auth-bg.jpg');
        } catch (Throwable) {
            return asset('images/auth-bg.jpg');
        }
    }
}
