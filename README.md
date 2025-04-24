# Laravel PDF to HTML Converter

A Laravel package that converts PDF files to HTML format with ease, supporting image extraction and CDN-hosted PDFs.

## Features

- Convert PDF files to HTML format
- Extract and handle images from PDF files
- Support for CDN-hosted PDF files
- Easy integration with Laravel applications
- Configurable output options
- Support for Laravel 7.x, 8.x, 9.x, and 10.x

## Installation

You can install the package via composer:

```bash
composer require moinul/laravel-pdf-to-html
```

## Requirements

- PHP ^7.3|^8.0
- Laravel ^7.0|^8.0|^9.0|^10.0
- setasign/fpdf ^1.8

## Usage

```php
use Moinul\LaravelPdfToHtml\PdfToHtmlConverter;

// Convert local PDF file to HTML
$converter = new PdfToHtmlConverter();
$html = $converter->convert('path/to/your/file.pdf');

// Convert PDF from CDN/URL
$html = $converter->convertFromUrl('https://example.com/document.pdf');

// Convert with image extraction
$html = $converter->convert('path/to/your/file.pdf', [
    'extract_images' => true,
    'image_quality' => 80
]);
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Moinul\LaravelPdfToHtml\PdfToHtmlServiceProvider"
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 