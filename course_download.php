<?php
include_once('../db_connection.php');
require('../fpdf/fpdf.php');

// Create a custom PDF class extending FPDF
class AllocationPDF extends FPDF {
    // Page header
    function Header() {
        // Logo (if you have one)
        // $this->Image('logo.png', 10, 6, 30);
        
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        
        // Title
        $this->Cell(0, 10, 'Course Allocation Report', 0, 1, 'C');
        
        // Date
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        
        // Line break
        $this->Ln(10);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Table header for course allocations
    function CourseTableHeader() {
        // Colors for header
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 128, 128);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Header
        $this->Cell(70, 7, 'Lecturer', 1, 0, 'L', true);
        $this->Cell(70, 7, 'Course Title', 1, 0, 'L', true);
        $this->Cell(30, 7, 'Course Code', 1, 0, 'C', true);
        $this->Cell(20, 7, 'Credits', 1, 1, 'C', true);
    }
    
    // Table header for unallocated students
    function StudentTableHeader() {
        // Colors for header
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 128, 128);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Header
        $this->Cell(70, 7, 'Student Name', 1, 0, 'L', true);
        $this->Cell(50, 7, 'Registration Number', 1, 0, 'C', true);
        $this->Cell(70, 7, 'Interest Area', 1, 1, 'L', true);
    }
    
    // Table header for unallocated courses
    function UnallocatedCourseTableHeader() {
        // Colors for header
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 128, 128);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        // Header
        $this->Cell(30, 7, 'Course Code', 1, 0, 'C', true);
        $this->Cell(90, 7, 'Course Title', 1, 0, 'L', true);
        $this->Cell(20, 7, 'Credits', 1, 0, 'C', true);
        $this->Cell(50, 7, 'Category', 1, 1, 'L', true);
    }
}

// Create new PDF instance
$pdf = new AllocationPDF();

// Set document metadata
$pdf->SetTitle('Course Allocation Report');
$pdf->SetAuthor('Course Allocation System');
$pdf->SetCreator('FPDF');

// Set alias for number of pages
$pdf->AliasNbPages();

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Course Allocations', 0, 1, 'L');
$pdf->Ln(5);

// Set font
$pdf->SetFont('Arial', '', 10);

// Add table header
$pdf->CourseTableHeader();

// Fetch allocations from database
$query = "
    SELECT 
        l.name as lecturer_name, 
        c.title as course_title, 
        c.course_code, 
        c.credit_load,
        cat.name as category
    FROM 
        manual_course_allocations ca 
    JOIN 
        lecturers l ON ca.lecturer_id = l.id 
    JOIN 
        courses c ON ca.course_id = c.id 
    LEFT JOIN
        categories cat ON c.category_id = cat.id
    ORDER BY 
        l.name, c.course_code
";

$result = $conn->query($query);

// Check if there are allocations
if ($result->num_rows > 0) {
    // Group allocations by lecturer
    $currentLecturer = '';
    $lecturerTotalCredits = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Check if we're starting a new lecturer
        if ($currentLecturer != $row['lecturer_name']) {
            // If not the first lecturer, print the previous lecturer's total
            if ($currentLecturer != '') {
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(170, 7, 'Total Credit Load:', 1, 0, 'R', false);
                $pdf->Cell(20, 7, $lecturerTotalCredits, 1, 1, 'C', false);
                $pdf->Ln(3);
            }
            
            // Start new lecturer section
            $currentLecturer = $row['lecturer_name'];
            $lecturerTotalCredits = 0;
            
            // Add some space before new lecturer (except for the first one)
            if ($pdf->GetY() > 30) {
                $pdf->Ln(5);
            }
            
            // Print lecturer name as a section header
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Lecturer: ' . $row['lecturer_name'], 0, 1);
            
            // Add table header for this lecturer
            $pdf->CourseTableHeader();
        }
        
        // Add to lecturer's total credits
        $lecturerTotalCredits += $row['credit_load'];
        
        // Print allocation data
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(70, 6, $row['lecturer_name'], 1, 0, 'L');
        $pdf->Cell(70, 6, $row['course_title'], 1, 0, 'L');
        $pdf->Cell(30, 6, $row['course_code'], 1, 0, 'C');
        $pdf->Cell(20, 6, $row['credit_load'], 1, 1, 'C');
    }
    
    // Print the last lecturer's total
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(170, 7, 'Total Credit Load:', 1, 0, 'R', false);
    $pdf->Cell(20, 7, $lecturerTotalCredits, 1, 1, 'C', false);
} else {
    // No allocations found
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'No course allocations found.', 0, 1, 'C');
}

