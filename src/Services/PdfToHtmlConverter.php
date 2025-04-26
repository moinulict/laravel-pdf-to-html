<?php

namespace Moinul\LaravelPdfToHtml\Services;

use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Element;
use Smalot\PdfParser\Element\ElementXRef;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Imagick;
use TCPDF;
use Mpdf\Mpdf;

class PdfToHtmlConverter
{
    private Parser $parser;
    private array $config;
    private string $imageStoragePath;
    private ?Mpdf $mpdf;

    public function __construct()
    {
        $this->parser = new Parser();
        $this->config = config('pdf-to-html');
        $this->imageStoragePath = storage_path('app/public/pdf-images');
        $this->mpdf = null;
        
        // Create storage directories if they don't exist
        if (!is_dir(storage_path('app/public'))) {
            mkdir(storage_path('app/public'), 0777, true);
        }
        
        // Create symbolic link if it doesn't exist
        if (!file_exists(public_path('storage'))) {
            $this->createStorageLink();
        }
        
        // Ensure image storage directory exists
        if (!is_dir($this->imageStoragePath)) {
            mkdir($this->imageStoragePath, 0777, true);
        }
    }

    /**
     * Create storage symbolic link
     */
    private function createStorageLink(): void
    {
        $target = storage_path('app/public');
        $link = public_path('storage');

        if (PHP_OS_FAMILY === 'Windows') {
            exec('mklink /D "' . str_replace('/', '\\', $link) . '" "' . str_replace('/', '\\', $target) . '"');
        } else {
            symlink($target, $link);
        }
    }

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

        // Merge options with defaults
        $options = array_merge([
            'extract_images' => true,
            'image_quality' => 90,
            'preserve_styles' => true,
            'dpi' => 300,
            'page_width' => 210, // A4 width in mm
            'page_height' => 297 // A4 height in mm
        ], $options);

        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            
            $html = $this->generateHeader();
            
            foreach ($pages as $pageNumber => $page) {
                $html .= $this->convertPageToHtml($page, $pageNumber + 1, $options);
            }
            
            $html .= $this->generateFooter();
            
