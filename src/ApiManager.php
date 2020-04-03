<?php

namespace Reedware\LaravelApi;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Container\Container;
use Reedware\LaravelApi\Connectors\ConnectionFactory;

class ApiManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * The api connection factory instance.
     *
     * @var \Reedware\LaravelApi\Connectors\ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Create a new api manager instance.
     *
     * @param  \Illuminate\Container\Container                    $app
     * @param  \Reedware\LaravelApi\Connectors\ConnectionFactory  $factory
     *
     * @return $this
     */
    public function __construct(Container $app, ConnectionFactory $factory)
    {
        $this->app = $app;
        $this->factory = $factory;
    }

    /**
     * Returns the specifeid api connection instance.
     *
     * @param  string|null  $name
     *
     * @return \Reedware\LaravelApi\Connection
     */
    public function connection($name = null)
    {
        // Make sure a connection name is specified
        $name = $name ?: $this->getDefaultConnection();

        // If the connection has already been resolved, return it
        if(isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // Resolve, remember, and return the connection
        return $this->connections[$name] = $this->configure(
            $this->makeConnection($name)
        );
    }

    /**
     * Creates and returns the specified api connection instance.
     *
     * @param  string  $name
     *
     * @return \Reedware\LaravelApi\Connection
     */
    protected function makeConnection($name)
    {
        // Determine the configuration for the given connection name.
        $config = $this->configuration($name);

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // closure and pass it the config allowing it to resolve the connection.

        // If a custom connection extension exists, use it
        if(isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        // Determine the driver
        $driver = $config['driver'] ?? $this->getDefaultDriver();

        // Next we will check to see if an extension has been registered for a driver
        // and will call the closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.

        // If a custom driver extension exists, use it
        if(isset($this->extensions[$driver])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        // Pass the driver into the configuration
        $config['driver'] = $driver;

        // Create and return the connection
        return $this->factory->make($config, $name);
    }

    /**
     * Returns the configuration for the specified connection.
     *
     * @param  string  $name
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        // Make sure a connection name is specified
        $name = $name ?: $this->getDefaultConnection();

        // To get the web api connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.

        // Determine the api connections
        $connections = $this->app['config']['api.connections'];

        // If the connection isn't configured, throw an exception
        if(is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("API Connection [{$name}] not configured.");
        }

        // Return the configuration
        return $config;
    }

    /**
     * Prepares the database connection instance.
     *
     * @param  \Reedware\LaravelApi\Connection  $connection
     *
     * @return \Reedware\LaravelApi\Connection
     */
    protected function configure(Connection $connection)
    {
        // Set the event dispatcher
        if($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        // Return the connection
        return $connection;
    }

    /**
     * Removes the specified connection from the local cache.
     *
     * @param  string|null  $name
     *
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        unset($this->connections[$name]);
    }

    /**
     * Returns the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->app['config']['api.default'];
    }

    /**
     * Sets the default connection name.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['api.default'] = $name;
    }

    /**
     * Returns all of the support drivers.
     *
     * @return array
     */
    public function supportedDrivers()
    {
        return ['guzzle'];
    }

    /**
     * Returns the default driver.
     *
     * @return array
     */
    public function getDefaultDriver()
    {
        return 'guzzle';
    }

    /**
     * Registers the specified extension connection resolver.
     *
     * @param  string    $name
     * @param  callable  $resolver
     *
     * @return void
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Returns all of the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically passes methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
