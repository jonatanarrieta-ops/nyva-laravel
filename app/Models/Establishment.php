<?php

namespace App\Models;

use App\Services\ImageStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string|null $description
 * @property string $address
 * @property string $phone
 * @property string|null $email
 * @property string|null $logo
 * @property string|null $cover_image
 * @property string $status
 * @property string $operating_status
 * @property Carbon|null $registered_at
 */
class Establishment extends Model
{
    public const OPERATING_STATUSES = [
        'open' => 'Abierto',
        'closed' => 'Cerrado',
        'temporarily_inactive' => 'Temporalmente inactivo',
    ];

    public const STATUSES = [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'suspended' => 'Suspendido',
    ];

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'description',
        'address',
        'phone',
        'email',
        'logo',
        'cover_image',
        'operating_status',
        'registered_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Establishment $establishment) {
            $establishment->slug ??= static::uniqueSlug($establishment->name);
            $establishment->status ??= 'active';
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: 'establecimiento', 170, '');
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('permissions');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<FrequentlyAskedQuestion, $this>
     */
    public function frequentlyAskedQuestions(): HasMany
    {
        return $this->hasMany(FrequentlyAskedQuestion::class);
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasOne<ChatbotSetting, $this>
     */
    public function chatbotSetting(): HasOne
    {
        return $this->hasOne(ChatbotSetting::class);
    }

    public function chatbot(): ChatbotSetting
    {
        return $this->chatbotSetting()->firstOrCreate([], [
            'enabled' => true,
            'orders_enabled' => true,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function orderRecipients(): Collection
    {
        return $this->members()
            ->whereJsonContains('establishment_user.permissions', 'orders')
            ->get()
            ->push($this->user)
            ->filter()
            ->unique('id')
            ->values();
    }

    public function logoUrl(): ?string
    {
        return ImageStorage::url($this->logo);
    }

    public function coverUrl(): ?string
    {
        return ImageStorage::url($this->cover_image);
    }

    public function isPublic(): bool
    {
        return $this->status === 'active';
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->where(function (Builder $query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();
    }

    public function currentPlan(): ?Plan
    {
        return $this->currentSubscription()?->plan;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->currentPlan()?->hasFeature($feature) ?? false;
    }

    public function productCount(): int
    {
        return Product::query()
            ->whereHas('category', fn (Builder $query) => $query->where('establishment_id', $this->id))
            ->count();
    }

    public function isWithinSchedule(): bool
    {
        $schedules = $this->schedules()->where('status', 'active')->get();

        if ($schedules->isEmpty()) {
            return true;
        }

        $now = now();

        return $schedules->contains(fn (Schedule $schedule) => $schedule->isOpenAt($now));
    }

    /**
     * Motivo por el que no se reciben pedidos, o null si se pueden recibir.
     */
    public function orderBlockReason(): ?string
    {
        return match (true) {
            ! $this->isPublic() => 'Este establecimiento no está disponible.',
            $this->operating_status !== 'open' => 'Este establecimiento no está recibiendo pedidos en este momento.',
            ! $this->hasFeature('public_order') => 'Este establecimiento no tiene pedidos en línea habilitados.',
            ! $this->isWithinSchedule() => 'Este establecimiento está fuera de su horario de atención.',
            default => null,
        };
    }

    public function canReceiveOrders(): bool
    {
        return $this->orderBlockReason() === null;
    }
}
