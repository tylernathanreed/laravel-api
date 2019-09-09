<?php

namespace Reedware\LaravelApi;

use Closure;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Events\Dispatcher;
use Reedware\LaravelApi\Events\RequestExecuted;
use Reedware\LaravelApi\Request\Processors\Processor;
use Reedware\LaravelApi\Request\Builder as RequestBuilder;

class Connection implements ConnectionInterface
{
    /**
     * The active api connection.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The api connection configuration options.
     *
     * @var array
     */
    protected $config = [];

    /**
     * The request post processor implementation.
     *
     * @var \Reedware\LaravelApi\Request\Processors\Processor
     */
    protected $postProcessor;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * All of the requests run against the connection.
     *
     * @var array
     */
    protected $requestLog = [];

    /**
     * Indicates whether requests are being logged.
     *
     * @var bool
     */
    protected $loggingRequests = false;

    /**
     * Indicates if the connection is in a "dry run".
     *
     * @var bool
     */
    protected $pretending = false;

    /**
     * The connection resolvers.
     *
     * @var array
     */
    protected static $resolvers = [];

    /**
     * Create a new api connection instance.
     *
     * @param  \GuzzleHttp\Client  $client
     * @param  array               $config
     *
     * @return $this
     */
    public function __construct($client, array $config = [])
    {
        $this->client = $client;
        $this->config = $config;

        $this->useDefaultPostProcessor();
    }

    /**
     * Set the request post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Reedware\LaravelApi\Request\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Creates and returns a new request builder instance.
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    public function request()
    {
        return $this->applyRequestOptions(
            $this->newRequestWithoutOptions()
        );
    }

    /**
     * Applies the request options to the specified request.
     *
     * @param  \Reedware\LaravelApi\Request\Builder  $request
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    public function applyRequestOptions($request)
    {
        // Determine the request options
        $options = $this->config['options'] ?? [];

        // Determine the supported options
        $supported = $request->getSupportedOptions();

        // Only apply the supported options
        $available = Arr::only($options, $supported);

        // Set the request options
        $request->setOptions($available);

        // Return the request
        return $request;
    }

    /**
     * Creates and returns a new request builder instance.
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    public function newRequestWithoutOptions()
    {
        return new RequestBuilder(
            $this, $this->getPostProcessor()
        );
    }

    /**
     * Sends the specified request to the api.
     *
     * @param  string  $url
     * @param  array   $options
     * @param  string  $method
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function send($url = '/', $options = [], $method = 'GET')
    {
        return $this->run($url, $options, $method, function($url, $options, $method) {

            // If we're pretending, return a successful response
            if($this->pretending()) {
                return new Response;
            }

            // Send the request
            return $this->getClient()->request($method, $url, $options);

        });
    }

    /**
     * Sends the specified GET request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function get($url = '/', $options = [])
    {
        return $this->send($url, $options, 'GET');
    }

    /**
     * Sends the specified POST request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function post($url = '/', $options = [])
    {
        return $this->send($url, $options, 'POST');
    }

    /**
     * Sends the specified PUT request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function put($url = '/', $options = [])
    {
        return $this->send($url, $options, 'PUT');
    }

    /**
     * Sends the specified PATCH request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function patch($url = '/', $options = [])
    {
        return $this->send($url, $options, 'PATCH');
    }

    /**
     * Sends the specified DELETE request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function delete($url = '/', $options = [])
    {
        return $this->send($url, $options, 'DELETE');
    }

    /**
     * Executes the given callback in "dry run" mode and returns the request log.
     *
     * @param  \Closure  $callback
     *
     * @return array
     */
    public function pretend(Closure $callback)
    {
        return $this->withFreshRequestLog(function() use ($callback) {

            $this->pretending = true;

            $callback($this);

            $this->pretending = false;

            return $this->requestLog;

        });
    }

    /**
     * Executes the given callback with a fresh request log.
     *
     * @param  \Closure  $callback
     *
     * @return array
     */
    protected function withFreshRequestLog($callback)
    {
        // First we will back up the value of the logging requests property and then
        // we'll be ready to run callbacks. This request log will also get cleared
        // so we will have a new log of all the requests that are executed now.

        // Remember whether or not we were previously logging requests
        $loggingRequests = $this->loggingRequests;

        // Enable request logging
        $this->enableRequestLog();

        // Flush the request log
        $this->requestLog = [];

        // Now we'll execute this callback and capture the result. Once it has been
        // executed we will restore the value of request logging and give back the
        // value of the callback so the original callers can have the results.

        // Execute the callback
        $result = $callback();

        // Restore the logging requests flag
        $this->loggingRequests = $loggingRequests;

        // Return the result
        return $result;
    }

    /**
     * Runs the specified request and logs its execution context.
     *
     * @param  string    $url
     * @param  array     $options
     * @param  string    $method
     * @param  \Closure  $callback
     *
     * @return mixed
     *
     * @throws \Reedware\LaravelApi\RequestException
     */
    protected function run($url, $options, $method, Closure $callback)
    {
        // Initialize the benchmark
        $start = microtime(true);

        // Try to run the request
        try {

            // Invoke the request callback
            $result = $this->runRequestCallback($url, $options, $method, $callback);

        }

        // Catch all request exceptions
        catch(RequestException $e) {

            // Handle the exception
            $result = $this->handleRequestException(
                $e, $url, $options, $method, $callback
            );

        }

        // Once we have run the request we will calculate the time that it took to run and
        // then log the request, options, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.

        // Log the request
        $this->logRequest(
            $url, $options, $method, $this->getElapsedTime($start)
        );

        // Return the result
        return $result;
    }

