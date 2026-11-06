<?php

namespace App\Providers;

use App\Http\Middleware\EnsureEstablishmentAccess;
use App\Http\Middleware\EnsurePlatformAdmin;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        Livewire::addPersistentMiddleware([EnsurePlatformAdmin::class, EnsureEstablishmentAccess::class]);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('chatbot-conversations', fn (Request $request) => Limit::perMinute(10)
            ->by((string) $request->ip())
            ->response($this->tooManyRequests('Abriste muchas conversaciones. Espera un momento.')));

        RateLimiter::for('chatbot-history', fn (Request $request) => Limit::perMinute(30)
            ->by((string) $request->ip())
            ->response($this->tooManyRequests('Consultaste el historial muchas veces. Espera un momento.')));

        RateLimiter::for('chatbot-messages', fn (Request $request) => Limit::perMinute(20)
            ->by((string) $request->ip())
            ->response($this->tooManyRequests('Enviaste muchos mensajes. Espera un momento.')));
    }

    /**
     * @return Closure(Request, array<string, string>): JsonResponse
     */
    private function tooManyRequests(string $message): Closure
    {
        /** @param array<string, string> $headers */
        return fn (Request $request, array $headers): JsonResponse => response()->json(['message' => $message], 429, $headers);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
