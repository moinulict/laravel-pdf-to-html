<?php

namespace Moinul\LaravelPdfToHtml\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Moinul\LaravelPdfToHtml\PdfToHtmlServiceProvider;
use Illuminate\Support\Facades\Http;

class PdfToHtmlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create storage directories
        Storage::makeDirectory('public/pdf-images');
        Storage::makeDirectory('temp/pdf-to-html');
    }

    protected function getPackageProviders($app)
    {
        return [PdfToHtmlServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        // Configure storage path for testing
        $app['config']->set('filesystems.disks.public.root', storage_path('app/public'));
        
        // Configure PDF to HTML settings
        $app['config']->set('pdf-to-html', [
            'css_classes' => [
                'container' => 'pdf-content-container',
                'content' => 'pdf-content',
                'page' => 'pdf-page',
                'paragraph' => 'pdf-paragraph',
                'heading' => 'pdf-heading',
                'image' => 'pdf-image',
            ],
            'output' => [
                'include_styles' => true,
                'preserve_whitespace' => true,
                'split_paragraphs' => true,
                'responsive_images' => true,
            ],
        ]);
    }

    /** @test */
    public function it_can_convert_uploaded_pdf_file()
    {
        // Create a test PDF file
        $pdf = $this->createTestPdf();
        
        // Create an uploaded file instance
        $uploadedFile = new UploadedFile(
            $pdf,
            'test.pdf',
            'application/pdf',
            null,
            true
        );

        // Make the request
        $response = $this->post('/pdf-to-html/convert', [
            'pdf_file' => $uploadedFile,
            'extract_images' => true,
            'image_quality' => 80,
        ]);

        // Assert response
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html');
        
        // Check HTML content
        $content = $response->getContent();
        $this->assertStringContainsString('pdf-content-container', $content);
        $this->assertStringContainsString('Test PDF Content', $content);
    }

    /** @test */
    public function it_can_convert_pdf_from_url()
    {
        // Mock HTTP client for PDF URL
        Http::fake([
            'example.com/test.pdf' => Http::response(
                file_get_contents($this->createTestPdf()),
                200,
                ['Content-Type' => 'application/pdf']
            ),
        ]);

        // Make the request
        $response = $this->post('/pdf-to-html/convert-url', [
            'pdf_url' => 'https://example.com/test.pdf',
            'extract_images' => true,
            'image_quality' => 80,
        ]);

        // Assert response
        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html');

        // Check HTML content
        $content = $response->getContent();
        $this->assertStringContainsString('pdf-content-container', $content);
        $this->assertStringContainsString('Test PDF Content', $content);
    }

    /** @test */
    public function it_handles_invalid_pdf_url()
    {
        Http::fake([
            'example.com/invalid.pdf' => Http::response(null, 404),
        ]);

        $response = $this->post('/pdf-to-html/convert-url', [
            'pdf_url' => 'https://example.com/invalid.pdf',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_handles_large_pdf_files()
    {
        // Mock a large PDF file response
        Http::fake([
            'example.com/large.pdf' => Http::response(
                'fake content',
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Length' => 50 * 1024 * 1024 // 50MB
                ]
            ),
        ]);

        $response = $this->post('/pdf-to-html/convert-url', [
            'pdf_url' => 'https://example.com/large.pdf',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'PDF file size exceeds the maximum limit of 40MB'
            ]);
    }

    /** @test */
    public function it_validates_pdf_mime_type()
    {
        Http::fake([
            'example.com/fake.pdf' => Http::response(
                'not a pdf',
                200,
                ['Content-Type' => 'text/plain']
            ),
        ]);

        $response = $this->post('/pdf-to-html/convert-url', [
            'pdf_url' => 'https://example.com/fake.pdf',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid file type. Expected PDF, got: text/plain'
            ]);
    }

    /** @test */
    public function it_handles_cdn_redirects()
    {
        Http::fake([
            'cdn.example.com/test.pdf' => Http::response(null, 302)->header(
                'Location', 
                'https://storage.example.com/test.pdf'
            ),
            'storage.example.com/test.pdf' => Http::response(
                file_get_contents($this->createTestPdf()),
                200,
                ['Content-Type' => 'application/pdf']
            ),
        ]);

        $response = $this->post('/pdf-to-html/convert-url', [
            'pdf_url' => 'https://cdn.example.com/test.pdf',
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html');
    }

    /** @test */
    public function it_extracts_images_correctly()
    {
        // Create a test PDF file with an image
        $pdf = $this->createTestPdfWithImage();
        
        $uploadedFile = new UploadedFile(
            $pdf,
            'test-with-image.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->post('/pdf-to-html/convert', [
            'pdf_file' => $uploadedFile,
            'extract_images' => true,
            'image_quality' => 80,
        ]);

        $response->assertStatus(200);
        $content = $response->getContent();
        
        // Check for image elements
        $this->assertStringContainsString('<img src="', $content);
        $this->assertStringContainsString('class="pdf-image"', $content);
        
        // Check if image files were created
        $this->assertTrue(
            Storage::disk('public')->exists('pdf-images/page_1_')
        );
    }

    /** @test */
    public function it_respects_image_quality_settings()
    {
        $pdf = $this->createTestPdfWithImage();
        
        // Test with different quality settings
        $qualities = [40, 80];
        $fileSizes = [];

        foreach ($qualities as $quality) {
            Storage::disk('public')->deleteDirectory('pdf-images');
            
            $uploadedFile = new UploadedFile(
                $pdf,
                'test-with-image.pdf',
                'application/pdf',
                null,
                true
            );

            $this->post('/pdf-to-html/convert', [
                'pdf_file' => $uploadedFile,
                'extract_images' => true,
                'image_quality' => $quality,
            ]);

            // Get the size of the first image
            $files = Storage::disk('public')->files('pdf-images');
            if (!empty($files)) {
                $fileSizes[$quality] = Storage::disk('public')->size($files[0]);
            }
        }

        // Higher quality should result in larger file size
        $this->assertGreaterThan($fileSizes[40], $fileSizes[80]);
    }

    protected function createTestPdf(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_');
        
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Test PDF Content');
        $pdf->Output('F', $tempFile);
        
        return $tempFile;
    }

    protected function createTestPdfWithImage(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_');
        
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Test PDF Content');
        
        // Add a test image
        $imageFile = $this->createTestImage();
        $pdf->Image($imageFile, 10, 30, 90);
        $pdf->Output('F', $tempFile);
        
        unlink($imageFile);
        
        return $tempFile;
    }

    protected function createTestImage(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'img_test_') . '.png';
        
        $image = imagecreatetruecolor(100, 100);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);
        
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }

    protected function tearDown(): void
    {
        // Clean up storage
        Storage::deleteDirectory('public/pdf-images');
        Storage::deleteDirectory('temp/pdf-to-html');
        
        parent::tearDown();
    }
} 