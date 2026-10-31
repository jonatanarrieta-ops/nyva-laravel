<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('responses do not start with a byte order mark', function () {
    expect(substr($this->get('/')->getContent(), 0, 15))->toStartWith('<!DOCTYPE html>');
});
