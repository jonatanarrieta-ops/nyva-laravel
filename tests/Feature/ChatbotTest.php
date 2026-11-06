<?php

use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Product;
use App\Models\Schedule;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function chat(Establishment $establishment): object
{
    return new class($establishment)
    {
        public Conversation $conversation;

        private ChatbotService $service;

        public function __construct(public Establishment $establishment)
        {
            $this->service = app(ChatbotService::class);
            $this->conversation = $this->service->start($establishment);
        }

        public function say(string $message): string
        {
            return $this->service->respond($this->establishment->fresh(), $this->conversation->fresh(), $message)->text;
        }

        public function reply(string $message): object
        {
            return $this->service->respond($this->establishment->fresh(), $this->conversation->fresh(), $message);
        }
    };
}

function orderUpTo(object $chat): void
{
    $chat->say('agrega 2 hamburguesa clasica');
    $chat->say('listo');
    $chat->say('Ana Ruiz');
    $chat->say('3001234567');
    $chat->say('Calle 10 # 20-30');
}

test('it answers with the real data of the establishment', function () {
    [, $establishment, $category, $product] = catalogFixture();
    Schedule::forceCreate(['establishment_id' => $establishment->id, 'day_of_week' => 'monday', 'opens_at' => '08:00:00', 'closes_at' => '20:00:00', 'status' => 'active']);
    $establishment->frequentlyAskedQuestions()->create(['question' => '¿Qué métodos de pago aceptan?', 'answer' => 'Efectivo y transferencia.', 'status' => 'active']);
    $chat = chat($establishment);

    expect($chat->say('Hola'))->toContain($establishment->name)
        ->and($chat->say('¿Cuál es el horario?'))->toContain('Lunes: 08:00 a 20:00')
        ->and($chat->say('¿Dónde están ubicados?'))->toContain($establishment->address)
        ->and($chat->say('¿Qué categorías tienen?'))->toContain($category->name)
        ->and($chat->say('¿Cuánto cuesta la hamburguesa clásica?'))->toContain('$ 25.000')
        ->and($chat->say('¿Qué métodos de pago aceptan?'))->toBe('Efectivo y transferencia.');
});

test('it does not invent information', function () {
    [, $establishment] = catalogFixture();

    expect(chat($establishment)->say('¿Hacen envíos a Marte?'))->toContain('No tengo esa información');
});

test('it reports the availability of a product', function () {
    [, $establishment, , $product] = catalogFixture(['available' => false]);

    expect(chat($establishment)->say('tienen hamburguesa clasica'))->toContain('No está disponible');
});

test('a chatbot never exposes products of another establishment', function () {
    [, $establishment] = catalogFixture();
    [, , $foreignCategory] = catalogFixture();
    Product::create(['category_id' => $foreignCategory->id, 'name' => 'Sushi secreto', 'price' => 9000, 'status' => 'active', 'available' => true]);
    $chat = chat($establishment);

    expect($chat->say('¿tienen sushi secreto?'))->not->toContain('9.000')
        ->and($chat->say('quiero sushi secreto'))->not->toContain('Agregué');
});

test('the full order flow registers the order only after explicit confirmation', function () {
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);

    orderUpTo($chat);

    expect(Order::count())->toBe(0);

    $review = $chat->reply('hola');

    expect($review->text)->toContain('necesito tu confirmación')
        ->and($review->summary['total'])->toBe('$ 50.000')
        ->and(Order::count())->toBe(0);

    $confirmed = $chat->reply('confirmar pedido');
    $order = Order::with('orderItems')->firstOrFail();

    expect($order->channel)->toBe('chat')
        ->and($order->status)->toBe('pending')
        ->and((float) $order->total)->toBe(50000.0)
        ->and($order->customer_name)->toBe('Ana Ruiz')
        ->and($confirmed->link)->toBe(route('orders.track', $order->public_token));
});

test('products can be added, changed and removed during the order', function () {
    [, $establishment, $category] = catalogFixture();
    Product::create(['category_id' => $category->id, 'name' => 'Gaseosa', 'price' => 5000, 'status' => 'active', 'available' => true]);
    $chat = chat($establishment);

    expect($chat->reply('quiero 2 hamburguesa clasica y 1 gaseosa')->summary['total'])->toBe('$ 55.000')
        ->and($chat->reply('cambia gaseosa a 3')->summary['total'])->toBe('$ 65.000')
        ->and($chat->reply('quita la hamburguesa clasica')->summary['total'])->toBe('$ 15.000')
        ->and($chat->reply('ver pedido')->summary['lines'])->toHaveCount(1);
});

