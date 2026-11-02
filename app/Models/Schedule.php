<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $establishment_id
 * @property string $day_of_week
 * @property string $opens_at
 * @property string $closes_at
 * @property string $status
 */
class Schedule extends Model
{
    public const DAYS = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    public $timestamps = false;

    protected $fillable = [
        'establishment_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
        ];
    }

    public function dayLabel(): string
    {
        return self::DAYS[strtolower($this->day_of_week)] ?? $this->day_of_week;
    }

    public function isOpenAt(CarbonInterface $moment): bool
    {
        $time = $moment->format('H:i:s');
        $opens = $this->normalizedTime($this->opens_at);
        $closes = $this->normalizedTime($this->closes_at);
        $day = strtolower($this->day_of_week);

        // Un cierre menor que la apertura significa que el turno termina al día siguiente.
        if ($closes <= $opens) {
            return ($day === strtolower($moment->format('l')) && $time >= $opens)
                || ($day === strtolower($moment->copy()->subDay()->format('l')) && $time < $closes);
        }

        return $day === strtolower($moment->format('l')) && $time >= $opens && $time < $closes;
    }

    private function normalizedTime(string $time): string
    {
        return str_pad(substr($time, 0, 8), 8, ':00', STR_PAD_RIGHT);
    }

    /**
     * @return BelongsTo<Establishment, $this>
     */
    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }
}
