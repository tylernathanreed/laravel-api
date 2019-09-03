<?php

namespace Reedware\LaravelApi;

use Reedware\LaravelApi\ApiManager;
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
        $this->registerJiraApiManager();
    }

    /**
     * Registers the Jira service.
     *
     * @return void
     */
    protected function registerJiraApiManager()
    {
        $this->app->singleton(ApiManager::class);
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
        return config_path('api.php');
    }
}
