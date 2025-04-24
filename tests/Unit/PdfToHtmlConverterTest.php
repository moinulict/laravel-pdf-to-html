<?php

namespace Moinul\LaravelPdfToHtml\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Moinul\LaravelPdfToHtml\PdfToHtmlConverter;
use Moinul\LaravelPdfToHtml\PdfToHtmlServiceProvider;
use FPDF;

class PdfToHtmlConverterTest extends TestCase
{
    use WithFaker;

    private PdfToHtmlConverter $converter;
    private string $testPdfPath;
    private string $testImagePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create storage disk for testing
        Storage::fake('local');
        
        $this->converter = new PdfToHtmlConverter();
        $this->testPdfPath = storage_path('app/test.pdf');
        $this->testImagePath = storage_path('app/test-image.jpg');
        
        // Create test files
        $this->createTestPdf();
        $this->createTestImage();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testPdfPath)) {
            unlink($this->testPdfPath);
        }
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
        
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [PdfToHtmlServiceProvider::class];
    }

    /** @test */
    public function it_throws_exception_for_non_existent_file()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->converter->convert('non-existent.pdf');
    }

    /** @test */
    public function it_converts_simple_pdf_to_html()
    {
        $html = $this->converter->convert($this->testPdfPath);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('Test PDF Content', $html);
    }

    private function createTestPdf(): void
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Test PDF Content');
        $pdf->Output('F', $this->testPdfPath);
    }

    private function createTestImage(): void
    {
        // Create a simple test image
        $image = imagecreatetruecolor(100, 100);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);
        imagejpeg($image, $this->testImagePath);
        imagedestroy($image);
    }
} 