test('invalid quantities are rejected while editing', function () {
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);
    $chat->say('agrega hamburguesa clasica');

    expect($chat->say('cambia hamburguesa clasica a 0'))->toContain('entre 1 y 99')
        ->and($chat->say('cambia hamburguesa clasica a 500'))->toContain('entre 1 y 99');
});

test('the order can be cancelled at any step without registering anything', function () {
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);
    orderUpTo($chat);

    expect($chat->say('Cancelar pedido'))->toContain('Cancelé')
        ->and($chat->say('confirmar pedido'))->not->toContain('registrado')
        ->and(Order::count())->toBe(0);
});

test('customer data is validated during the conversation', function () {
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);
    $chat->say('agrega hamburguesa clasica');
    $chat->say('listo');
    $chat->say('Ana');

    expect($chat->say('no tengo'))->toContain('no parece válido');
});

test('an unavailable product is not added', function () {
    [, $establishment] = catalogFixture(['available' => false]);

    expect(chat($establishment)->say('quiero hamburguesa clasica'))->toContain('no está disponible');
});

test('availability is validated again when confirming', function () {
    [, $establishment, , $product] = catalogFixture();
    $chat = chat($establishment);
    orderUpTo($chat);

    $product->update(['available' => false]);

    expect($chat->say('confirmo'))->toContain('no está disponible')
        ->and(Order::count())->toBe(0);
});

test('orders are refused outside the schedule or while closed', function () {
    [, $establishment] = catalogFixture();
    $day = strtolower(now()->addDay()->format('l'));
    Schedule::forceCreate(['establishment_id' => $establishment->id, 'day_of_week' => $day, 'opens_at' => '08:00:00', 'closes_at' => '09:00:00', 'status' => 'active']);

    expect(chat($establishment)->say('quiero hamburguesa clasica'))->toContain('fuera de su horario');

    $establishment->schedules()->delete();
    $establishment->update(['operating_status' => 'closed']);

    expect(chat($establishment)->say('quiero hamburguesa clasica'))->toContain('no está recibiendo pedidos');
});

test('an interrupted conversation is recovered and reports changes', function () {
    [, $establishment, , $product] = catalogFixture();
    $chat = chat($establishment);
    $chat->say('agrega hamburguesa clasica');
    $chat->say('listo');
    $product->update(['price' => 30000]);

    $resumed = app(ChatbotService::class)->resume($establishment, $chat->conversation->fresh());

    expect($resumed->text)->toContain('Retomemos')
        ->and($resumed->summary['total'])->toBe('$ 30.000');
});

test('an old draft expires', function () {
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);
    $chat->say('agrega hamburguesa clasica');
    $chat->conversation->fresh()->update(['last_activity_at' => now()->subDays(2)]);

    expect(app(ChatbotService::class)->resume($establishment, $chat->conversation->fresh()))->toBeNull()
        ->and($chat->conversation->fresh()->draft)->toBeNull();
});

test('a conversation of another establishment cannot be used', function () {
    [, $establishment] = catalogFixture();
    [, $other] = catalogFixture();
    $conversation = chat($other)->conversation;

    expect(app(ChatbotService::class)->find($establishment, $conversation->token))->toBeNull();
});

test('the widget talks through the backend and stores the history', function () {
    [, $establishment] = catalogFixture();

    Livewire::test('pages::chatbot.widget', ['establishmentId' => $establishment->id])
        ->set('message', 'Hola')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('Puedo contarte');

    expect(Conversation::firstOrFail()->messages()->count())->toBe(2);
});

test('the widget respects the settings and the plan', function () {
    [, $establishment] = catalogFixture();
    $establishment->chatbot()->update(['enabled' => false]);

    Livewire::test('pages::chatbot.widget', ['establishmentId' => $establishment->id])
        ->assertDontSee('Asistente virtual')
        ->call('quick', 'Hola')
        ->assertForbidden();

    expect(Conversation::count())->toBe(0);
});

test('a plan without the chatbot hides it', function () {
    [, $establishment] = catalogFixture();
    $establishment->currentPlan()->update(['features' => ['public_order']]);

    expect(app(ChatbotService::class)->isAvailable($establishment->fresh()))->toBeFalse();
});

