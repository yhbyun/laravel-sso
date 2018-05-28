<?php

namespace Losted\SSO;

use Illuminate\Support\ServiceProvider;

class SSOServiceProvider extends ServiceProvider
{
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

        $server = $this->app->config['sso']['custom_server'] ?? Server::class;

        $this->app->bind(
            \Losted\SSO\Contracts\Server::class,
            $server
        );
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Losted\SSO\Console\Commands\CreateBroker::class,
                \Losted\SSO\Console\Commands\RemoveBroker::class,
                \Losted\SSO\Console\Commands\ListBrokers::class,
            ]);
        }
    }
}
