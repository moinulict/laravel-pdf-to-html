<?php

namespace Moinul\LaravelPdfToHtml;

use Illuminate\Support\ServiceProvider;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

class PdfToHtmlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PdfToHtmlConverter::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Create storage directory if it doesn't exist
        $outputPath = storage_path('app/public/pdf-output');
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        // Create storage symlink if it doesn't exist
        if (!file_exists(public_path('storage'))) {
            $this->app->make('files')->link(
                storage_path('app/public'),
                public_path('storage')
            );
        }
    }
} 