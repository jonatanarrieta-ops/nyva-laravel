<?php

use App\Models\Conversation;
use App\Models\Establishment;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function openConversation(Establishment $establishment): string
{
    return test()->postJson(route('api.establishments.conversations.store', $establishment))
        ->assertCreated()
        ->json('token');
}

function sendMessage(Establishment $establishment, string $token, string $message): TestResponse
{
    return test()->postJson(
        route('api.establishments.conversations.messages.store', [$establishment, $token]),
        ['message' => $message],
    );
}

test('the api opens a conversation with the welcome message', function () {
    [, $establishment] = catalogFixture();
    $establishment->chatbot()->update(['welcome_message' => 'Bienvenido al local']);

    $this->postJson(route('api.establishments.conversations.store', $establishment))
        ->assertCreated()
        ->assertJsonPath('welcome', 'Bienvenido al local')
        ->assertJsonStructure(['token', 'welcome']);

    expect(Conversation::firstOrFail())
        ->establishment_id->toBe($establishment->id)
        ->status->toBe('active');
});

test('the api answers with the real data of the establishment', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    sendMessage($establishment, $token, '¿dónde están?')
        ->assertCreated()
        ->assertJsonPath('reply.text', fn (string $text) => str_contains($text, $establishment->address));
});

test('the api returns the history in order', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);
    sendMessage($establishment, $token, 'Hola');

    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))
        ->assertOk()
        ->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.0.role', 'user')
        ->assertJsonPath('messages.0.content', 'Hola')
        ->assertJsonPath('messages.1.role', 'assistant');
});

test('the api registers an order only after explicit confirmation', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    foreach (['agrega 2 hamburguesa clasica', 'listo', 'Ana Ruiz', '3001234567', 'Calle 10 # 20-30'] as $message) {
        sendMessage($establishment, $token, $message)->assertCreated();
    }

    expect($establishment->orders()->count())->toBe(0);

    sendMessage($establishment, $token, 'confirmo')
        ->assertCreated()
        ->assertJsonPath('reply.text', fn (string $text) => str_contains($text, 'fue registrado'))
        ->assertJsonStructure(['reply' => ['text', 'link']]);

    expect($establishment->orders()->count())->toBe(1);
});

test('reading the history recovers the order and reports changes', function () {
    [, $establishment, , $product] = catalogFixture();
    $token = openConversation($establishment);
    sendMessage($establishment, $token, 'agrega hamburguesa clasica');
    sendMessage($establishment, $token, 'listo');
    $product->update(['price' => 30000]);

    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))
        ->assertOk()
        ->assertJsonPath('messages.4.content', fn (string $text) => str_starts_with($text, 'Retomemos'))
        ->assertJsonPath('messages.4.payload.summary.total', '$ 30.000');

    // Consultar otra vez no repite el aviso.
    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))
        ->assertJsonCount(5, 'messages');
});

test('reading the history discards an old draft', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);
    sendMessage($establishment, $token, 'agrega hamburguesa clasica');
    Conversation::firstOrFail()->update(['last_activity_at' => now()->subDays(2)]);

    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))->assertOk();

    expect(Conversation::firstOrFail()->draft)->toBeNull();
});

test('the api closes a conversation and discards the draft', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);
    sendMessage($establishment, $token, 'agrega hamburguesa clasica');

    $this->deleteJson(route('api.establishments.conversations.destroy', [$establishment, $token]))
        ->assertNoContent();

    expect(Conversation::firstOrFail())
        ->status->toBe('closed')
        ->ended_at->not->toBeNull()
        ->draft->toBeNull();

    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))->assertNotFound();
    sendMessage($establishment, $token, 'Hola')->assertNotFound();
    $this->deleteJson(route('api.establishments.conversations.destroy', [$establishment, $token]))->assertNotFound();
});

