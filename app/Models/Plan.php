<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'monthly_price',
        'annual_price',
        'status',
    ];

    /**
     * Relationship:
     * A plan can have many subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'status' => 'boolean',
        ];
    }
}
