<?php

namespace Moinul\LaravelPdfToHtml\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moinul\LaravelPdfToHtml\Facades\PdfToHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PdfToHtmlController extends Controller
{
    /**
     * Convert PDF file to HTML
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function convert(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:40960', // max 40MB (40960 KB)
            'return_type' => 'string|in:json,html',
            'extract_images' => 'boolean',
            'image_quality' => 'integer|min:1|max:100',
        ]);

        try {
            $pdfPath = $request->file('pdf_file')->path();
            $options = [
                'extract_images' => $request->boolean('extract_images', true),
                'image_quality' => $request->integer('image_quality', 80),
            ];

            $html = PdfToHtml::convert($pdfPath, $options);

            // Return based on requested format
            if ($request->input('return_type') === 'json') {
                return response()->json([
                    'success' => true,
                    'html' => $html->toHtml(),
                ]);
            }

            // Default to HTML response
            return response($html->toHtml())
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Convert PDF URL to HTML
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function convertFromUrl(Request $request)
    {
        $request->validate([
            'pdf_url' => 'required|url',
            'return_type' => 'string|in:json,html',
            'extract_images' => 'boolean',
            'image_quality' => 'integer|min:1|max:100',
        ]);

        try {
            // Create temp directory if it doesn't exist
            $tempDir = storage_path('app/temp/pdf-to-html');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            // Download PDF file with size check and proper headers
            $pdfUrl = $request->input('pdf_url');
            
            // Use Laravel's HTTP client for better CDN support
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'application/pdf,*/*',
            ])
            ->withOptions([
                'verify' => true,
                'timeout' => 30,
                'connect_timeout' => 10,
                'follow_location' => true,
                'max_redirects' => 5,
            ])
            ->get($pdfUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to download PDF: HTTP response code ' . $response->status());
            }

            // Check Content-Type
            $contentType = $response->header('Content-Type');
            if (!str_contains(strtolower($contentType), 'pdf')) {
                throw new \Exception('Invalid file type. Expected PDF, got: ' . $contentType);
            }

            // Check file size
            $contentLength = $response->header('Content-Length');
            if ($contentLength && (int)$contentLength > 41943040) { // 40MB in bytes
                throw new \Exception('PDF file size exceeds the maximum limit of 40MB');
            }

            // Save the PDF content
            $tempFile = tempnam($tempDir, 'pdf_');
            file_put_contents($tempFile, $response->body());

            // Convert options
            $options = [
                'extract_images' => $request->boolean('extract_images', true),
                'image_quality' => $request->integer('image_quality', 80),
            ];

            // Convert to HTML
            $html = PdfToHtml::convert($tempFile, $options);

            // Clean up temp file
            unlink($tempFile);

            // Return based on requested format
            if ($request->input('return_type') === 'json') {
                return response()->json([
                    'success' => true,
                    'html' => $html->toHtml(),
                ]);
            }

            // Default to HTML response
            return response($html->toHtml())
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * View converted PDF
     *
     * @param string|null $filename
     * @return Response
     */
    public function view(?string $filename = null)
    {
        try {
            // If no filename provided, use a default PDF
            if (!$filename) {
                $pdfPath = public_path('OpenAI_Guide__1744995287.pdf');
            } else {
                $pdfPath = public_path($filename);
            }

            if (!file_exists($pdfPath)) {
                throw new \Exception("PDF file not found");
            }

            $outputPath = public_path('storage/pdf-output');
            
            // Create output directory if it doesn't exist
            if (!file_exists($outputPath)) {
                mkdir($outputPath, 0777, true);
            }

            // Convert PDF to HTML
            $html = PdfToHtml::convert($pdfPath);

            // Return the view
            return response($html)
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
} 