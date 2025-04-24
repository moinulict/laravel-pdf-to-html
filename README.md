# Laravel PDF to HTML Converter

A Laravel package to convert PDF files to HTML format with support for text extraction and image handling.

## Installation

You can install the package via composer:

```bash
composer require moinul/laravel-pdf-to-html
```

## Requirements

- PHP ^8.0
- Laravel ^8.0|^9.0|^10.0
- setasign/fpdf ^1.8

## Usage

```php
use Moinul\LaravelPdfToHtml\PdfToHtmlConverter;

// Convert PDF file to HTML
$converter = new PdfToHtmlConverter();
$html = $converter->convert('path/to/your/file.pdf');

// Convert PDF from URL
$html = $converter->convertFromUrl('https://example.com/document.pdf');
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