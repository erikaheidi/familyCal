<?php

test('index page shows public calendar placeholder when not configured', function () {
    config()->set('services.public_calendar.account_email', null);

    $response = $this->get('/');

    $response
        ->assertSuccessful()
        ->assertSee('Public calendar not configured')
        ->assertSee('Week')
        ->assertSee('Month');
});
