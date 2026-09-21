<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'establishment_id',
        'plan_id',
        'billing_period',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @return BelongsTo<Establishment, Subscription>
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    /**
     * @return BelongsTo<Plan, Subscription>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
        ];
    }
}
