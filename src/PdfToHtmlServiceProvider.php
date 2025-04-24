<?php

namespace Moinul\LaravelPdfToHtml;

use Illuminate\Support\ServiceProvider;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

class PdfToHtmlServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('pdf-to-html', function ($app) {
            return new PdfToHtmlConverter();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/pdf-to-html.php', 'pdf-to-html'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/pdf-to-html.php' => config_path('pdf-to-html.php'),
        ], 'config');

        // Register routes if not in console
        if (!$this->app->runningInConsole()) {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        }
    }
} 