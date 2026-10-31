<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public $timestamps = false;
    use HasFactory;

    public const STATUS_LABELS = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'preparing' => 'Preparando',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];

    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'establishment_id',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
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
