<?php

namespace Moinul\LaravelPdfToHtml\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString convert(string $pdfPath)
 */
class PdfToHtml extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pdf-to-html';
    }
} 