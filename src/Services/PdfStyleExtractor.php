<?php

namespace Moinul\LaravelPdfToHtml\Services;

use Smalot\PdfParser\Page;
use TCPDF_FONTS;
use Mpdf\Mpdf;

class PdfStyleExtractor
{
    /**
     * Extract styles from PDF page
     *
     * @param Page $page
     * @return array
     */
    public function extractStyles(Page $page): array
    {
        $styles = [];
        
        // Extract font information
        $fonts = $page->getFonts();
        foreach ($fonts as $font) {
            $styles['fonts'][] = [
                'name' => $font->getname(),
                'type' => $font->getType(),
                'encoding' => $font->getEncoding()
            ];
        }
        
        // Extract text styling
        $details = $page->getDetails();
        if (isset($details['TextMatrix'])) {
            $styles['text'] = [
                'size' => $details['FontSize'] ?? 12,
                'color' => $this->extractColor($details),
                'alignment' => $this->determineAlignment($details),
                'lineHeight' => $details['Leading'] ?? 1.2
            ];
        }
        
        return $styles;
    }

    /**
     * Extract color information
     *
     * @param array $details
     * @return string
     */
    private function extractColor(array $details): string
    {
        if (isset($details['FillColor'])) {
            $color = $details['FillColor'];
            // Convert color space if needed
            if (is_array($color)) {
                return sprintf('rgb(%d,%d,%d)', 
                    (int)($color[0] * 255),
                    (int)($color[1] * 255),
                    (int)($color[2] * 255)
                );
            }
            return $color;
        }
        return '#000000'; // Default black
    }

    /**
     * Determine text alignment
     *
     * @param array $details
     * @return string
     */
    private function determineAlignment(array $details): string
    {
        if (isset($details['TextMatrix'])) {
            $matrix = $details['TextMatrix'];
            // Basic alignment detection based on position
            if ($matrix[4] < 100) {
                return 'left';
            } elseif ($matrix[4] > 400) {
                return 'right';
            }
            return 'center';
        }
        return 'left'; // Default alignment
    }

    /**
     * Generate CSS for extracted styles
     *
     * @param array $styles
     * @return string
     */
    public function generateCss(array $styles): string
    {
        $css = '';
        
        // Add font-face declarations if available
        if (!empty($styles['fonts'])) {
            foreach ($styles['fonts'] as $font) {
                $css .= $this->generateFontFace($font);
            }
        }
        
        // Add text styling
        if (!empty($styles['text'])) {
            $css .= sprintf('.pdf-content { 
                font-size: %spx; 
                color: %s; 
                text-align: %s; 
                line-height: %s; 
            }',
                $styles['text']['size'],
                $styles['text']['color'],
                $styles['text']['alignment'],
                $styles['text']['lineHeight']
            );
        }
        
        return $css;
    }

    /**
     * Generate @font-face CSS
     *
     * @param array $font
     * @return string
     */
    private function generateFontFace(array $font): string
    {
        // Only generate for standard fonts or if font file exists
        if (in_array($font['name'], ['Helvetica', 'Arial', 'Times', 'Times-Roman'])) {
            return '';
        }
        
        return sprintf('@font-face {
            font-family: "%s";
            src: local("%s");
        }', $font['name'], $font['name']);
    }
} 