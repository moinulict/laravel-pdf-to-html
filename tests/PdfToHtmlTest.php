<?php

namespace Moinul\LaravelPdfToHtml\Tests;

use Orchestra\Testbench\TestCase;
use Moinul\LaravelPdfToHtml\PdfToHtmlServiceProvider;
use Moinul\LaravelPdfToHtml\Facades\PdfToHtml;
use InvalidArgumentException;
use setasign\Fpdi\Fpdi as FPDF;
use PHPUnit\Framework\Assert;

class PdfToHtmlTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PdfToHtmlServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'PdfToHtml' => PdfToHtml::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Add configuration
        $app['config']->set('pdf-to-html.output.include_styles', true);
        $app['config']->set('pdf-to-html.css_classes', [
            'container' => 'pdf-content-container',
            'content' => 'pdf-content',
            'page' => 'pdf-page',
            'paragraph' => 'pdf-paragraph',
            'heading' => 'pdf-heading',
            'image' => 'pdf-image',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_non_existent_file()
    {
        $this->expectException(InvalidArgumentException::class);
        PdfToHtml::convert('non-existent.pdf');
    }

    /** @test */
    public function it_converts_pdf_to_html()
    {
        // Create a sample PDF file for testing
        $pdfPath = __DIR__ . '/fixtures/sample.pdf';
        if (!file_exists(__DIR__ . '/fixtures')) {
            mkdir(__DIR__ . '/fixtures', 0777, true);
        }
        
        // Create a simple PDF file with text using FPDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(40, 10, 'Test PDF Content');
        $pdf->Output($pdfPath, 'F');

        // Convert PDF to HTML
        $html = PdfToHtml::convert($pdfPath);
        $htmlString = $html->toHtml();

        // Assert responsive structure
        Assert::assertStringContainsString('pdf-content-container', $htmlString);
        Assert::assertStringContainsString('pdf-page', $htmlString);
        Assert::assertStringContainsString('Test PDF Content', $htmlString);
        Assert::assertStringContainsString('<style>', $htmlString);
        Assert::assertStringContainsString('@media', $htmlString);
        
        // Cleanup
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
        if (is_dir(__DIR__ . '/fixtures')) {
            rmdir(__DIR__ . '/fixtures');
        }
    }

    /** @test */
    public function it_generates_responsive_styles()
    {
        $pdfPath = __DIR__ . '/fixtures/sample.pdf';
        if (!file_exists(__DIR__ . '/fixtures')) {
            mkdir(__DIR__ . '/fixtures', 0777, true);
        }
        
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(40, 10, 'Responsive Test');
        $pdf->Output($pdfPath, 'F');

        // Convert and check responsive elements
        $html = PdfToHtml::convert($pdfPath);
        $htmlString = $html->toHtml();

        // Assert responsive features
        Assert::assertStringContainsString('max-width:', $htmlString);
        Assert::assertStringContainsString('@media (max-width:', $htmlString);
        Assert::assertStringContainsString('@media (min-width:', $htmlString);
        Assert::assertStringContainsString('@media print', $htmlString);
        
        // Cleanup
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
        if (is_dir(__DIR__ . '/fixtures')) {
            rmdir(__DIR__ . '/fixtures');
        }
    }
} 