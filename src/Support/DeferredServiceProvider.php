<?php

namespace Reedware\LaravelApi\Support;

use Illuminate\Support\ServiceProvider;

// Check if the deferrable interface exists
if(interface_exists('Illuminate\Contracts\Support\DeferrableProvider')) {

    // Create the class using the deferrable interface
    abstract class DeferredServiceProvider extends ServiceProvider implements \Illuminate\Contracts\Support\DeferrableProvider {}

}

// Otherwise, the deferrable interface doesn't exist
else {

    // Create the class using the defer property
    abstract class DeferredServiceProvider extends ServiceProvider {
        protected $defer = true;
    }

}