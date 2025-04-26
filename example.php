#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository;
use Moinul\LaravelPdfToHtml\Services\PdfToHtmlConverter;

// Create a sample PDF using TCPDF
$pdf = new TCPDF();
$pdf->SetCreator('Example Script');
$pdf->SetAuthor('Example Author');
$pdf->SetTitle('Sample PDF Document');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Sample PDF Document', 0, 1, 'C');
$pdf->Ln();

// Add normal text
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 10, 'This is a sample PDF document to demonstrate the PDF to HTML conversion. It includes different text styles and formatting.', 0, 'L');
$pdf->Ln();

// Add bold text
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'This is bold text.', 0, 1, 'L');

// Add italic text
$pdf->SetFont('helvetica', 'I', 12);
$pdf->Cell(0, 10, 'This is italic text.', 0, 1, 'L');

// Add colored text
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(0, 10, 'This is red text.', 0, 1, 'L');

// Save the PDF
$pdfPath = __DIR__ . '/sample.pdf';
$pdf->Output($pdfPath, 'F');
echo "Sample PDF created successfully!\n";

// Bootstrap minimal Laravel application
$app = new Container();
Container::setInstance($app);

// Register Config
$config = new Repository();
$config->set('pdf-to-html', [
    'storage_path' => 'pdf-images',
    'public_path' => 'storage/pdf-images',
]);
$app->instance('config', $config);

// Register Filesystem
$app->singleton('files', function() {
    return new \Illuminate\Filesystem\Filesystem();
});

// Initialize Facade
Facade::setFacadeApplication($app);

// Create the converter
$converter = new PdfToHtmlConverter();

// Convert PDF to HTML
try {
    $html = $converter->convert($pdfPath);
    file_put_contents('sample.html', $html);
    echo "Conversion successful! Check sample.html\n";
    
    // Display the HTML content
    echo "\nHTML Output Preview:\n";
    echo "===================\n";
    echo $html;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 