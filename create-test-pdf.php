<?php
require __DIR__ . '/vendor/autoload.php';

use FPDF;

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'PDF to HTML Converter Test', 0, 1, 'C');
        $this->Ln(10);
    }
}

$pdf = new PDF();
$pdf->AddPage();

// Main content
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 10, "This is a test document to verify the PDF to HTML conversion functionality.\n\n");

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Features being tested:', 0, 1);

$pdf->SetFont('Arial', '', 12);
$features = [
    'Text conversion',
    'Basic formatting',
    'Font rendering',
    'Layout preservation'
];

foreach ($features as $feature) {
    $pdf->Cell(10, 10, chr(149), 0, 0); // bullet point
    $pdf->Cell(0, 10, $feature, 0, 1);
}

$pdf->Ln(10);
$pdf->Cell(0, 10, 'Date: ' . date('Y-m-d'), 0, 1);

// Save the PDF
$pdf->Output('F', __DIR__ . '/test.pdf');
echo "Test PDF created successfully!\n"; 