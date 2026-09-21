<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'establishment_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'total',
        'status',
        'ordered_at',
    ];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot([
                'quantity',
                'unit_price',
                'subtotal',
            ]);
    }

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];
    }
}
