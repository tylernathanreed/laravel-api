<?php

namespace Reedware\LaravelApi;

class ConnectionResolver implements ConnectionResolverInterface
{
    /**
     * All of the registered connections.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The default connection name.
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new connection resolver instance.
     *
     * @param  array  $connections
     *
     * @return void
     */
    public function __construct(array $connections = [])
    {
        foreach($connections as $name => $connection) {
            $this->addConnection($name, $connection);
        }
    }

    /**
     * Returns the specified api connection instance.
     *
     * @param  string|null  $name
     * @return \Reedware\LaravelApi\ConnectionInterface
     */
    public function connection($name = null)
    {
        if(is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        return $this->connections[$name];
    }

    /**
     * Adds the specified connection to the resolver.
     *
     * @param  string                                    $name
     * @param  \Reedware\LaravelApi\ConnectionInterface  $connection
     *
     * @return void
     */
    public function addConnection($name, ConnectionInterface $connection)
    {
        $this->connections[$name] = $connection;
    }

    /**
     * Returns whether or not  the specified connection has been registered.
     *
     * @param  string  $name
     *
     * @return boolean
     */
    public function hasConnection($name)
    {
        return isset($this->connections[$name]);
    }

    /**
     * Returns the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default;
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
        $this->default = $name;
    }
}