test('a token of another establishment cannot be used through the api', function () {
    [, $establishment] = catalogFixture();
    [, $other] = catalogFixture();
    $token = openConversation($other);

    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))->assertNotFound();
    sendMessage($establishment, $token, 'Hola')->assertNotFound();
    $this->deleteJson(route('api.establishments.conversations.destroy', [$establishment, $token]))->assertNotFound();

    expect(Conversation::firstOrFail()->status)->toBe('active');
});

test('the api is not available when the chatbot is disabled or not in the plan', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);
    $establishment->chatbot()->update(['enabled' => false]);

    $this->postJson(route('api.establishments.conversations.store', $establishment))->assertNotFound();
    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))->assertNotFound();
    sendMessage($establishment, $token, 'Hola')->assertNotFound();

    $establishment->chatbot()->update(['enabled' => true]);
    $establishment->currentPlan()->update(['features' => ['public_order']]);

    $this->postJson(route('api.establishments.conversations.store', $establishment))->assertNotFound();
});

test('the api validates the message', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    sendMessage($establishment, $token, '')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message' => 'Escribe un mensaje.']);

    sendMessage($establishment, $token, str_repeat('a', 501))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message' => 'El mensaje es demasiado largo.']);
});

test('the api limits the conversations opened per minute', function () {
    [, $establishment] = catalogFixture();

    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('api.establishments.conversations.store', $establishment))->assertCreated();
    }

    $this->postJson(route('api.establishments.conversations.store', $establishment))
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'Abriste muchas conversaciones. Espera un momento.');
});

test('the api limits the messages per minute', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    foreach (range(1, 20) as $attempt) {
        sendMessage($establishment, $token, 'Hola')->assertCreated();
    }

    sendMessage($establishment, $token, 'Hola')
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'Enviaste muchos mensajes. Espera un momento.');
});

test('reading the history has its own limit', function () {
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    foreach (range(1, 30) as $attempt) {
        $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))->assertOk();
    }

    $this->getJson(route('api.establishments.conversations.show', [$establishment, $token]))
        ->assertTooManyRequests()
        ->assertJsonPath('message', 'Consultaste el historial muchas veces. Espera un momento.');

    // Leer el historial no consume el límite para abrir conversaciones.
    $this->postJson(route('api.establishments.conversations.store', $establishment))->assertCreated();
});

test('the gemini driver writes informative answers through the api', function () {
    config(['services.chatbot' => ['driver' => 'gemini', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Respuesta de Gemini']]]]]])]);
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    sendMessage($establishment, $token, '¿dónde están?')->assertJsonPath('reply.text', 'Respuesta de Gemini');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/modelo-de-prueba:generateContent'
        && $request->hasHeader('x-goog-api-key', 'clave-de-prueba')
        && str_contains((string) data_get($request->data(), 'system_instruction.parts.0.text'), $establishment->address)
        && data_get($request->data(), 'contents.0.role') === 'user');

    sendMessage($establishment, $token, 'agrega hamburguesa clasica')
        ->assertJsonPath('reply.text', fn (string $text) => str_contains($text, 'Agregué'));

    Http::assertSentCount(1);
});

test('if gemini fails or answers empty the structured answer is used', function (array $response, int $status) {
    config(['services.chatbot' => ['driver' => 'gemini', 'key' => 'clave-de-prueba', 'model' => 'modelo-de-prueba', 'url' => null]]);
    Http::fake(['*' => Http::response($response, $status)]);
    [, $establishment] = catalogFixture();
    $token = openConversation($establishment);

    sendMessage($establishment, $token, '¿dónde están?')
        ->assertCreated()
        ->assertJsonPath('reply.text', fn (string $text) => str_contains($text, $establishment->address));
})->with([
    'error del proveedor' => [[], 500],
    'clave inválida' => [['error' => ['message' => 'API key not valid']], 400],
    'respuesta bloqueada' => [['candidates' => [['finishReason' => 'SAFETY']]], 200],
]);
