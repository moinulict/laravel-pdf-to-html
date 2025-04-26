# Laravel PDF to HTML Converter

A Laravel package that converts PDF files to HTML with preserved formatting and styling using poppler-utils.

## Requirements

- PHP 8.2 or higher
- Laravel 7.x|8.x|9.x|10.x|11.x
- `pdftohtml` command-line tool (poppler-utils)

## Installation

1. Install the package via Composer:
```bash
composer require moinul/laravel-pdf-to-html
```

2. Install the required system dependency:

For Ubuntu/Debian:
```bash
sudo apt-get install poppler-utils
```

For CentOS/RHEL:
```bash
sudo yum install poppler-utils
```

For macOS:
```bash
brew install poppler
```

3. The package will automatically register its service provider in Laravel 5.5 and newer. 

4. Create the storage symlink if you haven't already:
```bash
php artisan storage:link
```

## Usage

### Basic Usage

```php
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

public function convertPdf(Request $request)
{
    $converter = app(PdfToHtmlConverter::class);
    $html = $converter->convert('path/to/your/file.pdf');
    
    return $html; // Returns HtmlString
}
```

### Example Route

Here's a complete example of a route that converts a PDF file to HTML:

```php
use Illuminate\Support\Facades\Route;

Route::get('/pdf-to-html', function () {
    $pdfPath = public_path('example.pdf');
    
    try {
        $converter = app(PdfToHtmlConverter::class);
        $html = $converter->convert($pdfPath);
        
        return view('pdf.html-output', ['html' => $html]);
    } catch (\Exception $e) {
        return "Error converting PDF: " . $e->getMessage();
    }
});
```

### Example View

Create a view file `resources/views/pdf/html-output.blade.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>PDF to HTML Conversion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    {!! $html !!}
</body>
</html>
```

## Features

- Converts PDF files to responsive HTML
- Preserves text formatting and layout
- Handles images and maintains their positions
- Includes print-friendly styles
- Responsive design for various screen sizes
- Uses the efficient `pdftohtml` command-line tool

## How it Works

The package uses the `pdftohtml` command-line tool from poppler-utils to convert PDF files to HTML. The conversion process:

1. Takes a PDF file as input
2. Converts it to HTML while preserving formatting
3. Processes images and fixes their paths
4. Adds responsive and print-friendly CSS
5. Returns a clean, formatted HTML string

## License

This package is open-sourced software licensed under the MIT license.

## Support

If you encounter any issues or have questions, please [create an issue](https://github.com/moinulict/laravel-pdf-to-html/issues) on GitHub. 