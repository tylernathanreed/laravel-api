<?php

namespace Reedware\LaravelApi\Events;

class RequestExecuted
{
    /**
     * The url endpoint of the request.
     *
     * @var string
     */
    public $url;

    /**
     * The options of the request.
     *
     * @var array
     */
    public $options;

    /**
     * The method of the request.
     *
     * @var string
     */
    public $method;

    /**
     * The number of milliseconds it took to execute the request.
     *
     * @var float
     */
    public $time;

    /**
     * The api connection instance.
     *
     * @var \Reedware\LaravelApi\Connection
     */
    public $connection;

    /**
     * The api connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * Create a new event instance.
     *
     * @param  string                           $url
     * @param  array                            $options
     * @param  string                           $method
     * @param  float|null                       $time
     * @param  \Reedware\LaravelApi\Connection  $connection
     *
     * @return $this
     */
    public function __construct($url, $options, $method, $time, $connection)
    {
        $this->url = $url;
        $this->time = $time;
        $this->options = $options;
        $this->method = $method;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