// Add a page for unallocated students
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Unallocated Students', 0, 1, 'L');
$pdf->Ln(5);

// Fetch unallocated students
$query = "
    SELECT 
        s.id AS student_id, 
        s.name AS student_name, 
        s.reg_no AS registration_number, 
        e.name AS interest_area
    FROM student s
    LEFT JOIN student_lecturer_allocation sla ON s.id = sla.student_id
    LEFT JOIN expertise e ON s.interest_id = e.id
    WHERE sla.id IS NULL
    ORDER BY s.name
";

$result = $conn->query($query);

// Check if there are unallocated students
if ($result->num_rows > 0) {
    // Add table header
    $pdf->StudentTableHeader();
    
    // Print unallocated students
    $pdf->SetFont('Arial', '', 10);
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(70, 6, $row['student_name'], 1, 0, 'L');
        $pdf->Cell(50, 6, $row['registration_number'], 1, 0, 'C');
        $pdf->Cell(70, 6, $row['interest_area'] ? $row['interest_area'] : 'Not specified', 1, 1, 'L');
    }
} else {
    // No unallocated students found
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'All students have been allocated.', 0, 1, 'C');
}

// Add a page for unallocated courses
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Unallocated Courses', 0, 1, 'L');
$pdf->Ln(5);

// Fetch unallocated courses
$query = "
    SELECT 
        c.id,
        c.course_code,
        c.title,
        c.credit_load,
        cat.name AS category_name
    FROM courses c
    LEFT JOIN manual_course_allocations mca ON c.id = mca.course_id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE mca.id IS NULL
    ORDER BY c.course_code
";

$result = $conn->query($query);

// Check if there are unallocated courses
if ($result->num_rows > 0) {
    // Add table header
    $pdf->UnallocatedCourseTableHeader();
    
    // Print unallocated courses
    $pdf->SetFont('Arial', '', 10);
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(30, 6, $row['course_code'], 1, 0, 'C');
        $pdf->Cell(90, 6, $row['title'], 1, 0, 'L');
        $pdf->Cell(20, 6, $row['credit_load'], 1, 0, 'C');
        $pdf->Cell(50, 6, $row['category_name'] ? $row['category_name'] : 'Uncategorized', 1, 1, 'L');
    }
} else {
    // No unallocated courses found
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'All courses have been allocated.', 0, 1, 'C');
}

// Add summary section
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Allocation Summary', 0, 1, 'C');
$pdf->Ln(5);

// Fetch summary data
$totalLecturers = $conn->query("SELECT COUNT(*) as count FROM lecturers")->fetch_assoc()['count'];
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM student")->fetch_assoc()['count'];
$totalCourses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];

$allocatedStudents = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM student_lecturer_allocation")->fetch_assoc()['count'];
$unallocatedStudents = $totalStudents - $allocatedStudents;

$allocatedCourses = $conn->query("SELECT COUNT(DISTINCT course_id) as count FROM manual_course_allocations")->fetch_assoc()['count'];
$unallocatedCourses = $totalCourses - $allocatedCourses;

// Print summary
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Allocation Overview', 0, 1);
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 8, 'Total Lecturers:', 0, 0);
$pdf->Cell(30, 8, $totalLecturers, 0, 1);

$pdf->Cell(100, 8, 'Total Students:', 0, 0);
$pdf->Cell(30, 8, $totalStudents, 0, 1);

$pdf->Cell(100, 8, 'Allocated Students:', 0, 0);
$pdf->Cell(30, 8, $allocatedStudents, 0, 1);

$pdf->Cell(100, 8, 'Unallocated Students:', 0, 0);
$pdf->Cell(30, 8, $unallocatedStudents, 0, 1);

$pdf->Cell(100, 8, 'Total Courses:', 0, 0);
$pdf->Cell(30, 8, $totalCourses, 0, 1);

$pdf->Cell(100, 8, 'Allocated Courses:', 0, 0);
$pdf->Cell(30, 8, $allocatedCourses, 0, 1);

$pdf->Cell(100, 8, 'Unallocated Courses:', 0, 0);
$pdf->Cell(30, 8, $unallocatedCourses, 0, 1);

// Output the PDF
$pdf->Output('D', 'Course_Allocation_Report.pdf');
?>

