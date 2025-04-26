<?php

require_once __DIR__ . '/vendor/autoload.php';

// Create PDF
$pdf = new TCPDF();

// Set document information
$pdf->SetCreator('Test PDF Creator');
$pdf->SetAuthor('Test Author');
$pdf->SetTitle('Test PDF Document');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 16);

// Add a title
$pdf->Cell(0, 10, 'Test PDF Document', 0, 1, 'C');

// Add normal text
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(10);
$pdf->MultiCell(0, 10, 'This is a test PDF document with various elements to test the PDF to HTML conversion. It includes different text styles, sizes, and formatting.', 0, 'L');

// Add bold text
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Ln(10);
$pdf->MultiCell(0, 10, 'This is bold text to test font weight conversion.', 0, 'L');

// Add italic text
$pdf->SetFont('helvetica', 'I', 12);
$pdf->Ln(10);
$pdf->MultiCell(0, 10, 'This is italic text to test font style conversion.', 0, 'L');

// Add colored text
$pdf->SetTextColor(255, 0, 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(10);
$pdf->MultiCell(0, 10, 'This is red text to test color conversion.', 0, 'L');

// Reset text color
$pdf->SetTextColor(0);

// Add a table
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(10);
$pdf->Cell(60, 10, 'Column 1', 1, 0, 'C');
$pdf->Cell(60, 10, 'Column 2', 1, 0, 'C');
$pdf->Cell(60, 10, 'Column 3', 1, 1, 'C');
$pdf->Cell(60, 10, 'Data 1', 1, 0, 'C');
$pdf->Cell(60, 10, 'Data 2', 1, 0, 'C');
$pdf->Cell(60, 10, 'Data 3', 1, 1, 'C');

// Add a new page
$pdf->AddPage();

// Add some text on the second page
$pdf->SetFont('helvetica', '', 14);
$pdf->Cell(0, 10, 'Second Page', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 10, 'This is the second page of the test document. It helps us verify that multi-page conversion works correctly.', 0, 'L');

// Save the PDF
$pdf->Output(__DIR__ . '/test.pdf', 'F');

echo "Test PDF created successfully!\n"; 