            return new HtmlString($html);
        } catch (\Exception $e) {
            Log::error('PDF conversion error: ' . $e->getMessage());
            throw new InvalidArgumentException('Failed to convert PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate HTML header with styles
     *
     * @return string
     */
    private function generateHeader(): string
    {
        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style type="text/css">
                .pdf-container { width: 100%; max-width: 1200px; margin: 0 auto; }
                .pdf-page { position: relative; margin-bottom: 20px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px; }
                .pdf-content { position: relative; }
                .pdf-text { margin: 10px 0; line-height: 1.6; }
                .pdf-image { max-width: 100%; height: auto; display: block; margin: 10px 0; }
                @media print {
                    .pdf-page { box-shadow: none; margin: 0; page-break-after: always; }
                }
            </style>
        </head>
        <body>
        <div class="pdf-container">';
    }

    /**
     * Generate HTML footer
     *
     * @return string
     */
    private function generateFooter(): string
    {
        return '</div></body></html>';
    }

    /**
     * Convert a single page to HTML
     *
     * @param Page $page
     * @param int $pageNumber
     * @param array $options
     * @return string
     */
    private function convertPageToHtml(Page $page, int $pageNumber, array $options): string
    {
        try {
            $pageDetails = $page->getDetails();
            $pageWidth = $pageDetails['MediaBox'][2] ?? 595; // Default A4 width in points
            $pageHeight = $pageDetails['MediaBox'][3] ?? 842; // Default A4 height in points
            
            $html = sprintf('<div class="pdf-page" style="width: %spx; min-height: %spx;" data-page="%d">', 
                $pageWidth * 96/72, // Convert points to pixels
                $pageHeight * 96/72,
                $pageNumber
            );

            $html .= '<div class="pdf-content">';
            
            // Get text content with proper handling of Header objects
            try {
                $text = '';
                $textElements = $page->getTextArray();
                foreach ($textElements as $element) {
                    if (is_string($element)) {
                        $text .= $element . ' ';
                    } elseif (method_exists($element, '__toString')) {
                        $text .= $element->__toString() . ' ';
                    } elseif (is_object($element) && method_exists($element, 'getText')) {
                        $text .= $element->getText() . ' ';
                    }
                }
                $text = trim($text);
            } catch (\Exception $e) {
                $text = '';
                Log::warning('Error extracting text from page ' . $pageNumber . ': ' . $e->getMessage());
            }
            
            // Process text content
            if (!empty($text)) {
                // Split into paragraphs and preserve line breaks
                $paragraphs = preg_split('/\n\s*\n/', $text);
                foreach ($paragraphs as $paragraph) {
                    $paragraph = trim($paragraph);
                    if (!empty($paragraph)) {
                        // Convert single newlines to <br> tags
                        $paragraph = nl2br(htmlspecialchars($paragraph));
                        $html .= sprintf('<div class="pdf-text">%s</div>', $paragraph);
                    }
                }
            }
            
            $html .= '</div></div>';
            
            return $html;
        } catch (\Exception $e) {
            Log::error('Page conversion error: ' . $e->getMessage());
            return sprintf('<div class="pdf-page"><div class="pdf-content">Failed to convert page %d</div></div>', $pageNumber);
        }
    }

    /**
     * Extract styled elements from PDF page
     *
     * @param Page $page
     * @return array
     */
    private function extractStyledElements(Page $page): array
    {
        $elements = [];
        $fonts = $page->getFonts();
        
        // Get text elements with their styles
        $text = $page->getText();
        $details = $page->getDetails();
        
        // Parse text into elements with positioning
        preg_match_all('/\[(.*?)\]TJ/i', $details['text'] ?? '', $matches);
        
        foreach ($matches[1] as $index => $textContent) {
            // Extract font details
            $currentFont = null;
            foreach ($fonts as $font) {
                if (strpos($details['text'], $font->getName()) !== false) {
                    $currentFont = $font;
                    break;
                }
            }
            
            // Get text position
            $position = $this->extractTextPosition($details, $index);
            
            $elements[] = [
                'type' => 'text',
                'content' => trim($textContent, '()'),
                'style' => [
                    'font' => $currentFont ? [
                        'name' => $currentFont->getName(),
                        'size' => $currentFont->getDetails()['FontSize'] ?? 12,
                        'weight' => $this->getFontWeight($currentFont)
                    ] : null,
                    'color' => $this->extractColor($details['stroke_color'] ?? null),
                    'position' => $position
                ]
            ];
        }
        
        return $elements;
    }

    /**
     * Extract text position from PDF details
     *
     * @param array $details
     * @param int $index
     * @return array
     */
    private function extractTextPosition(array $details, int $index): array
    {
        $matrix = $details['TextMatrix'] ?? null;
        if ($matrix) {
            return [
                'x' => $matrix[4] ?? 0,
                'y' => $matrix[5] ?? 0
            ];
        }
        
        // Fallback positioning
        return [
            'x' => 0,
            'y' => $index * 20 // Simple vertical stacking
        ];
    }

    /**
     * Get font weight based on font name
     *
     * @param mixed $font
     * @return string
     */
    private function getFontWeight($font): string
    {
        if (!$font) {
            return 'normal';
        }

        try {
            $details = $font->getDetails();
            $name = strtolower($details['Name'] ?? '');
            
            if (strpos($name, 'bold') !== false || strpos($name, 'black') !== false) {
                return 'bold';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get font weight: ' . $e->getMessage());
        }
        
        return 'normal';
    }

    /**
     * Extract images from PDF page with enhanced quality
     *
     * @param Page $page
     * @param int $pageNumber
     * @param array $options
     * @return array
     */
    private function extractImages(Page $page, int $pageNumber, array $options): array
    {
        // Image extraction disabled for now
        return [];
    }

    /**
     * Get image position from Imagick object
     *
     * @param Imagick $image
     * @return array
     */
    private function getImagePosition(Imagick $image): array
    {
        $geometry = $image->getImageGeometry();
        return [
            'x' => $geometry['x'] ?? 0,
            'y' => $geometry['y'] ?? 0,
            'width' => $geometry['width'] ?? 0,
            'height' => $geometry['height'] ?? 0
        ];
    }

    /**
     * Insert an image into HTML with positioning
     *
     * @param array $image
     * @return string
     */
    private function insertImage(array $image): string
    {
        $style = sprintf(
            'position: absolute; left: %spx; top: %spx; width: %spx; height: %spx;',
            $image['position']['x'],
            $image['position']['y'],
            $image['position']['width'],
            $image['position']['height']
        );

        return sprintf(
            '<img src="%s" alt="%s" class="pdf-image" style="%s">',
            $image['path'],
            $image['alt'],
            $style
        );
    }

    /**
     * Extract color information
     *
     * @param mixed $color
     * @return string
     */
    private function extractColor($color): string
    {
        if (!$color) {
            return '#000000';
        }

        if (is_array($color)) {
            // Convert RGB array to hex
            return sprintf('#%02x%02x%02x',
                (int)($color[0] * 255),
                (int)($color[1] * 255),
                (int)($color[2] * 255)
            );
        }

        return $color;
    }

    /**
     * Format a styled element
     *
     * @param array $element
     * @return string
     */
    private function formatStyledElement(array $element): string
    {
        $style = $element['style'];
        $styleString = sprintf(
            'style="font-family: %s; font-size: %spx; color: %s; position: absolute; left: %spx; top: %spx;"',
            $style['font']['name'] ?? 'Arial',
            $style['font']['size'] ?? 12,
            $style['color'],
            $style['position']['x'],
            $style['position']['y']
        );

        if ($this->isHeading($element)) {
            return sprintf('<h2 class="pdf-heading" %s>%s</h2>', 
                $styleString, 
                htmlspecialchars($element['content'])
            );
        }

        return sprintf('<p class="pdf-text" %s>%s</p>', 
            $styleString, 
            htmlspecialchars($element['content'])
        );
    }

    /**
     * Determine if an element is a heading
     *
     * @param array $element
     * @return bool
     */
    private function isHeading(array $element): bool
    {
        $content = $element['content'];
        $style = $element['style'];
        
        return (
            strlen($content) < 100 &&
            preg_match('/^[A-Z0-9\s]{5,}$/', $content) &&
            ($style['font']['size'] > 14 || $style['font']['weight'] === 'bold')
        );
    }
} 