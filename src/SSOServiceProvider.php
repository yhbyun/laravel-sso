<?php

namespace losted\SSO;

class SSOServiceProvider extends \Illuminate\Support\ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/sso.php';
        $this->mergeConfigFrom($configPath, 'sso');

        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations')
        ], 'migrations');

        $this->loadRoutesFrom(__DIR__ . '/routes.php');

    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \losted\SSO\Console\Commands\CreateBroker::class,
                \losted\SSO\Console\Commands\RemoveBroker::class,
                \losted\SSO\Console\Commands\ListBrokers::class,
            ]);
        }
    }

}