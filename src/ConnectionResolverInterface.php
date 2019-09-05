<?php

namespace Reedware\LaravelApi;

interface ConnectionResolverInterface
{
    /**
     * Returns the specified api connection instance.
     *
     * @param  string|null  $name
     *
     * @return \Reedware\LaravelApi\ConnectionInterface
     */
    public function connection($name = null);

    /**
     * Returns the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection();

    /**
     * Sets the default connection name.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultConnection($name);
}
