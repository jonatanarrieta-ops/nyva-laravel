<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $establishment_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property-read Establishment|null $establishment
 */
class Category extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'establishment_id',
        'name',
        'description',
        'status',
    ];

    /**

     * @return BelongsTo<Establishment, $this>

     */

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    /**

     * @return HasMany<Product, $this>

     */

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
        ];
    }
}
