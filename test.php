#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

// Create a test PDF using TCPDF
require_once(__DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php');

class PDF extends \TCPDF {
    // ... existing code ...
}

// Create new PDF document
$pdf = new PDF();
$pdf->SetCreator('Test Script');
$pdf->SetAuthor('Test Author');
$pdf->SetTitle('Test PDF Document');

// Add a page
$pdf->AddPage();

// Set default font
$pdf->SetFont('helvetica', '', 12);

// Add title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Test PDF Document', 0, 1, 'C');
$pdf->Ln();

// Add normal text
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 10, 'This is a test PDF document with various elements to test the PDF to HTML conversion. It includes different text styles, sizes, and formatting.', 0, 'L');
$pdf->Ln();

// Add bold text
$pdf->SetFont('helvetica-bold', '', 12);
$pdf->Cell(0, 10, 'This is bold text to test font weight conversion.', 0, 1, 'L');

// Add italic text
$pdf->SetFont('helvetica-oblique', '', 12);
$pdf->Cell(0, 10, 'This is italic text to test font style conversion.', 0, 1, 'L');

// Add colored text
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(0, 10, 'This is red text to test color conversion.', 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);

// Add a table
$pdf->Ln();
$pdf->SetFont('helvetica-bold', '', 12);
$pdf->Cell(60, 10, 'Column 1', 1, 0, 'C');
$pdf->Cell(60, 10, 'Column 2', 1, 0, 'C');
$pdf->Cell(60, 10, 'Column 3', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(60, 10, 'Data 1', 1, 0, 'C');
$pdf->Cell(60, 10, 'Data 2', 1, 0, 'C');
$pdf->Cell(60, 10, 'Data 3', 1, 1, 'C');

// Save the PDF
$pdfPath = __DIR__ . '/test.pdf';
$pdf->Output($pdfPath, 'F');
echo "Test PDF created successfully!\n";

// Bootstrap minimal Laravel application
$app = new Container();
Container::setInstance($app);

// Register Config
$config = new Repository();
$config->set('pdf-to-html', require __DIR__ . '/config/pdf-to-html.php');
$app->instance('config', $config);

// Register Filesystem
$app->singleton('files', function() {
    return new \Illuminate\Filesystem\Filesystem();
});

// Register Config Repository
$app->singleton('config', function() use ($config) {
    return $config;
});

// Initialize Facade
Facade::setFacadeApplication($app);

// Create the converter
$converter = new PdfToHtmlConverter();

// Convert PDF to HTML
try {
    $html = $converter->convert($pdfPath);
    file_put_contents('output.html', $html);
    echo "Conversion successful! Check output.html\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 