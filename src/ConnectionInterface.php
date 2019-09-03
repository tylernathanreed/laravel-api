<?php

namespace Reedware\LaravelApi;

use Closure;

interface ConnectionInterface
{
    /**
     * Creates and returns a new request against the api connection.
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    public function request();

    /**
     * Sends the specified request to the api.
     *
     * @param  string  $url
     * @param  array   $options
     * @param  string  $method
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function send($url = '/', $options = [], $method = 'GET');

    /**
     * Sends the specified GET request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function get($url = '/', $options = []);

    /**
     * Sends the specified POST request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function post($url = '/', $options = []);

    /**
     * Sends the specified PUT request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function put($url = '/', $options = []);

    /**
     * Sends the specified PATCH request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function patch($url = '/', $options = []);

    /**
     * Sends the specified DELETE request to the api.
     *
     * @param  string  $request
     * @param  array   $bindings
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function delete($url = '/', $options = []);

    /**
     * Executes the given callback in "dry run" mode and returns the request log.
     *
     * @param  \Closure  $callback
     *
     * @return array
     */
    public function pretend(Closure $callback);
}
