<?php

namespace Reedware\LaravelApi\Connectors;

use GuzzleHttp\Client;

class Connector
{
    /**
     * The default client connection options.
     *
     * @var array
     */
    protected $options = [
        'verify' => false
    ];

    /**
     * Establishs the specified api connection.
     *
     * @param  array  $config
     *
     * @return \GuzzleHttp\Client
     *
     * @throws \GuzzleHttp\Exception\ConnectException
     */
    public function connect(array $config)
    {
        // Determine the host
        $host = $this->getHost($config);

        // Determine the connection options
        $options = $this->getOptions($config);

        // Create the connection
        $connection = $this->createConnection($host, $config, $options);

        // Tap the connection to make sure it's working
        $connection->request('GET');

        // Return the connection
        return $connection;
    }

    /**
     * Returns the host from the specified configuration.
     *
     * @param  array   $config
     *
     * @return string
     */
    protected function getHost(array $config)
    {
        return $config['host'];
    }

    /**
     * Create a new client connection.
     *
     * @param  string  $host
     * @param  array   $config
     * @param  array   $options
     *
     * @return \GuzzleHttp\Client
     */
    public function createConnection($host, array $config, array $options)
    {
        [$username, $password] = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        return $this->createClientConnection(
            $host, $username, $password, $options
        );
    }

    /**
     * Creates and returns a new client connection instance.
     *
     * @param  string  $host
     * @param  string  $username
     * @param  string  $password
     * @param  array   $options
     *
     * @return mixed
     */
    protected function createClientConnection($host, $username, $password, $options)
    {
        return new Client(array_merge($options, [
            'base_uri' => $host,
            'auth' => [$username, $password]
        ]));
    }

    /**
     * Returns the client options based on the configuration.
     *
     * @param  array  $config
     *
     * @return array
     */
    public function getOptions(array $config)
    {
        $options = $config['options'] ?? [];

        return array_diff_key($this->options, $options) + $options;
    }

    /**
     * Returns the default client connection options.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->options;
    }

    /**
     * Sets the default PDO connection options.
     *
     * @param  array  $options
     *
     * @return void
     */
    public function setDefaultOptions(array $options)
    {
        $this->options = $options;
    }
}
