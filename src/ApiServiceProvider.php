<?php

namespace Reedware\LaravelApi;

use Reedware\LaravelApi\ApiManager;
use Reedware\LaravelApi\Connectors\ConnectionFactory;
use Reedware\LaravelApi\Support\DeferredServiceProvider;

class ApiServiceProvider extends DeferredServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConnectionServices();
    }

    /**
     * Registers the primary api connection bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        $this->registerApiConnectionFactory();
        $this->registerApiManager();
        $this->registerApiDefaultConnection();
    }

    /**
     * Registers the api connection factory.
     *
     * @return void
     */
    protected function registerApiConnectionFactory()
    {
        // The connection factory is used to create the actual connection instances on
        // to the web api. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.

        // Register the service
        $this->app->singleton(ConnectionFactory::class, function($app) {
            return new ConnectionFactory($app);
        });

        // Alias the binding
        $this->app->alias(ConnectionFactory::class, 'api.factory');
    }

    /**
     * Registers the api manager.
     *
     * @return void
     */
    protected function registerApiManager()
    {
        // The web api manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.

        // Register the service
        $this->app->singleton(ApiManager::class, function($app) {
            return new ApiManager($app, $app['api.factory']);
        });

        // Alias the binding
        $this->app->alias(ApiManager::class, 'api');
    }

    /**
     * Registers the default api connection.
     *
     * @return void
     */
    protected function registerApiDefaultConnection()
    {
        // Register the service
        $this->app->bind('api.connection', function($app) {
            return $app['api']->connection();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the configuration file
        $this->publishConfigurationFile();
    }

    /**
     * Publishes the configuration file.
     *
     * @return void
     */
    protected function publishConfigurationFile()
    {
        // Determine the local configuration path
        $source = $this->getLocalConfigurationPath();

        // Determine the application configuration path
        $destination = $this->getApplicationConfigPath();

        // Publish the configuration file
        $this->publishes([$source => $destination], 'config');
    }

    /**
     * Returns the path to the configuration file within this package.
     *
     * @return string
     */
    protected function getLocalConfigurationPath()
    {
        return __DIR__ . '/../config/api.php';
    }

    /**
     * Returns the path to the configuration file within the application.
     *
     * @return string
     */
    protected function getApplicationConfigPath()
    {
        return $this->app->basePath('config/api.php');
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            ConnectionFactory::class, 'api.factory',
            ApiManager::class, 'api',
            'api.connection'
        ];
    }
}
