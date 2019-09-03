<?php

namespace Reedware\LaravelApi\Connectors;

interface ConnectorInterface
{
    /**
     * Establishes an api connection.
     *
     * @param  array  $config
     *
     * @return mixed
     */
    public function connect(array $config);
}
