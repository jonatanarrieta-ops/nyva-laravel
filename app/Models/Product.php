<?php

namespace App\Models;

use App\Services\ImageStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 * @property numeric-string $price
 * @property string|null $image
 * @property bool $available
 * @property string $status
 * @property-read Category|null $category
 */
class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'available',
        'status',
    ];

    /**

     * @return BelongsTo<Category, $this>

     */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageUrl(): ?string
    {
        return ImageStorage::url($this->image);
    }

    public function isOrderable(): bool
    {
        return $this->available
            && $this->status === 'active'
            && $this->category?->status === 'active';
    }

    /**

     * @return HasMany<OrderItem, $this>

     */

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'available' => 'boolean',
        ];
    }
}
