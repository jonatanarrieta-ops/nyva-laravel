<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'establishment_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Relationship:
     * A category belongs to an establishment.
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    /**
     * Relationship:
     * A category can have many products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
}
