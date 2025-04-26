# Laravel PDF to HTML Converter

A Laravel package that converts PDF files to HTML with preserved formatting and styling.

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

### Example Routes

Here's an example of how to set up routes for PDF conversion:

```php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

// Convert uploaded PDF file
Route::post('/convert-pdf', function (Request $request) {
    $request->validate([
        'pdf_file' => 'required|file|mimes:pdf|max:10240', // max 10MB
    ]);

    try {
        $pdfFile = $request->file('pdf_file');
        $pdfPath = $pdfFile->store('temp');
        $fullPath = storage_path('app/' . $pdfPath);

        $converter = app(PdfToHtmlConverter::class);
        $html = $converter->convert($fullPath);

        unlink($fullPath); // Clean up

        return response()->json([
            'success' => true,
            'html' => $html->toHtml()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});

// Convert PDF from URL
Route::get('/convert-pdf-url', function (Request $request) {
    $request->validate([
        'url' => 'required|url'
    ]);

    try {
        $pdfUrl = $request->input('url');
        $pdfContent = file_get_contents($pdfUrl);
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        file_put_contents($tempFile, $pdfContent);

        $converter = app(PdfToHtmlConverter::class);
        $html = $converter->convert($tempFile);

        unlink($tempFile); // Clean up

        return response()->json([
            'success' => true,
            'html' => $html->toHtml()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
```

### Example Form View

Create a view file `resources/views/pdf-converter.blade.php`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>PDF to HTML Converter</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .btn { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .result { margin-top: 20px; padding: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PDF to HTML Converter</h1>
        
        <div class="form-group">
            <h3>Upload PDF File</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <input type="file" name="pdf_file" accept=".pdf" required>
                <button type="submit" class="btn">Convert</button>
            </form>
        </div>

        <div class="form-group">
            <h3>Convert from URL</h3>
            <form id="urlForm">
                @csrf
                <input type="url" name="url" placeholder="Enter PDF URL" required>
                <button type="submit" class="btn">Convert</button>
            </form>
        </div>

        <div id="result" class="result" style="display: none;"></div>
    </div>

    <script>
        document.getElementById('uploadForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('/convert-pdf', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('result').innerHTML = data.html;
                    document.getElementById('result').style.display = 'block';
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Error converting PDF');
            }
        };

        document.getElementById('urlForm').onsubmit = async (e) => {
            e.preventDefault();
            const url = new URLSearchParams(new FormData(e.target)).get('url');
            try {
                const response = await fetch(`/convert-pdf-url?url=${encodeURIComponent(url)}`);
                const data = await response.json();
                if (data.success) {
                    document.getElementById('result').innerHTML = data.html;
                    document.getElementById('result').style.display = 'block';
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Error converting PDF');
            }
        };
    </script>
</body>
</html>
```

## Features

- Converts PDF files to responsive HTML
- Preserves text formatting and layout
- Handles images and maintains their positions
- Supports both file uploads and URLs
- Includes print-friendly styles
- Responsive design for various screen sizes

## License

This package is open-sourced software licensed under the MIT license.

## Support

If you encounter any issues or have questions, please [create an issue](https://github.com/moinulict/laravel-pdf-to-html/issues) on GitHub. 