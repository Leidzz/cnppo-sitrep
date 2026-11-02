<?php

it('redirects the root URL to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

it('shows the login page to guests', function () {
    $this->get(route('login'))->assertOk();
});