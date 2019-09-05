<?php

namespace Reedware\LaravelApi\Request;

use InvalidArgumentException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Traits\Macroable;
use Reedware\LaravelApi\ConnectionInterface;
use Reedware\LaravelApi\Request\Processors\Processor;

class Builder
{
    use Macroable;

    /**
     * The api connection instance.
     *
     * @var \Reedware\LaravelApi\ConnectionInterface
     */
    public $connection;

    /**
     * The database query post processor instance.
     *
     * @var \Reedware\LaravelApi\Request\Processors\Processor
     */
    public $processor;

    /**
     * The current url endpoint.
     *
     * @var string
     */
    public $endpoint = '/';

    /**
     * The path appended to the url endpoint.
     *
     * @var string
     */
    public $path;

    /**
     * The current options.
     *
     * @var array
     */
    public $options = [];

    /**
     * The current method.
     *
     * @var string
     */
    public $method = 'GET';

    /**
     * All of the supported request options.
     *
     * @var array
     */
    public $supportedOptions = [
        'expects_json',
        RequestOptions::ALLOW_REDIRECTS,
        RequestOptions::AUTH,
        RequestOptions::BODY,
        RequestOptions::CERT,
        RequestOptions::CONNECT_TIMEOUT,
        RequestOptions::COOKIES,
        RequestOptions::DEBUG,
        RequestOptions::DECODE_CONTENT,
        RequestOptions::DELAY,
        RequestOptions::EXPECT,
        RequestOptions::FORCE_IP_RESOLVE,
        RequestOptions::FORM_PARAMS,
        RequestOptions::HEADERS,
        RequestOptions::HTTP_ERRORS,
        RequestOptions::JSON,
        RequestOptions::MULTIPART,
        RequestOptions::ON_HEADERS,
        RequestOptions::ON_STATS,
        RequestOptions::PROGRESS,
        RequestOptions::PROXY,
        RequestOptions::QUERY,
        RequestOptions::READ_TIMEOUT,
        RequestOptions::SINK,
        RequestOptions::SSL_KEY,
        RequestOptions::STREAM,
        RequestOptions::SYNCHRONOUS,
        RequestOptions::TIMEOUT,
        RequestOptions::VERIFY,
        RequestOptions::VERSION
    ];

    /**
     * Create a new query builder instance.
     *
     * @param  \Reedware\LaravelApi\ConnectionInterface  $connection
     * @param  \Reedware\LaravelApi\Request\Processors\Processor|null  $processor
     * @return void
     */
    public function __construct(ConnectionInterface $connection, Processor $processor = null)
    {
        $this->connection = $connection;
        $this->processor = $processor ?: $connection->getPostProcessor();
    }

