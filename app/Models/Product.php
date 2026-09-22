<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'author_id', 'category_id', 'title', 'slug', 'tagline', 'description',
        'price_cents', 'compare_at_price_cents', 'extended_price_cents',
        'status', 'is_studio_original', 'is_featured', 'tech_stack', 'requirements',
        'demo_url', 'current_version', 'rating_avg', 'rating_count', 'sales_count',
        'view_count', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'requirements' => 'array',
            'is_studio_original' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class)->latest('submitted_at');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedItem::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function priceFormatted(): string
    {
        return '$'.number_format($this->price_cents / 100, 0);
    }
}
