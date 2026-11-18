<?php

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

test('models are strict outside production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

test('dates are immutable', function () {
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});

test('destructive database commands are blocked in production', function () {
    $provider = new AppServiceProvider($this->app);
    $this->app['env'] = 'production';
    $provider->boot();

    $this->artisan(WipeCommand::class, ['--force' => true])->assertFailed();

    // Model strictness is static; restore it for the following tests.
    $this->app['env'] = 'testing';
    $provider->boot();
});
