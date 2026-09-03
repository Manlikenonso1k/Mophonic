<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Work extends Model
{
    public const TYPES = [
        'video' => 'Music video',
        'album' => 'Album',
        'film' => 'Film',
        'zine' => 'Zine',
        'other' => 'Other',
    ];

    protected $fillable = [
        'title',
        'slug',
        'type',
        'cover_image',
        'cover_url',
        'background_video',
        'background_video_url',
        'link_url',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Uploaded file wins over the pasted URL, so an editor can override a
     * remote asset simply by uploading a replacement.
     */
    public function coverSrc(): ?string
    {
        return $this->resolve($this->cover_image, $this->cover_url);
    }

    public function backgroundVideoSrc(): ?string
    {
        return $this->resolve($this->background_video, $this->background_video_url);
    }

    protected function resolve(?string $path, ?string $url): ?string
    {
        if (filled($path)) {
            return Storage::disk('public')->url($path);
        }

        return filled($url) ? $url : null;
    }
}
