<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

// Create a container instance
$app = new Container;
Container::setInstance($app);

// Bind config to container
$config = new Repository([
    'pdf-to-html' => [
        'storage_path' => 'pdf-images',
        'public_path' => 'storage/pdf-images',
    ]
]);
$app->instance('config', $config);

// Set up Facade
Facade::setFacadeApplication($app);

// Add storage_path helper function
if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return __DIR__ . '/storage' . ($path ? '/' . $path : $path);
    }
}

// Add public_path helper function
if (!function_exists('public_path')) {
    function public_path($path = '') {
        return __DIR__ . '/public' . ($path ? '/' . $path : $path);
    }
}

// Add app helper function
if (!function_exists('app')) {
    function app($abstract = null) {
        $container = Container::getInstance();
        if (is_null($abstract)) {
            return $container;
        }
        return $container->make($abstract);
    }
}

// Add config helper function
if (!function_exists('config')) {
    function config($key = null, $default = null) {
        if (is_null($key)) {
            return app('config');
        }
        return app('config')->get($key, $default);
    }
}

// Create necessary directories
if (!is_dir(__DIR__ . '/storage/app/public')) {
    mkdir(__DIR__ . '/storage/app/public', 0777, true);
}

if (!is_dir(__DIR__ . '/public')) {
    mkdir(__DIR__ . '/public', 0777, true);
}

// Create storage link if it doesn't exist
if (!file_exists(__DIR__ . '/public/storage')) {
    symlink(__DIR__ . '/storage/app/public', __DIR__ . '/public/storage');
} 