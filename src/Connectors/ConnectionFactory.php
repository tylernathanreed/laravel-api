<?php

namespace Reedware\LaravelApi\Connectors;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use Reedware\LaravelApi\Connection;
use Illuminate\Contracts\Container\Container;

class ConnectionFactory
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Creates a new connection factory instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     *
     * @return $this
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Establishes an api connection based on the configuration.
     *
     * @param  array        $config
     * @param  string|null  $name
     *
     * @return \Reedware\LaravelApi\Connection
     */
    public function make(array $config, $name = null)
    {
        $client = $this->createClientResolver($config);

        return $this->createConnection(
            $config['driver'], $client, $config
        );
    }

    /**
     * Creates and returns a new closure that resolves to the specified host.
     *
     * @param  array  $config
     *
     * @return \Closure
     *
     * @throws \GuzzleHttp\Exception\ConnectException
     */
    protected function createClientResolver(array $config)
    {
        return function () use ($config) {

            // Randomly try each host
            foreach(Arr::shuffle($hosts = $this->parseHosts($config)) as $key => $host) {

                // Configure the current host
                $config['host'] = $host;

                // Try to connect to the host
                try {
                    return $this->createConnector($config)->connect($config);
                } catch (ConnectException $e) {
                    continue;
                }
            }

            // Throw the most recently caught exception
            throw $e;

        };
    }

    /**
     * Parse the hosts configuration item into an array.
     *
     * @param  array  $config
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseHosts(array $config)
    {
        // Make sure the host configuration is an array
        $hosts = Arr::wrap($config['host']);

        // If we don't have any hosts, throw an exception
        if(empty($hosts)) {
            throw new InvalidArgumentException('API hosts array is empty.');
        }

        return $hosts;
    }

    /**
     * Creates and returns a connector instance based on the specified configuration.
     *
     * @param  array  $config
     *
     * @return \Reedware\LaravelApi\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    public function createConnector(array $config)
    {
        // Make sure the driver is specified
        if(!isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        // If the connector is bound to the container, use it
        if($this->container->bound($key = "api.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

        // Create and return the connector
        return new Connector;
    }

    /**
     * Creates and returns the specified connection instance.
     *
     * @param  string              $driver
     * @param  \GuzzleHttp\Client  $connection
     * @param  array               $config
     *
     * @return \Reedware\LaravelApi\Connection
     */
    protected function createConnection($driver, $connection, array $config = [])
    {
        // If a custom resolver exists, use it
        if($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $config);
        }

        // Create and return the connection
        return new Connection($connection, $config);
    }
}
