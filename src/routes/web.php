<?php

use Illuminate\Support\Facades\Route;
use Moinul\LaravelPdfToHtml\Http\Controllers\PdfToHtmlController;

Route::prefix('pdf-to-html')->group(function () {
    Route::post('convert', [PdfToHtmlController::class, 'convert'])
        ->name('pdf-to-html.convert');
        
    Route::post('convert-url', [PdfToHtmlController::class, 'convertFromUrl'])
        ->name('pdf-to-html.convert-url');
}); 