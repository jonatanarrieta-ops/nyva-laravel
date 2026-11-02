<?php

namespace App\Services\Chatbot;

class IntentResolver
{
    public const GREETING = 'greeting';

    public const HOURS = 'hours';

    public const LOCATION = 'location';

    public const CATEGORIES = 'categories';

    public const MENU = 'menu';

    public const PRODUCT = 'product';

    public const FAQ = 'faq';

    public const UNKNOWN = 'unknown';

    /**
     * Resuelve la intención informativa de un mensaje normalizado.
     */
    public function resolve(string $message, EstablishmentContext $context): string
    {
        return match (true) {
            $this->bestFaq($message, $context) !== null => self::FAQ,
            (bool) preg_match('/\b(horario|horarios|hora|abren|abierto|cierran|cerrado|atienden)\b/', $message) => self::HOURS,
            (bool) preg_match('/\b(direccion|ubicacion|ubicados|donde|telefono|contacto|correo|llegar)\b/', $message) => self::LOCATION,
            (bool) preg_match('/\b(categoria|categorias|secciones)\b/', $message) => self::CATEGORIES,
            $context->matchProducts($message)->isNotEmpty() => self::PRODUCT,
            (bool) preg_match('/\b(menu|carta|productos|catalogo|venden|ofrecen|tienen)\b/', $message) => self::MENU,
            (bool) preg_match('/^(hola|buenas|buenos dias|buenas tardes|buenas noches|hey|saludos)\b/', $message) => self::GREETING,
            default => self::UNKNOWN,
        };
    }

    public function bestFaq(string $message, EstablishmentContext $context): ?string
    {
        $words = array_filter(EstablishmentContext::stems($message), fn (string $word) => strlen($word) > 3);

        if ($words === []) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($context->faqs() as $faq) {
            $questionWords = array_filter(
                EstablishmentContext::stems(EstablishmentContext::normalize($faq->question)),
                fn (string $word) => strlen($word) > 3,
            );

            if ($questionWords === []) {
                continue;
            }

            $score = count(array_intersect($questionWords, $words)) / count($questionWords);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $faq->answer;
            }
        }

        return $bestScore >= 0.5 ? $best : null;
    }
}
