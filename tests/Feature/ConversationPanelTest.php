<?php

use App\Models\User;
use App\Services\Chatbot\ChatbotService;

test('the owner sees the conversations of the establishment and their messages', function () {
    [$owner, $establishment] = catalogFixture();
    $chatbot = app(ChatbotService::class);
    $conversation = $chatbot->start($establishment);
    $chatbot->respond($establishment->fresh(), $conversation, '¿dónde están?');

    $this->actingAs($owner)
        ->get(route('conversations.index'))
        ->assertOk()
        ->assertSee('Conversación #'.$conversation->id)
        ->assertSee('2 mensajes')
        ->assertSee('Activa');

    $this->get(route('conversations.show', $conversation->id))
        ->assertOk()
        ->assertSeeInOrder(['Cliente', '¿dónde están?', 'Asistente', $establishment->address])
        ->assertSee('En curso');
});

test('the conversations can be filtered by status', function () {
    [$owner, $establishment] = catalogFixture();
    $chatbot = app(ChatbotService::class);
    $active = $chatbot->start($establishment);
    $closed = $chatbot->start($establishment);
    $chatbot->close($closed);

    $this->actingAs($owner)
        ->get(route('conversations.index', ['status' => 'closed']))
        ->assertSee('Conversación #'.$closed->id)
        ->assertDontSee('Conversación #'.$active->id.'<', false);

    $this->get(route('conversations.show', $closed->id))->assertSee('Cerrada');
});

test('the order messages are marked in the history', function () {
    [$owner, $establishment] = catalogFixture();
    $chatbot = app(ChatbotService::class);
    $conversation = $chatbot->start($establishment);
    $chatbot->respond($establishment->fresh(), $conversation, 'agrega hamburguesa clasica');

    $this->actingAs($owner)
        ->get(route('conversations.show', $conversation->id))
        ->assertSee('Pedido')
        ->assertSee('Total del pedido: $ 25.000');
});

test('conversations of another establishment are not visible', function () {
    [$owner, $establishment] = catalogFixture();
    [, $other] = catalogFixture();
    $foreign = app(ChatbotService::class)->start($other);

    $this->actingAs($owner)
        ->get(route('conversations.index'))
        ->assertOk()
        ->assertDontSee('Conversación #'.$foreign->id);

    $this->get(route('conversations.show', $foreign->id))->assertNotFound();
    $this->get(route('conversations.index', ['establishment' => $other->id]))->assertNotFound();
});

test('the conversations panel requires an establishment account', function () {
    [, $establishment] = catalogFixture();
    $conversation = app(ChatbotService::class)->start($establishment);

    $this->get(route('conversations.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get(route('conversations.show', $conversation->id))
        ->assertForbidden();
});
