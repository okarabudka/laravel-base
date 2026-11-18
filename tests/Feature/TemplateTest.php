<?php

test('the start page shows the application name', function () {
    config(['app.name' => 'Template Check']);

    $this->get('/')->assertOk()->assertSee('<title>Template Check</title>', escape: false);
});

test('the health route responds', function () {
    $this->get('/up')->assertOk();
});