test('the message length is limited', function () {
    [, $establishment] = catalogFixture();

    Livewire::test('pages::chatbot.widget', ['establishmentId' => $establishment->id])
        ->set('message', str_repeat('a', 501))
        ->call('send')
        ->assertHasErrors('message');
});

test('the optional provider only writes informative answers', function () {
    config(['services.chatbot' => ['driver' => 'openai', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta redactada']]]])]);
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);

    expect($chat->say('¿dónde están?'))->toBe('Respuesta redactada');

    Http::fake()->assertSentCount(0);
    expect($chat->say('agrega hamburguesa clasica'))->toContain('Agregué');
});

test('if the provider fails the structured answer is used', function () {
    config(['services.chatbot' => ['driver' => 'anthropic', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response([], 500)]);
    [, $establishment] = catalogFixture();

    expect(chat($establishment)->say('¿dónde están?'))->toContain($establishment->address);
});

test('the provider receives the recent conversation as alternating turns', function () {
    config(['services.chatbot' => ['driver' => 'gemini', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Respuesta redactada']]]]]])]);
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);

    $chat->say('¿tienen hamburguesa clasica?');
    $chat->say('¿y cuánto vale?');

    $contents = data_get(Http::recorded()[1][0]->data(), 'contents');

    expect(array_column($contents, 'role'))->toBe(['user', 'model', 'user'])
        ->and($contents[0]['parts'][0]['text'])->toBe('¿tienen hamburguesa clasica?')
        ->and($contents[1]['parts'][0]['text'])->toBe('Respuesta redactada')
        ->and($contents[2]['parts'][0]['text'])->toBe('¿y cuánto vale?');
});

test('the order messages never reach the provider', function () {
    config(['services.chatbot' => ['driver' => 'openai', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta redactada']]]])]);
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);

    orderUpTo($chat);
    $chat->say('cancelar');
    $chat->say('¿dónde están?');

    Http::assertSentCount(1);
    Http::assertSent(function ($request) {
        $sent = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

        return ! str_contains($sent, 'Ana Ruiz')
            && ! str_contains($sent, 'Calle 10')
            && count($request->data()['messages']) === 2
            && data_get($request->data(), 'messages.1.content') === '¿dónde están?';
    });

    expect($chat->conversation->messages()->where('role', 'user')->where('content', 'Ana Ruiz')->value('payload'))
        ->toBe([ChatbotService::ORDER_FLAG => true]);
});

test('the history sent to the provider is limited', function () {
    config(['services.chatbot' => ['driver' => 'anthropic', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response(['content' => [['text' => 'Respuesta redactada']]])]);
    [, $establishment] = catalogFixture();
    $chat = chat($establishment);

    foreach (range(1, 6) as $number) {
        $chat->say("¿dónde están? pregunta {$number}");
    }

    $messages = data_get(Http::recorded()[5][0]->data(), 'messages');

    expect($messages)->toHaveCount(9)
        ->and($messages[0]['role'])->toBe('user')
        ->and($messages[0]['content'])->toBe('¿dónde están? pregunta 2')
        ->and(end($messages)['content'])->toBe('¿dónde están? pregunta 6');
});

test('every provider limits the length of the answer', function (string $driver, array $response, string $limitPath) {
    config(['services.chatbot' => ['driver' => $driver, 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response($response)]);
    [, $establishment] = catalogFixture();

    expect(chat($establishment)->say('¿dónde están?'))->toBe('Respuesta redactada');

    Http::assertSent(fn ($request) => data_get($request->data(), $limitPath) === 400);
})->with([
    'gemini' => ['gemini', ['candidates' => [['content' => ['parts' => [['text' => 'Respuesta redactada']]]]]], 'generationConfig.maxOutputTokens'],
    'anthropic' => ['anthropic', ['content' => [['text' => 'Respuesta redactada']]], 'max_tokens'],
    'openai' => ['openai', ['choices' => [['message' => ['content' => 'Respuesta redactada']]]], 'max_completion_tokens'],
]);

test('the provider is retried only once', function () {
    config(['services.chatbot' => ['driver' => 'gemini', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response([], 503)]);
    [, $establishment] = catalogFixture();

    expect(chat($establishment)->say('¿dónde están?'))->toContain($establishment->address);

    Http::assertSentCount(2);
});
