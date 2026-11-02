<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $establishment_id
 * @property int $plan_id
 * @property string $billing_period
 * @property CarbonInterface $start_date
 * @property CarbonInterface|null $end_date
 * @property string $status
 * @property-read Plan|null $plan
 * @property-read Establishment|null $establishment
 */
class Subscription extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'establishment_id',
        'plan_id',
        'billing_period',
        'start_date',
        'end_date',
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
     * @return BelongsTo<Plan, $this>
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
        ];
    }
}
