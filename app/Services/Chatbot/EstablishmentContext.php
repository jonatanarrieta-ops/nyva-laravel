<?php

namespace App\Services\Chatbot;

use App\Models\Category;
use App\Models\Establishment;
use App\Models\FrequentlyAskedQuestion;
use App\Models\Product;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Único punto de acceso del chatbot a los datos: todo queda limitado a un establecimiento.
 */
class EstablishmentContext
{
    /** @var Collection<int, Product>|null */
    private ?Collection $products = null;

    public function __construct(public Establishment $establishment) {}

    /**
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        return $this->products ??= Product::query()
            ->with('category')
            ->where('status', 'active')
            ->whereHas('category', function (Builder $query) {
                $query->where('establishment_id', $this->establishment->id)
                    ->where('status', 'active');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return $this->establishment->categories()
            ->where('status', 'active')
            ->whereHas('products', fn (Builder $query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, FrequentlyAskedQuestion>
     */
    public function faqs(): Collection
    {
        return $this->establishment->frequentlyAskedQuestions()->where('status', 'active')->get();
    }

    /**
     * @return Collection<int, string>
     */
    public function scheduleLines(): Collection
    {
        $order = array_keys(Schedule::DAYS);

        return $this->establishment->schedules()
            ->where('status', 'active')
            ->get()
            ->sortBy(fn (Schedule $schedule) => array_search(strtolower($schedule->day_of_week), $order, true))
            ->map(fn (Schedule $schedule) => sprintf(
                '%s: %s a %s',
                $schedule->dayLabel(),
                substr($schedule->opens_at, 0, 5),
                substr($schedule->closes_at, 0, 5),
            ))
            ->values();
    }

    /**
     * Productos cuyo nombre coincide por completo con el texto; si no hay, los que coinciden en parte.
     *
     * @return Collection<int, Product>
     */
    public function matchProducts(string $normalizedText): Collection
    {
        $words = self::stems($normalizedText);

        $exact = $this->products()->filter(function (Product $product) use ($words) {
            return array_diff(self::stems(self::normalize($product->name)), $words) === [];
        });

        if ($exact->isNotEmpty()) {
            $longest = $exact->max(fn (Product $product) => count(self::stems(self::normalize($product->name))));

            return $exact
                ->filter(fn (Product $product) => count(self::stems(self::normalize($product->name))) === $longest)
                ->values();
        }

        return $this->products()
            ->filter(fn (Product $product) => array_intersect(self::stems(self::normalize($product->name)), $words) !== [])
            ->values();
    }

    public function orderBlockReason(): ?string
    {
        if (! $this->establishment->chatbot()->orders_enabled) {
            return 'Los pedidos por chat no están habilitados en este establecimiento.';
        }

        return $this->establishment->orderBlockReason();
    }

    /**
     * Datos autorizados para responder; no incluye información interna.
     */
    public function facts(): string
    {
        $establishment = $this->establishment;

        $products = $this->products()->map(fn (Product $product) => sprintf(
            '- %s (%s): %s, %s. %s',
            $product->name,
            $product->category?->name,
            self::money($product->price),
            $product->isOrderable() ? 'disponible' : 'no disponible',
            $product->description,
        ))->implode("\n");

        $faqs = $this->faqs()
            ->map(fn (FrequentlyAskedQuestion $faq) => "- {$faq->question}: {$faq->answer}")
            ->implode("\n");

        return implode("\n", array_filter([
            "Establecimiento: {$establishment->name} ({$establishment->type})",
            $establishment->description,
            "Dirección: {$establishment->address}",
            "Teléfono: {$establishment->phone}",
            $this->scheduleLines()->isNotEmpty() ? "Horarios:\n".$this->scheduleLines()->implode("\n") : null,
            $establishment->chatbot()->business_info,
            $products !== '' ? "Productos:\n{$products}" : null,
            $faqs !== '' ? "Preguntas frecuentes:\n{$faqs}" : null,
        ]));
    }

    public static function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));

        return trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9\s]/', ' ', $text)));
    }

    public static function money(string|float $amount): string
    {
        return '$ '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * @return array<int, string>
     */
    public static function stems(string $normalizedText): array
    {
        $ignored = ['de', 'del', 'la', 'el', 'los', 'las', 'con', 'y', 'un', 'una', 'al'];

        return array_values(array_map(
            fn (string $word) => strlen($word) > 3 ? rtrim($word, 's') : $word,
            array_diff(explode(' ', $normalizedText), $ignored, ['']),
        ));
    }
}
