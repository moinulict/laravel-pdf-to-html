<?php

namespace Moinul\LaravelPdfToHtml\Tests;

use Orchestra\Testbench\TestCase;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;
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
    public function it_can_convert_pdf_to_html()
    {
        $converter = new PdfToHtmlConverter();
        
        // Create a simple test PDF
        $pdf = new \TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'Test Heading', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'This is a test paragraph.', 0, 1, 'L');
        
        // Save test PDF
        $pdfPath = storage_path('app/test.pdf');
        $pdf->Output($pdfPath, 'F');
        
        // Convert to HTML
        $html = $converter->convert($pdfPath);
        
        // Assert HTML contains expected elements
        $this->assertStringContainsString('Test Heading', $html);
        $this->assertStringContainsString('This is a test paragraph', $html);
        $this->assertStringContainsString('pdf-heading', $html);
        $this->assertStringContainsString('pdf-text', $html);
        
        // Clean up
        unlink($pdfPath);
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_pdf()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $converter = new PdfToHtmlConverter();
        $converter->convert('nonexistent.pdf');
    }

    /** @test */
    public function it_preserves_styles_and_images()
    {
        $converter = new PdfToHtmlConverter();
        
        // Create a PDF with styling and images
        $pdf = new \TCPDF();
        $pdf->AddPage();
        
        // Add styled text
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(0, 10, 'Red Heading', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 14);
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Cell(0, 10, 'Blue Text', 0, 1, 'L');
        
        // Add an image
        $imagePath = __DIR__ . '/test-image.png';
        if (file_exists($imagePath)) {
            $pdf->Image($imagePath, 10, 50, 50);
        }
        
        // Save test PDF
        $pdfPath = storage_path('app/test-styled.pdf');
        $pdf->Output($pdfPath, 'F');
        
        // Convert to HTML
        $html = $converter->convert($pdfPath);
        
        // Assert styling is preserved
        $this->assertStringContainsString('color: #ff0000', strtolower($html));
        $this->assertStringContainsString('color: #0000ff', strtolower($html));
        $this->assertStringContainsString('font-size: 24px', $html);
        $this->assertStringContainsString('font-size: 14px', $html);
        
        // Assert image handling
        if (file_exists($imagePath)) {
            $this->assertStringContainsString('pdf-image', $html);
            $this->assertStringContainsString('<img', $html);
        }
        
        // Clean up
        unlink($pdfPath);
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