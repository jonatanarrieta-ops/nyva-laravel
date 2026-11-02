<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

#[Signature('nyva:check-subscriptions')]
#[Description('Avisa sobre suscripciones vencidas o próximas a vencer')]
class CheckSubscriptions extends Command
{
    private const WARNING_DAYS = 7;

    public function handle(): int
    {
        $admins = User::whereIn('role', User::PLATFORM_ROLES)->get();

        Subscription::query()
            ->with('establishment.user')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', now()->addDays(self::WARNING_DAYS)->toDateString())
            ->get()
            ->each(function (Subscription $subscription) use ($admins) {
                $expired = $subscription->end_date->isPast() && ! $subscription->end_date->isToday();
                $state = $expired ? 'expired' : 'expiring';

                // Un solo aviso por suscripción, fecha de vencimiento y estado.
                $key = "subscription-alert:{$subscription->id}:{$subscription->end_date->toDateString()}:{$state}";

                if (! Cache::add($key, true, now()->addDays(60))) {
                    return;
                }

                $name = $subscription->establishment?->name;
                $date = $subscription->end_date->format('d/m/Y');
                $alert = new AdminAlert(
                    $expired ? 'Suscripción vencida' : 'Suscripción por vencer',
                    "{$name}: ".($expired ? 'venció' : 'vence')." el {$date}.",
                    route('admin.subscriptions', absolute: false),
                );

                Notification::send($admins, $alert);
                $subscription->establishment?->user?->notify(new AdminAlert(
                    $expired ? 'Tu suscripción venció' : 'Tu suscripción está por vencer',
                    "{$name}: ".($expired ? 'venció' : 'vence')." el {$date}.",
                ));
            });

        return self::SUCCESS;
    }
}
