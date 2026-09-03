<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price_kobo',
        'image',
        'image_url',
        'unit_label',
        'stock',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price_kobo' => 'integer',
        'stock' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /** Uploaded file wins over the pasted URL, matching the works pattern. */
    public function imageSrc(): ?string
    {
        if (filled($this->image)) {
            return Storage::disk('public')->url($this->image);
        }

        return filled($this->image_url) ? $this->image_url : null;
    }
}
