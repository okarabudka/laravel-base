<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureModels();
        $this->configureDates();
        $this->configureCommands();
    }

    /**
     * Fail loudly on lazy loading, unfillable and missing attributes outside production.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Block db:wipe, migrate:fresh and similar commands in production.
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
