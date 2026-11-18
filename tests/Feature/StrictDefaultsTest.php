<?php

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;

test('models are strict outside production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

test('dates are immutable', function () {
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});

test('destructive database commands are blocked in production', function () {
    $app = app();
    $provider = new AppServiceProvider($app);
    $app['env'] = 'production';
    $provider->boot();

    $command = artisan(WipeCommand::class, ['--force' => true]);
    assert($command instanceof PendingCommand);
    $command->assertFailed()->run();

    // Model strictness is static; restore it for the following tests.
    $app['env'] = 'testing';
    $provider->boot();
});
