<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

// Route to convert uploaded PDF to HTML
Route::post('/convert-pdf', function (Request $request) {
    // Validate the request
    $request->validate([
        'pdf_file' => 'required|file|mimes:pdf|max:10240', // max 10MB
    ]);

    try {
        // Get the uploaded file
        $pdfFile = $request->file('pdf_file');
        
        // Store the file temporarily
        $pdfPath = $pdfFile->store('temp');
        $fullPath = storage_path('app/' . $pdfPath);

        // Initialize the converter
        $converter = app(PdfToHtmlConverter::class);
        
        // Convert PDF to HTML
        $html = $converter->convert($fullPath);

        // Clean up the temporary file
        unlink($fullPath);

        // Return the HTML content
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

// Route to convert PDF from URL
Route::get('/convert-pdf-url', function (Request $request) {
    // Validate the request
    $request->validate([
        'url' => 'required|url'
    ]);

    try {
        // Get PDF from URL
        $pdfUrl = $request->input('url');
        $pdfContent = file_get_contents($pdfUrl);
        
        // Store the file temporarily
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        file_put_contents($tempFile, $pdfContent);

        // Initialize the converter
        $converter = app(PdfToHtmlConverter::class);
        
        // Convert PDF to HTML
        $html = $converter->convert($tempFile);

        // Clean up the temporary file
        unlink($tempFile);

        // Return the HTML content
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

// Route to display PDF conversion form
Route::get('/pdf-converter', function () {
    return view('pdf-converter');
}); 