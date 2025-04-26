<?php

namespace Moinul\LaravelPdfToHtml\Services;

use InvalidArgumentException;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;

class PdfToHtmlConverter
{
    /**
     * Convert PDF file to HTML
     *
     * @param string $pdfPath Path to the PDF file
     * @param array $options Conversion options
     * @return HtmlString
     * @throws InvalidArgumentException
     */
    public function convert(string $pdfPath, array $options = []): HtmlString
    {
        if (!file_exists($pdfPath)) {
            throw new InvalidArgumentException("PDF file not found at path: {$pdfPath}");
        }

        // Create output directory if it doesn't exist
        $outputPath = storage_path('app/public/pdf-output');
        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        try {
            // Convert PDF to HTML using pdftohtml command
            $command = sprintf(
                'pdftohtml -noframes -c -i %s %s/output.html 2>&1',
                escapeshellarg($pdfPath),
                escapeshellarg($outputPath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception("Failed to convert PDF: " . implode("\n", $output));
            }
            
            // Read the generated HTML file
            $htmlPath = $outputPath . '/output.html';
            if (!file_exists($htmlPath)) {
                throw new \Exception("HTML file was not generated");
            }
            
            $html = file_get_contents($htmlPath);
            
            // Fix image paths in the HTML
            $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/', function($matches) {
                $imagePath = $matches[1];
                // Convert relative path to absolute URL using storage path
                return str_replace($matches[1], url('storage/pdf-output/' . basename($imagePath)), $matches[0]);
            }, $html);
            
            // Add basic responsive styling
            if (preg_match('/<style.*?>(.*?)<\/style>/s', $html, $matches)) {
                $css = $matches[1];
                $css .= "
                    body { margin: 0; padding: 20px; }
                    #page-container { max-width: 1200px; margin: 0 auto; }
                    #page-container > div { 
                        margin-bottom: 20px; 
                        padding: 20px;
                        background: white;
                        box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    }
                    img { max-width: 100%; height: auto; }
                    @media print {
                        body { padding: 0; }
                        #page-container > div { 
                            margin: 0; 
                            padding: 0;
                            box-shadow: none;
                            page-break-after: always;
                        }
                    }
                ";
                $html = str_replace($matches[0], "<style>{$css}</style>", $html);
            }

            // Wrap content in a container
            $html = "<!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1'>
                <title>PDF to HTML Conversion</title>
                <style>
                    {$css}
                </style>
            </head>
            <body>
                <div id='page-container'>
                    {$html}
                </div>
            </body>
            </html>";
            
            return new HtmlString($html);
        } catch (\Exception $e) {
            Log::error('PDF conversion error: ' . $e->getMessage());
            throw new InvalidArgumentException('Failed to convert PDF: ' . $e->getMessage());
        }
    }
} 