<?php

namespace Reedware\LaravelApi;

use RuntimeException;

class RequestException extends RuntimeException
{
    /**
     * The url for the request.
     *
     * @var string
     */
    protected $url;

    /**
     * The options for the request.
     *
     * @var array
     */
    protected $options;

    /**
     * The method for the request.
     *
     * @var method
     */
    protected $method;

    /**
     * Creates a new request exception instance.
     *
     * @param  string      $url
     * @param  array       $options
     * @param  string      $method
     * @param  \Exception  $previous
     *
     * @return $this
     */
    public function __construct($url, array $options, $method, $previous)
    {
        // Call the parent constructor
        parent::__construct('', 0, $previous);

        // Set the constructor attributes
        $this->url = $url;
        $this->options = $options;
        $this->method = $method;

        // Set the exception attributes
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($url, $options, $method, $previous);
    }

    /**
     * Formats the request error message.
     *
     * @param  string      $url
     * @param  array       $options
     * @param  string      $method
     * @param  \Exception  $previous
     *
     * @return string
     */
    protected function formatMessage($url, $options, $method, $previous)
    {
        return $previous->getMessage() . " (Request: [{$method}] {$url} " . json_encode($options) . ')';
    }

    /**
     * Returns the url for the request.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the options for the request.
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns the method for the request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }
}
