<?php

namespace Moinul\LaravelPdfToHtml\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Imagick;

class PdfToHtmlConverter
{
    private Parser $parser;
    private array $config;
    private string $imageStoragePath;

    public function __construct()
    {
        $this->parser = new Parser();
        $this->config = config('pdf-to-html');
        $this->imageStoragePath = storage_path('app/public/pdf-images');
        
        // Ensure image storage directory exists
        if (!file_exists($this->imageStoragePath)) {
            mkdir($this->imageStoragePath, 0777, true);
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
            'image_quality' => 80,
        ], $options);

        $pdf = $this->parser->parseFile($pdfPath);
        $pages = $pdf->getPages();
        
        $html = $this->getStylesIfEnabled();
        $html .= sprintf('<div class="%s">', $this->config['css_classes']['container']);
        
        foreach ($pages as $pageNumber => $page) {
            $html .= $this->convertPageToHtml($page, $pageNumber + 1, $options);
        }
        
        $html .= '</div>';
        
        return new HtmlString($html);
    }

    /**
     * Convert a single page to HTML
     *
     * @param \Smalot\PdfParser\Page $page
     * @param int $pageNumber
     * @param array $options
     * @return string
     */
    private function convertPageToHtml($page, int $pageNumber, array $options): string
    {
        $text = $page->getText();
        $elements = $this->extractElements($text);
        
        $html = sprintf('<div class="%s" data-page="%d">', 
            $this->config['css_classes']['page'], 
            $pageNumber
        );

        // Extract images if enabled
        if ($options['extract_images']) {
            $images = $this->extractImages($page, $pageNumber, $options['image_quality']);
            $html .= $this->insertImages($images);
        }
        
        foreach ($elements as $element) {
            $html .= $this->formatElement($element);
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Extract images from PDF page
     *
     * @param \Smalot\PdfParser\Page $page
     * @param int $pageNumber
     * @param int $quality
     * @return array
     */
    private function extractImages($page, int $pageNumber, int $quality): array
    {
        $images = [];
        try {
            // Create Imagick instance for the page
            $imagick = new Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($page->getPath() . '[' . ($pageNumber - 1) . ']');
            
            // Convert to PNG for better quality
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality($quality);
            
            // Generate unique filename
            $filename = sprintf('page_%d_%s.png', $pageNumber, uniqid());
            $filepath = $this->imageStoragePath . '/' . $filename;
            
            // Save image
            $imagick->writeImage($filepath);
            $imagick->clear();
            
            $images[] = [
                'path' => asset('storage/pdf-images/' . $filename),
                'alt' => sprintf('Page %d Image', $pageNumber)
            ];
        } catch (\Exception $e) {
            // Log error but continue processing
            \Log::error('Failed to extract images from PDF: ' . $e->getMessage());
        }
        
        return $images;
    }

    /**
     * Insert extracted images into HTML
     *
     * @param array $images
     * @return string
     */
    private function insertImages(array $images): string
    {
        $html = '';
        foreach ($images as $image) {
            $html .= sprintf(
                '<img src="%s" alt="%s" class="%s">',
                $image['path'],
                $image['alt'],
                $this->config['css_classes']['image']
            );
        }
        return $html;
    }

    /**
     * Extract elements from text
     *
     * @param string $text
     * @return array
     */
    private function extractElements(string $text): array
    {
        // Remove multiple spaces and normalize line breaks
        $text = preg_replace('/\s+/', ' ', $text);
        $paragraphs = preg_split('/\n\s*\n/', $text);
        
        return array_filter($paragraphs, 'trim');
    }

    /**
     * Format an element with appropriate HTML tags
     *
     * @param string $text
     * @return string
     */
    private function formatElement(string $text): string
    {
        $text = trim($text);
        
        // Detect if the text might be a heading
        if (strlen($text) < 100 && preg_match('/^[A-Z0-9\s]{5,}$/', $text)) {
            return sprintf('<h2 class="%s">%s</h2>', 
                $this->config['css_classes']['heading'],
                htmlspecialchars($text)
            );
        }
        
        return sprintf('<p class="%s">%s</p>', 
            $this->config['css_classes']['paragraph'],
            htmlspecialchars($text)
        );
    }

    /**
     * Get default responsive styles if enabled
     *
     * @return string
     */
    private function getStylesIfEnabled(): string
    {
        if (!($this->config['output']['include_styles'] ?? false)) {
            return '';
        }

        return <<<HTML
        <style>
            .{$this->config['css_classes']['container']} {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            
            .{$this->config['css_classes']['page']} {
                margin-bottom: 30px;
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .{$this->config['css_classes']['heading']} {
                font-size: 24px;
                color: #2c3e50;
                margin: 20px 0;
                line-height: 1.3;
            }
            
            .{$this->config['css_classes']['paragraph']} {
                font-size: 16px;
                line-height: 1.6;
                color: #34495e;
                margin: 16px 0;
            }
            
            .{$this->config['css_classes']['image']} {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 20px auto;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            /* Responsive Breakpoints */
            @media (max-width: {$this->config['breakpoints']['mobile']}px) {
                .{$this->config['css_classes']['container']} {
                    padding: 10px;
                }
                
                .{$this->config['css_classes']['page']} {
                    padding: 15px;
                    margin-bottom: 20px;
                }
                
                .{$this->config['css_classes']['heading']} {
                    font-size: 20px;
                }
                
                .{$this->config['css_classes']['paragraph']} {
                    font-size: 14px;
                }
            }
            
            @media (min-width: {$this->config['breakpoints']['mobile']}px) and (max-width: {$this->config['breakpoints']['tablet']}px) {
                .{$this->config['css_classes']['container']} {
                    padding: 15px;
                }
            }
            
            @media print {
                .{$this->config['css_classes']['container']} {
                    max-width: none;
                    padding: 0;
                }
                
                .{$this->config['css_classes']['page']} {
                    box-shadow: none;
                    padding: 0;
                    margin-bottom: 20px;
                }
                
                .{$this->config['css_classes']['image']} {
                    max-width: 90%;
                    box-shadow: none;
                }
            }
        </style>
        HTML;
    }
} 