    /**
     * Runs the specified request.
     *
     * @param  string    $url
     * @param  array     $options
     * @param  string    $method
     * @param  \Closure  $callback
     *
     * @return mixed
     *
     * @throws \Reedware\LaravelApi\RequestException
     */
    protected function runRequestCallback($url, $options, $method, Closure $callback)
    {
        // To execute the request, we'll simply call the callback, which will actually
        // run the request against the connection. Then we can calculate the time it
        // took to execute and log the api request, options and time in our memory.

        // Invoke the callback
        try {
            $result = $callback($url, $options, $method);
        }

        // If an error occurs when attempting to run a request, we'll format the error
        // message to include the request details, which will make this exception a
        // lot more helpful to the developer instead of just the guzzle errors.

        // Catch all exceptions
        catch(Exception $e) {

            // Convert the exception to a request exception
            throw new RequestException(
                $url, $options, $method, $e
            );

        }

        // Return the result
        return $result;
    }

    /**
     * Log a request in the connection's request log.
     *
     * @param  string      $url
     * @param  array       $options
     * @param  string      $method
     * @param  float|null  $time
     *
     * @return void
     */
    public function logRequest($url, $options, $method, $time = null)
    {
        // Fire the request executed event
        $this->event(new RequestExecuted($url, $options, $method, $time, $this));

        // Add the request to the request log
        if($this->loggingRequests) {
            $this->requestLog[] = compact('url', 'options', 'method', 'time');
        }
    }

    /**
     * Returns the elapsed time since a given starting point.
     *
     * @param  integer  $start
     *
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Handles the specified request exception.
     *
     * @param  \Reedware\LaravelApi\RequestException  $e
     * @param  string                                 $url
     * @param  array                                  $options
     * @param  string                                 $method
     * @param  \Closure                               $callback
     *
     * @return mixed
     *
     * @throws \Reedware\LaravelApi\RequestException
     */
    protected function handleRequestException(RequestException $e, $url, $options, $method, Closure $callback)
    {
        throw $e;
    }

    /**
     * Registers the specified api request listener with the connection.
     *
     * @param  \Closure  $callback
     *
     * @return void
     */
    public function listen(Closure $callback)
    {
        if(isset($this->events)) {
            $this->events->listen(RequestExecuted::class, $callback);
        }
    }

    /**
     * Fires the specified event if possible.
     *
     * @param  mixed  $event
     *
     * @return void
     */
    protected function event($event)
    {
        if(isset($this->events)) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Returns the current client connection.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        // If the client is a closure, resolve it
        if($this->client instanceof Closure) {
            return $this->client = call_user_func($this->client);
        }

        // Return the client
        return $this->client;
    }

    /**
     * Sets the client connection.
     *
     * @param  \GuzzleHttp\Client|\Closure|null  $client
     *
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Returns the api connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Returns the specified option from the configuration options.
     *
     * @param  string|null  $option
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Sets the specified option in the configuration options.
     * 
     * @param  string|array  $key
     * @param  string|null   $value
     * 
     * @return $this
     */
    public function setConfig($key, $value = null)
    {
        Arr::set($this->config, $key, $value);

        return $this;
    }

    /**
     * Adds the specified option in the configuration options.
     * 
     * @param  string  $key
     * @param  mixed   $value
     * 
     * @return $this
     */
    public function addDefaultOption($key, $value)
    {
        // Determine the current options
        $options = $this->getConfig('options');

        // Add the specified option
        $options[$key] = $value;

        // Set the options back
        $this->setConfig('options', $options);

        // Allow chaining
        return $this;
    }

    /**
     * Returns the api driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->getConfig('driver');
    }

    /**
     * Get the request post processor used by the connection.
     *
     * @return \Reedware\LaravelApi\Request\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the request post processor used by the connection.
     *
     * @param  \Reedware\LaravelApi\Request\Processors\Processor  $processor
     *
     * @return $this
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;

        return $this;
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     *
     * @return $this
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;

        return $this;
    }

    /**
     * Unset the event dispatcher for this connection.
     *
     * @return void
     */
    public function unsetEventDispatcher()
    {
        $this->events = null;
    }

    /**
     * Returns whether or not the connection is in a "dry run".
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }

    /**
     * Returns the connection request log.
     *
     * @return array
     */
    public function getRequestLog()
    {
        return $this->requestLog;
    }

    /**
     * Clear the request log.
     *
     * @return void
     */
    public function flushRequestLog()
    {
        $this->requestLog = [];
    }

    /**
     * Enables the request log on the connection.
     *
     * @return void
     */
    public function enableRequestLog()
    {
        $this->loggingRequests = true;
    }

    /**
     * Disables the request log on the connection.
     *
     * @return void
     */
    public function disableRequestLog()
    {
        $this->loggingRequests = false;
    }

    /**
     * Returns whether or not we're logging requests.
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingRequests;
    }

    /**
     * Registers the specified connection resolver.
     *
     * @param  string    $driver
     * @param  \Closure  $callback
     *
     * @return void
     */
    public static function resolverFor($driver, Closure $callback)
    {
        static::$resolvers[$driver] = $callback;
    }

    /**
     * Returns the connection resolver for the specified driver.
     *
     * @param  string  $driver
     *
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return static::$resolvers[$driver] ?? null;
    }
}