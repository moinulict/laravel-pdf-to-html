<?php

namespace Moinul\LaravelPdfToHtml\Services;

use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class PdfToHtmlConverter
{
    /**
     * Convert PDF file to HTML
     *
     * @param string $pdfPath Path to the PDF file
     * @return HtmlString
     * @throws InvalidArgumentException
     */
    public function convert(string $pdfPath): HtmlString
    {
        if (!file_exists($pdfPath)) {
            throw new InvalidArgumentException("PDF file not found at path: {$pdfPath}");
        }

        $outputPath = storage_path('app/public/pdf-output');
        
        // Create output directory if it doesn't exist
        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        try {
            // Convert PDF to HTML preserving exact styling but without backgrounds
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
            
            // Remove any background images from the HTML
            $html = preg_replace('/background-image:[^;]+;/', '', $html);
            $html = preg_replace('/background:[^;]+;/', 'background: none !important;', $html);
            $html = preg_replace('/<img[^>]+background[^>]+>/', '', $html);
            
            // Fix image paths in the HTML
            $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/', function($matches) {
                $imagePath = $matches[1];
                // Convert relative path to absolute URL using storage path
                return str_replace($matches[1], url('storage/pdf-output/' . basename($imagePath)), $matches[0]);
            }, $html);
            
            // Extract and enhance the original CSS while preserving positioning
            if (preg_match('/<style.*?>(.*?)<\/style>/s', $html, $matches)) {
                $css = $matches[1];
                
                // Remove any background-related CSS
                $css = preg_replace('/(background[^:]*:[^;]+;)/', '', $css);
                
                // Add responsive and print-friendly styles
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
                
                // Replace the original CSS
                $html = str_replace($matches[0], "<style>{$css}</style>", $html);
            }

            return new HtmlString($html);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Failed to convert PDF: ' . $e->getMessage());
        }
    }
} 