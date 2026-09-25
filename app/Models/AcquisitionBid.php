<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionBid extends Model
{
    protected $fillable = [
        'product_id', 'bidder_id', 'amount_cents', 'currency', 'status', 'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bidder_id');
    }

    public function amountFormatted(): string
    {
        return '$'.number_format($this->amount_cents / 100, 0);
    }
}
