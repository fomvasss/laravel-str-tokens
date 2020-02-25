<?php

namespace Fomvasss\LaravelStrTokens;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__.'/../config/str-tokens.php' => config_path('str-tokens.php')
        ], 'str-tokens-config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/str-tokens.php', 'str-tokens');

        $this->app->singleton(StrTokenGenerator::class, function () {
            return new StrTokenGenerator($this->app);
        });
    }
}
