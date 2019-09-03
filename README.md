## Laravel API

This package adds configurable web api connections to your Laravel application.

## Installation

Require this package with composer.

```shell
composer require reedware/laravel-api
```

Laravel 5.5+ uses Package Auto-Discovery, so doesn't require you to manually add the service provider or facade. However, should you still need to reference them, here are their class paths:

```php
\Reedware\LaravelApi\ApiServiceProvider::class // Service Provider
\Reedware\LaravelApi\Facade::class // Facade
```

## Configuration

You can configure your various api endpoints by using the `~/config/api.php` configuration file.

```
'connections' => [

    'api-name' => [
        'host' => env('API_HOST'),
        'username' => env('API_USERNAME'),
        'password' => env('API_PASSWORD'),
        'options' => [
            'json' => true,
            'expects_json' => true
        ]
    ]

]
```

## Usage

You can now connect to your api using `Api::connection('api-name')`, and subsequently create requests for it:

```php
Api::connection('api-name')->request()->url('my/endpoint')->post([
    'my-form-data' => 'with-values'
]);
```