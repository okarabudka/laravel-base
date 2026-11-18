<?php

use function Pest\Laravel\get;

test('the start page shows the application name', function () {
    config(['app.name' => 'Template Check']);

    get('/')->assertOk()->assertSee('<title>Template Check</title>', escape: false);
});

test('the health route responds', function () {
    get('/up')->assertOk();
});
