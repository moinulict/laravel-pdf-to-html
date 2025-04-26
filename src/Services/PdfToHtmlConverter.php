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
        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0777, true);
        }

        try {
            $command = sprintf(
                'pdftohtml -noframes -c -i %s %s/output.html 2>&1',
                escapeshellarg($pdfPath),
                escapeshellarg($outputPath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception("Failed to convert PDF: " . implode("\n", $output));
            }
            
            $htmlPath = $outputPath . '/output.html';
            if (!file_exists($htmlPath)) {
                throw new \Exception("HTML file was not generated");
            }
            
            $html = file_get_contents($htmlPath);
            
            // Fix image paths
            $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/', function($matches) {
                return str_replace($matches[1], url('storage/pdf-output/' . basename($matches[1])), $matches[0]);
            }, $html);
            
            // Add basic styling
            if (preg_match('/<style.*?>(.*?)<\/style>/s', $html, $matches)) {
                $css = $matches[1] . "
                    body { margin: 0; padding: 20px; }
                    img { max-width: 100%; height: auto; }
                    @media print { body { padding: 0; } }
                ";
                $html = str_replace($matches[0], "<style>{$css}</style>", $html);
            }

            return new HtmlString($html);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Failed to convert PDF: ' . $e->getMessage());
        }
    }
} 