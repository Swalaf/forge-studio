<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcquisitionOffer extends Model
{
    protected $fillable = [
        'product_id', 'buyer_id', 'amount_cents', 'currency', 'status',
        'message', 'seller_response', 'expires_at', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function amountFormatted(): string
    {
        return '$'.number_format($this->amount_cents / 100, 0);
    }
}
