<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'company', 'country',
        'author_tier', 'commission_pct', 'standing', 'payout_method',
        'two_factor_enabled', 'author_application_status', 'author_application_note',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'commission_pct' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAuthor(): bool
    {
        return $this->role === 'author';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $letters = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'author_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'author_id');
    }

    public function savedItems(): HasMany
    {
        return $this->hasMany(SavedItem::class, 'customer_id');
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'customer_id');
    }

    public function serviceProjects(): HasMany
    {
        return $this->hasMany(ServiceProject::class, 'customer_id');
    }

    public function ownedServiceProjects(): HasMany
    {
        return $this->hasMany(ServiceProject::class, 'owner_id');
    }

    public function openedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'opener_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }
}
