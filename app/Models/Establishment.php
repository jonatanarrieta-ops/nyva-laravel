<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establishment extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'description',
        'address',
        'phone',
        'email',
        'status',
        'registered_at',
    ];

    /**
     * @return BelongsTo<User, Establishment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Subscription, Establishment>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<Category, Establishment>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Schedule, Establishment>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return HasMany<Order, Establishment>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<FrequentlyAskedQuestion, Establishment>
     */
    public function frequentlyAskedQuestions(): HasMany
    {
        return $this->hasMany(FrequentlyAskedQuestion::class);
    }

    /**
     * @return HasMany<Conversation, Establishment>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }
}
