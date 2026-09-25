<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'author_id', 'category_id', 'title', 'slug', 'tagline', 'description',
        'price_cents', 'compare_at_price_cents', 'extended_price_cents',
        'listing_type', 'acquisition_sale_type', 'acquisition_status', 'asking_price_cents',
        'reserve_price_cents', 'minimum_offer_cents', 'auction_starts_at', 'auction_ends_at',
        'sold_to_user_id', 'sold_at', 'monthly_revenue_cents', 'monthly_profit_cents',
        'monthly_visitors', 'monthly_pageviews', 'business_started_on',
        'status', 'is_studio_original', 'is_featured', 'tech_stack', 'requirements',
        'transfer_assets', 'verified_metrics', 'seller_disclosures', 'transfer_notes',
        'due_diligence_notes', 'demo_url', 'repository_url', 'current_version',
        'rating_avg', 'rating_count', 'sales_count', 'view_count', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'requirements' => 'array',
            'transfer_assets' => 'array',
            'verified_metrics' => 'array',
            'is_studio_original' => 'boolean',
            'is_featured' => 'boolean',
            'auction_starts_at' => 'datetime',
            'auction_ends_at' => 'datetime',
            'sold_at' => 'datetime',
            'business_started_on' => 'date',
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

    public function soldTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_to_user_id');
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

    public function acquisitionOffers(): HasMany
    {
        return $this->hasMany(AcquisitionOffer::class)->latest();
    }

    public function acquisitionBids(): HasMany
    {
        return $this->hasMany(AcquisitionBid::class)->orderByDesc('amount_cents');
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

    public function isAcquisition(): bool
    {
        return $this->listing_type === 'acquisition';
    }

    public function isAvailableAcquisition(): bool
    {
        return $this->isAcquisition() && $this->isLive() && $this->acquisition_status === 'available';
    }

    public function acceptsOffers(): bool
    {
        return in_array($this->acquisition_sale_type, ['offer', 'fixed_or_offer'], true);
    }

    public function acceptsBids(): bool
    {
        return $this->acquisition_sale_type === 'auction'
            && $this->auction_starts_at?->isPast()
            && $this->auction_ends_at?->isFuture();
    }

    public function currentBidCents(): ?int
    {
        return $this->acquisitionBids()->where('status', 'active')->max('amount_cents');
    }

    public function minimumBidCents(): int
    {
        $currentBid = $this->currentBidCents();

        if ($currentBid) {
            return $currentBid + 100;
        }

        return $this->reserve_price_cents ?? $this->asking_price_cents ?? $this->price_cents;
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeSoftware(Builder $query): Builder
    {
        return $query->where('listing_type', 'software');
    }

    public function scopeAcquisition(Builder $query): Builder
    {
        return $query->where('listing_type', 'acquisition');
    }

    public function scopeAvailableAcquisition(Builder $query): Builder
    {
        return $query->acquisition()->live()->where('acquisition_status', 'available');
    }

    public function priceFormatted(): string
    {
        return '$'.number_format($this->price_cents / 100, 0);
    }

    public function askingPriceFormatted(): string
    {
        return '$'.number_format(($this->asking_price_cents ?? $this->price_cents) / 100, 0);
    }

    public function monthlyRevenueFormatted(): string
    {
        return '$'.number_format(($this->monthly_revenue_cents ?? 0) / 100, 0);
    }

    public function monthlyProfitFormatted(): string
    {
        return '$'.number_format(($this->monthly_profit_cents ?? 0) / 100, 0);
    }
}
