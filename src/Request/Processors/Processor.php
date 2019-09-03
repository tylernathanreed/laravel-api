<?php

namespace Reedware\LaravelApi\Request\Processors;

use Reedware\LaravelApi\Request\Builder;

class Processor
{
    /**
     * Processes the response of the given request.
     *
     * @param  \Reedware\LaravelApi\Request\Builder  $request
     * @param  mixed                                 $response
     *
     * @return mixed
     */
    public function processResponse(Builder $request, $response)
    {
        // Determine the response contents
        $contents = $response->getBody()->getContents();

        // If the request expects json, decode it
        if($request->getOption('expects_json')) {
            return json_decode($contents);
        }

        // Otherwise, just return the contents
        return $contents;
    }
}
