<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property numeric-string $monthly_price
 * @property numeric-string|null $annual_price
 * @property int $duration_days
 * @property int|null $product_limit
 * @property int|null $user_limit
 * @property array<int, string>|null $features
 * @property string $status
 */
class Plan extends Model
{
    public const FEATURES = [
        'chatbot' => 'Chatbot',
        'reports' => 'Reportes',
        'public_order' => 'Pedidos en línea',
        'team' => 'Usuarios autorizados',
    ];

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'monthly_price',
        'annual_price',
        'duration_days',
        'product_limit',
        'user_limit',
        'features',
        'status',
    ];

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    /**
     * @return HasMany<Subscription, $this>
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
            'features' => 'array',
        ];
    }
}
