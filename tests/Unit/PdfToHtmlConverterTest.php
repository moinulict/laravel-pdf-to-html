<?php

namespace Moinul\LaravelPdfToHtml\Tests\Unit;

use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use TCPDF;
use Illuminate\Support\HtmlString;
use Moinul\LaravelPdfToHtml\PdfToHtmlServiceProvider;

/**
 * @internal
 */
class PdfToHtmlConverterTest extends TestCase
{
    private PdfToHtmlConverter $converter;
    private string $samplePdfPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create the converter instance
        $this->converter = new PdfToHtmlConverter();
        
        // Create a sample PDF for testing
        $this->samplePdfPath = $this->createSamplePdf();
        
        // Mock storage configuration
        Storage::fake('public');
        
        // Set up configuration
        Config::set('pdf-to-html', [
            'storage_path' => 'pdf-images',
            'public_path' => 'storage/pdf-images',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up the sample PDF
        if (file_exists($this->samplePdfPath)) {
            unlink($this->samplePdfPath);
        }
        
        parent::tearDown();
    }

    /** 
     * @test 
     * @covers \Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter::convert
     */
    public function it_can_convert_pdf_to_html(): void
    {
        // Convert the sample PDF to HTML
        $html = $this->converter->convert($this->samplePdfPath);

        // Assert the result is an HtmlString
        $this->assertInstanceOf(HtmlString::class, $html);
        
        // Assert the HTML contains expected structure
        $this->assertStringContainsString('<!DOCTYPE html>', $html->toHtml());
        $this->assertStringContainsString('<div class="pdf-container">', $html->toHtml());
        $this->assertStringContainsString('<div class="pdf-page"', $html->toHtml());
    }

    /** 
     * @test 
     * @covers \Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter::convert
     */
    public function it_throws_exception_for_non_existent_pdf(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PDF file not found at path:');

        $this->converter->convert('non-existent.pdf');
    }

    /** 
     * @test 
     * @covers \Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter::convert
     */
    public function it_handles_empty_pdf(): void
    {
        // Create an empty PDF
        $emptyPdfPath = $this->createEmptyPdf();

        // Convert the empty PDF
        $html = $this->converter->convert($emptyPdfPath);

        // Assert basic structure is present
        $this->assertStringContainsString('<!DOCTYPE html>', $html->toHtml());
        $this->assertStringContainsString('<div class="pdf-container">', $html->toHtml());

        // Clean up
        unlink($emptyPdfPath);
    }

    /** 
     * @test 
     * @covers \Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter::convert
     */
    public function it_preserves_text_formatting(): void
    {
        $html = $this->converter->convert($this->samplePdfPath);
        
        // Assert text content is wrapped in appropriate div
        $this->assertStringContainsString('<div class="pdf-text">', $html->toHtml());
        
        // Assert sample content is present
        $this->assertStringContainsString('Sample PDF Content', $html->toHtml());
        $this->assertStringContainsString('Second line of text', $html->toHtml());
    }

    /** 
     * @test 
     * @covers \Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter::convert
     */
    public function it_handles_custom_options(): void
    {
        $options = [
            'extract_images' => false,
            'image_quality' => 75,
            'preserve_styles' => false,
            'dpi' => 150
        ];

        $html = $this->converter->convert($this->samplePdfPath, $options);
        
        // Assert the conversion completed successfully
        $this->assertInstanceOf(HtmlString::class, $html);
    }

    /**
     * Create a sample PDF file for testing
     *
     * @return string Path to the created PDF file
     */
    private function createSamplePdf(): string
    {
        /** @var TCPDF $pdf */
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Sample PDF Content', 0, 1);
        $pdf->Cell(0, 10, 'Second line of text', 0, 1);
        
        $path = sys_get_temp_dir() . '/test.pdf';
        $pdf->Output($path, 'F');
        
        return $path;
    }

    /**
     * Create an empty PDF file for testing
     *
     * @return string Path to the created PDF file
     */
    private function createEmptyPdf(): string
    {
        /** @var TCPDF $pdf */
        $pdf = new TCPDF();
        $pdf->AddPage();
        
        $path = sys_get_temp_dir() . '/empty.pdf';
        $pdf->Output($path, 'F');
        
        return $path;
    }

    /**
     * Get package providers
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PdfToHtmlServiceProvider::class
        ];
    }
} 