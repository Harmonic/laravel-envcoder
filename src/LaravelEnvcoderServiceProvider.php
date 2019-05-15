<?php

namespace harmonic\LaravelEnvcoder;

use harmonic\LaravelEnvcoder\Commands\LaravelEnvcoderEncrypt;
use harmonic\LaravelEnvcoder\Commands\LaravelEnvcoderDecrypt;
use harmonic\LaravelEnvcoder\Commands\LaravelEnvcoderCompare;
use Illuminate\Support\ServiceProvider;

class LaravelEnvcoderServiceProvider extends ServiceProvider {
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot() {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'harmonic');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'harmonic');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register() {
        //TODO: Config is not copying when published
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-envcoder.php', 'laravel-envcoder');

        // Register the service the package provides.
        $this->app->singleton('LaravelEnvcoder', function ($app) {
            return new LarevelEnvcoder;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return ['LaravelEnvcoder'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole() {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/laravel-envcoder.php' => config_path('laravel-envcoder.php'),
        ], 'laravel-envcoder.config'); //TODO: This isn't copying to config folder!

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/harmonic'),
        ], 'laravel-envcoder.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/harmonic'),
        ], 'laravel-envcoder.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/harmonic'),
        ], 'laravel-envcoder.views');*/

        // Registering package commands.
        $this->commands([
            LaravelEnvcoderEncrypt::class,
            LaravelEnvcoderDecrypt::class,
            LaravelEnvcoderCompare::class,
        ]);
    }
}
