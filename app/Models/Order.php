<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $establishment_id
 * @property int|null $user_id
 * @property string $public_token
 * @property string|null $idempotency_key
 * @property string $channel
 * @property string $customer_name
 * @property string $customer_phone
 * @property string $delivery_address
 * @property numeric-string $total
 * @property string $status
 * @property CarbonInterface|null $ordered_at
 * @property-read Establishment|null $establishment
 */
class Order extends Model
{
    public $timestamps = false;

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
        'public_token',
        'idempotency_key',
        'channel',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'total',
        'status',
        'ordered_at',
    ];

    /**
     * @return BelongsTo<Establishment, $this>
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_token ??= Str::random(40);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
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

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
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
