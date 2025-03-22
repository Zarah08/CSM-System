<?php
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
session_start();
require('../fpdf/fpdf.php');

// Check if allocation data exists
if (!isset($_SESSION['allocation']) || !isset($_SESSION['lecturers'])) {
    die("No allocation data found. Please allocate courses first.");
}

$allocation = $_SESSION['allocation'];
$lecturers = $_SESSION['lecturers'];

class PDF extends FPDF
{
    function Header()
    {
      
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'SUPERVISOR ALLOCATION RESULTS', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

foreach ($allocation as $lecturer_id => $allocated_students) {
    $lecturer = array_filter($lecturers, fn($l) => $l['id'] == $lecturer_id);
    $lecturer = reset($lecturer);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, $lecturer['name'], 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Expertise: ' . $lecturer['expertise_areas'], 0, 1);

    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 7, 'Student Name', 1);
    $pdf->Cell(40, 7, 'Registration No.', 1);
    $pdf->Cell(90, 7, 'Interest', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 10);
    foreach ($allocated_students as $student) {
        $pdf->Cell(60, 7, $student['name'], 1);
        $pdf->Cell(40, 7, $student['reg_no'], 1);
        $pdf->Cell(90, 7, $student['interest'], 1);
        $pdf->Ln();
    }

    $pdf->Ln(10);
}

$pdf->Output('D', 'allocation_results.pdf');