    /**
     * Sets the url endpoint of this request.
     *
     * @param  string  $endpoint
     *
     * @return $this
     */
    public function endpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Returns the url endpoint of this request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Sets the path appended to the url for this request.
     *
     * @param  string  $path
     *
     * @return $this
     */
    public function path($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Returns the path appended to the url for this request.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the url endpoint of this request and clears the path.
     *
     * @param  string  $url
     *
     * @return $this
     */
    public function url($url)
    {
        return $this->endpoint($url)->path(null);
    }

    /**
     * Returns the base url endpoint with the appended path for this request.
     *
     * @return string
     */
    public function getUrl()
    {
        return rtrim($this->endpoint, '/') . '/' . ltrim($this->path, '/');
    }

    /**
     * Sets the http verb used for this request.
     *
     * @param  string  $method
     *
     * @return $this
     */
    public function method($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Returns the http verb used for this request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Executes the request using the GET method.
     *
     * @param  array|null  $data
     *
     * @return mixed
     */
    public function get($data = null)
    {
        return $this->execute('GET', $data);
    }

    /**
     * Executes the request using the POST method.
     *
     * @param  array|null  $data
     *
     * @return mixed
     */
    public function post($data = null)
    {
        return $this->execute('POST', $data);
    }

    /**
     * Executes the request using the PUT method.
     *
     * @param  array|null  $data
     *
     * @return mixed
     */
    public function put($data = null)
    {
        return $this->execute('PUT', $data);
    }

    /**
     * Executes the request using the PATCH method.
     *
     * @param  array|null  $data
     *
     * @return mixed
     */
    public function patch($data = null)
    {
        return $this->execute('PATCH', $data);
    }

    /**
     * Executes the request using the DELETE method.
     *
     * @param  array|null  $data
     *
     * @return mixed
     */
    public function delete($data = null)
    {
        return $this->execute('DELETE', $data);
    }

    /**
     * Executes the request within the given form data.
     *
     * @param  string      $method
     * @param  array|null  $data
     *
     * @return mixed
     */
    public function execute($method = 'GET', $data = null)
    {
        // Set the request method
        $this->method($method);

        // Set the form parameters
        if(!is_null($data)) {
            $this->setOption(RequestOptions::FORM_PARAMS, $data);
        }

        // Return the response
        return $this->run();
    }

    /**
     * Runs this request against the api connection, returning the processed response.
     *
     * @return mixed
     */
    public function run()
    {
        return $this->processor->processResponse($this, $this->send());
    }

    /**
     * Runs this request against the api connection, returning the raw response.
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function send()
    {
        return $this->connection->send(
            $this->getUrl(), $this->getOptions(), $this->getMethod()
        );
    }

    /**
     * Creates and returns a new instance of the request builder.
     *
     * @return static
     */
    public function newRequest()
    {
        return new static($this->connection, $this->processor);
    }

    /**
     * Returns the specified option.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Returns the array of options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the specified option on the request builder.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setOption($key, $value)
    {
        // Make sure the option is supported
        if(!$this->isSupportedOption($key)) {
            throw new InvalidArgumentException("Invalid option type: [{$key}].");
        }

        // Set the option
        $this->options[$key] = $value;

        // Allow chaining
        return $this;
    }

    /**
     * Sets the options on the request builder.
     *
     * @param  array  $options
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setOptions($options)
    {
        // Reset the options
        $this->options = [];

        // Set each option
        foreach($options as $key => $value) {
            $this->setOption($key, $value);
        }

        // Allow chaining
        return $this;
    }

    /**
     * Adds the specified option to the request.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function addOption($key, $value)
    {
        // Make sure the option is supported
        if(!$this->isSupportedOption($key)) {
            throw new InvalidArgumentException("Invalid option type: [{$key}].");
        }

        // If the value is an array, merge it into the existing option
        if(is_array($value)) {
            $this->options[$key] = array_merge($this->options[$key] ?? [], $value);
        }

        // Otherwise, append to the options
        else {
            $this->options[$key][] = $value;
        }

        // Allow chaining
        return $this;
    }

    /**
     * Removes the specified option from the request.
     *
     * @param  string  $key
     * @param  mixed   $criteria
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function removeOption($key, $criteria = null)
    {
        // Make sure the option is supported
        if(!$this->isSupportedOption($key)) {
            throw new InvalidArgumentException("Invalid option type: [{$key}].");
        }

        // If the criteria is null, remove the option entirely
        if(is_null($criteria)) {
            unset($this->options[$key]);
        }

        // If the value is a string, remove it from the existin option
        else if(is_string($criteria)) {
            unset($this->options[$key][$criteria]);
        }

        // If the value is an array, perform a key diff
        else if(is_array($criteria)) {
            $this->options[$key] = array_except($this->options[$key], $criteria);
        }

        // Unknown criteria
        throw new InvalidArgumentException("Invalid removal criteria for option type: [{$key}].");

        // Allow chaining
        return $this;
    }

    /**
     * Sets the redirect behavior of this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#allow-redirects
     *
     * allow_redirects: (bool|array) Controls redirect behavior. Pass false
     * to disable redirects, pass true to enable redirects, pass an
     * associative to provide custom redirect settings. Defaults to "false".
     * This option only works if your handler has the RedirectMiddleware. When
     * passing an associative array, you can provide the following key value
     * pairs:
     *
     * - max: (int, default=5) maximum number of allowed redirects.
     * - strict: (bool, default=false) Set to true to use strict redirects
     *   meaning redirect POST requests with POST requests vs. doing what most
     *   browsers do which is redirect POST requests with GET requests
     * - referer: (bool, default=true) Set to false to disable the Referer
     *   header.
     * - protocols: (array, default=['http', 'https']) Allowed redirect
     *   protocols.
     * - on_redirect: (callable) PHP callable that is invoked when a redirect
     *   is encountered. The callable is invoked with the request, the redirect
     *   response that was received, and the effective URI. Any return value
     *   from the on_redirect function is ignored.
     *
     * @param  boolean|array
     *
     * @return $this
     */
    public function allowRedirects($value = true)
    {
        return $this->setOption(RequestOptions::ALLOW_REDIRECTS, $value);
    }

    /**
     * Sets the authentication for this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#auth
     *
     * auth: (array) Pass an array of HTTP authentication parameters to use
     * with the request. The array must contain the username in index [0],
     * the password in index [1], and you can optionally provide a built-in
     * authentication type in index [2]. Pass null to disable authentication
     * for a request.
     *
     * @param  array|null
     *
     * @return $this
     */
    public function authenticate($value)
    {
        return $this->setOption(RequestOptions::AUTH, $value);
    }

    /**
     * Sets the body of this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#body
     *
     * body: (resource|string|null|int|float|StreamInterface|callable|\Iterator)
     * Body to send in the request.
     *
     * @param  mixed
     *
     * @return $this
     */
    public function body($value)
    {
        return $this->setOption(RequestOptions::BODY, $value);
    }

    /**
     * Sets the path to the client side certificate.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#cert
     *
     * cert: (string|array) Set to a string to specify the path to a file
     * containing a PEM formatted SSL client side certificate. If a password
     * is required, then set cert to an array containing the path to the PEM
     * file in the first array element followed by the certificate password
     * in the second array element.
     *
     * @param  string|array  $value
     *
     * @return $this
     */
    public function cert($value)
    {
        return $this->setOption(RequestOptions::CERT, $value);
    }

    /**
     * Sets whether or not cookies are used in this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#cookies
     *
     * cookies: (bool|GuzzleHttp\Cookie\CookieJarInterface, default=false)
     * Specifies whether or not cookies are used in a request or what cookie
     * jar to use or what cookies to send. This option only works if your
     * handler has the `cookie` middleware. Valid values are `false` and
     * an instance of {@see GuzzleHttp\Cookie\CookieJarInterface}.
     *
     * @param  \GuzzleHttp\Cookie\CookieJarInterface|boolean  $value
     *
     * @return $this
     */
    public function cookies($value = true)
    {
        return $this->setOption(RequestOptions::COOKIES, $value);
    }

    /**
     * Sets the number of seconds to wait while trying to connect to the api.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#connect-timeout
     *
     * connect_timeout: (float, default=0) Float describing the number of
     * seconds to wait while trying to connect to a server. Use 0 to wait
     * indefinitely (the default behavior).
     *
     * @param  float  $value
     *
     * @return $this
     */
    public function connectTimeout($value)
    {
        return $this->setOption(RequestOptions::CONNECT_TIMEOUT, $value);
    }

    /**
     * Enables the debug output handler used to send a request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#debug
     *
     * debug: (bool|resource) Set to true or set to a PHP stream returned by
     * fopen()  enable debug output with the HTTP handler used to send a
     * request.
     *
     * @param  boolean|resource  $value
     *
     * @return $this
     */
    public function debug($value = true)
    {
        return $this->setOption(RequestOptions::DEBUG, $value);
    }

    /**
     * Sets whether or not encoded responses are automatically decoded.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#decode-content
     *
     * decode_content: (bool, default=true) Specify whether or not
     * Content-Encoding responses (gzip, deflate, etc.) are automatically
     * decoded.
     *
     * @param  boolean|string  $value
     *
     * @return $this
     */
    public function decodeContent($value = true)
    {
        return $this->setOption(RequestOptions::DECODE_CONTENT, $value);
    }

    /**
     * Sets the number of milliseconds to delay before sending the request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#delay
     *
     * delay: (int) The amount of time to delay before sending in milliseconds.
     *
     * @param  integer|float|null  $value
     *
     * @return $this
     */
    public function delay($value)
    {
        return $this->setOption(RequestOptions::DELAY, $value);
    }

    /**
     * Sets the behavior of the "Expect: 100-Continue" header.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#expect
     *
     * expect: (bool|integer) Controls the behavior of the
     * "Expect: 100-Continue" header.
     *
     * Set to `true` to enable the "Expect: 100-Continue" header for all
     * requests that sends a body. Set to `false` to disable the
     * "Expect: 100-Continue" header for all requests. Set to a number so that
     * the size of the payload must be greater than the number in order to send
     * the Expect header. Setting to a number will send the Expect header for
     * all requests in which the size of the payload cannot be determined or
     * where the body is not rewindable.
     *
     * By default, Guzzle will add the "Expect: 100-Continue" header when the
     * size of the body of a request is greater than 1 MB and a request is
     * using HTTP/1.1.
     *
     * @param  boolean|integer  $value
     *
     * @return $this
     */
    public function expect($value)
    {
        return $this->setOption(RequestOptions::EXCEPT, $value);
    }

    /**
     * Sets the ip protocol for the HTTP handlers.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#force-ip-resolve
     *
     * force_ip_resolve: (bool) Force client to use only ipv4 or ipv6 protocol
     *
     * @param  string|boolean|null  $value
     *
     * @return $this
     */
    public function forceIpResolve($value = true)
    {
        return $this->setOption(RequestOptions::FORCE_IP_RESOLVE, $value);
    }

    /**
     * Sets the parameters sent for form encoded requests.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#form-params
     *
     * form_params: (array) Associative array of form field names to values
     * where each value is a string or array of strings. Sets the Content-Type
     * header to application/x-www-form-urlencoded when no Content-Type header
     * is already present.
     *
     * @param  array  $value
     *
     * @return $this
     */
    public function parameters($value)
    {
        return $this->setOption(RequestOptions::FORM_PARAMS, $value);
    }

    /**
     * Adds the specified parameter to this request.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return $this
     */
    public function addParameter($key, $value)
    {
        return $this->addOption(RequestOptions::FORM_PARAMS, [$key => $value]);
    }

    /**
     * Removes the specified parameter from this request.
     *
     * @param  string  $key
     *
     * @return $this
     */
    public function removeParameter($key)
    {
        return $this->removeOption(RequestOptions::FORM_PARAMS, $key);
    }

    /**
     * Sets the headers to the request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#headers
     *
     * headers: (array) Associative array of HTTP headers. Each value MUST be
     * a string or array of strings.
     *
     * @param  array  $value
     *
     * @return $this
     */
    public function headers($value)
    {
        return $this->setOption(RequestOptions::HEADERS, $value);
    }

    /**
     * Adds the specified header to this request.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return $this
     */
    public function addHeader($key, $value)
    {
        return $this->addOption(RequestOptions::HEADERS, [$key => $value]);
    }

    /**
     * Removes the specified header from this request.
     *
     * @param  string  $key
     *
     * @return $this
     */
    public function removeHeader($key)
    {
        return $this->removeOption(RequestOptions::HEADERS, $key);
    }

    /**
     * Sets whether or not exceptions are thrown on HTTP protocol errors (i.e. 4xx and 5xx responses).
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#http-errors
     *
     * http_errors: (bool, default=true) Set to false to disable exceptions
     * when a non- successful HTTP response is received. By default,
     * exceptions will be thrown for 4xx and 5xx responses. This option only
     * works if your handler has the `httpErrors` middleware.
     *
     * @param  boolean  $value
     *
     * @return $this
     */
    public function throwHttpErrors($value = true)
    {
        return $this->setOption(RequestOptions::HTTP_ERRORS, $value);
    }

    /**
     * Enables exception throwing on HTTP protocol errors.
     *
     * @return $this
     */
    public function enableHttpErrors()
    {
        return $this->throwHttpErrors(true);
    }

    /**
     * Disables exception throwing on HTTP protocol errors.
     *
     * @return $this
     */
    public function disableHttpErrors()
    {
        return $this->throwHttpErrors(false);
    }

    /**
     * Sets the json encoded data for this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#json
     *
     * json: (mixed) Adds JSON data to a request. The provided value is JSON
     * encoded and a Content-Type header of application/json will be added to
     * the request if no Content-Type header is already present.
     *
     * @param  mixed  $value
     *
     * @return $this
     */
    public function json($value)
    {
        return $this->setOption(RequestOptions::JSON, $value);
    }

    /**
     * Sets whether or not this request expects the response to be json.
     *
     * @param  boolean  $value
     *
     * @return $this
     */
    public function expectsJson($value = true)
    {
        $this->setOption('expects_json', $value)->acceptsJson();
    }

    /**
     * Sets the headers to accept a json response.
     *
     * @return $this
     */
    public function acceptsJson()
    {
        return $this->header('Accept', 'application/json');
    }

    /**
     * Sets the body of this request to a multipart/form-data form.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#multipart
     *
     * multipart: (array) Array of associative arrays, each containing a
     * required "name" key mapping to the form field, name, a required
     * "contents" key mapping to a StreamInterface|resource|string, an
     * optional "headers" associative array of custom headers, and an
     * optional "filename" key mapping to a string to send as the filename in
     * the part. If no "filename" key is present, then no "filename" attribute
     * will be added to the part.
     *
     * @param  array  $value
     *
     * @return $this
     */
    public function multipart($value)
    {
        return $this->setOption(RequestOptions::MULTIPART, $value);
    }

    /**
     * Sets the callback that is invoked when the HTTP headers of the response have been received.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#on-headers
     *
     * on_headers: (callable) A callable that is invoked when the HTTP headers
     * of the response have been received but the body has not yet begun to
     * download.
     *
     * @param  callable  $value
     *
     * @return $this
     */
    public function onHeaders(callable $value)
    {
        return $this->setOption(RequestOptions::ON_HEADERS, $value);
    }

    /**
     * Sets the callback that is invoked when the handler has finished sending the request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#on-stats
     *
     * on_stats: (callable) allows you to get access to transfer statistics of
     * a request and access the lower level transfer details of the handler
     * associated with your client. ``on_stats`` is a callable that is invoked
     * when a handler has finished sending a request. The callback is invoked
     * with transfer statistics about the request, the response received, or
     * the error encountered. Included in the data is the total amount of time
     * taken to send the request.
     *
     * @param  callable  $value
     *
     * @return $this
     */
    public function onStats(callable $value)
    {
        return $this->setOption(RequestOptions::ON_STATS, $value);
    }

    /**
     * Sets the callback that is invoked when transfer progress has been made.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#progress
     *
     * progress: (callable) Defines a function to invoke when transfer
     * progress is made. The function accepts the following positional
     * arguments: the total number of bytes expected to be downloaded, the
     * number of bytes downloaded so far, the number of bytes expected to be
     * uploaded, the number of bytes uploaded so far.
     *
     * @param  callable  $value
     *
     * @return $this
     */
    public function onProgress(callable $value)
    {
        return $this->setOption(RequestOptions::PROGRESS, $value);
    }

    /**
     * Sets the proxy for this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#proxy
     *
     * proxy: (string|array) Pass a string to specify an HTTP proxy, or an
     * array to specify different proxies for different protocols (where the
     * key is the protocol and the value is a proxy string).
     *
     * @param  string|array  $value
     *
     * @return $this
     */
    public function proxy($value)
    {
        return $this->setOption(RequestOptions::PROXY, $value);
    }

    /**
     * Sets the query string to add to the request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#query
     *
     * query: (array|string) Associative array of query string values to add
     * to the request. This option uses PHP's http_build_query() to create
     * the string representation. Pass a string value if you need more
     * control than what this method provides
     *
     * @param  string|array  $value
     *
     * @return $this
     */
    public function query($value)
    {
        return $this->setOption(RequestOptions::QUERY, $value);
    }

    /**
     * Sets the read timeout of this request when using a streamed body.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#read-timeout
     *
     * read_timeout: (float, default=default_socket_timeout ini setting) Float describing
     * the body read timeout, for stream requests.
     *
     * @param  float  $value
     *
     * @return $this
     */
    public function readTimeout($value)
    {
        return $this->setOption(RequestOptions::READ_TIMEOUT, $value);
    }

    /**
     * Sets where the body of the response will be saved.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#sink
     *
     * sink: (resource|string|StreamInterface) Where the data of the
     * response is written to. Defaults to a PHP temp stream. Providing a
     * string will write data to a file by the given name.
     *
     * @param  mixed  $value
     *
     * @return $this
     */
    public function sink($value)
    {
        return $this->setOption(RequestOptions::SINK, $value);
    }

    /**
     * Sets the path to the file containing the private SSL key in PEM format.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#ssl-key
     *
     * ssl_key: (array|string) Specify the path to a file containing a private
     * SSL key in PEM format. If a password is required, then set to an array
     * containing the path to the SSL key in the first array element followed
     * by the password required for the certificate in the second element.
     *
     * @param  string|array  $value
     *
     * @return $this
     */
    public function sslKey($value)
    {
        return $this->setOption(RequestOptions::SSL_KEY, $value);
    }

    /**
     * Sets whether or not the response is a stream instead of downloaded all up-front.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#stream
     *
     * stream: Set to true to attempt to stream a response rather than
     * download it all up-front.
     *
     * @param  boolean  $value
     *
     * @return $this
     */
    public function stream($value = true)
    {
        return $this->setOption(RequestOptions::STREAM, $value);
    }

    /**
     * Sets whether or not this request will wait on the response.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#synchronous
     *
     * synchronous: (bool) Set to true to inform HTTP handlers that you intend
     * on waiting on the response. This can be useful for optimizations. Note
     * that a promise is still returned if you are using one of the async
     * client methods.
     *
     * @param  boolean  $value
     *
     * @return $this
     */
    public function synchronous($value = true)
    {
        return $this->setOption(RequestOptions::SYNCHRONOUS, $value);
    }

    /**
     * Inverse of {@see $this->synchronous()}.
     *
     * @param  boolean  $value
     *
     * @return $this
     */
    public function asynchronous($value = true)
    {
        return $this->synchronous(!$value);
    }

    /**
     * Sets whether or not the host identity should be verified using their SSL certificate.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#verify
     *
     * verify: (bool|string, default=true) Describes the SSL certificate
     * verification behavior of a request. Set to true to enable SSL
     * certificate verification using the system CA bundle when available
     * (the default). Set to false to disable certificate verification (this
     * is insecure!). Set to a string to provide the path to a CA bundle on
     * disk to enable verification using a custom certificate.
     *
     * @param  boolean|string  $value
     *
     * @return $this
     */
    public function verify($value = true)
    {
        return $this->setOption(RequestOptions::VERIFY, $value);
    }

    /**
     * Sets the timeout of the request (in seconds).
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#timeout
     *
     * timeout: (float, default=0) Float describing the timeout of the
     * request in seconds. Use 0 to wait indefinitely (the default behavior).
     *
     * @param  float  $value
     *
     * @return $this
     */
    public function timeout($value)
    {
        return $this->setOption(RequestOptions::TIMEOUT, $value);
    }

    /**
     * Sets the protocol version to use with this request.
     *
     * @link http://docs.guzzlephp.org/en/stable/request-options.html#version
     *
     * version: (float) Specifies the HTTP protocol version to attempt to use.
     *
     * @param  string|float  $value
     *
     * @return $this
     */
    public function version($value)
    {
        return $this->setOption(RequestOptions::VERSION, $value);
    }

    /**
     * Returns the supported options.
     *
     * @return array
     */
    public function getSupportedOptions()
    {
        return $this->supportedOptions;
    }

    /**
     * Returns whether or not the specified option is supported.
     *
     * @param  string  $key
     *
     * @return boolean
     */
    public function isSupportedOption($key)
    {
        return in_array($key, $this->getSupportedOptions());
    }

    /**
     * Get the database connection instance.
     *
     * @return \Reedware\LaravelApi\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the database query processor instance.
     *
     * @return \Reedware\LaravelApi\Request\Processors\Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Clones this request without the given properties.
     *
     * @param  array  $properties
     *
     * @return static
     */
    public function cloneWithout(array $properties)
    {
        return tap(clone $this, function($clone) use ($properties) {
            foreach($properties as $property) {
                $clone->{$property} = null;
            }
        });
    }

    /**
     * Clones this request without the given options.
     *
     * @param  array  $except
     *
     * @return static
     */
    public function cloneWithoutOptions(array $except)
    {
        return tap(clone $this, function($clone) use ($except) {
            foreach($except as $key) {
                unset($clone->options[$key]);
            }
        });
    }

    /**
     * Dumps the current request.
     *
     * @return $this
     */
    public function dump()
    {
        dump([
            'method' => $this->method,
            'url' => $this->url,
            'options' => $this->options
        ]);

        return $this;
    }

    /**
     * Die and dumps the current request.
     *
     * @return void
     */
    public function dd()
    {
        dd([
            'method' => $this->method,
            'url' => $this->url,
            'options' => $this->options
        ]);
    }
}
