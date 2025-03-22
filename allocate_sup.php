<?php
session_start();
include_once('../db_connection.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getStudents($conn) {
    $query = "SELECT s.id, s.name, s.reg_no, i.name AS interest
              FROM student s
              LEFT JOIN interest i ON s.interest_id = i.id
              ORDER BY s.id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch lecturers - modified to check exemption status from lecturer_exemptions table
function getLecturers($conn) {
    // Determine current academic year and semester
    $current_year = date('Y');
    $next_year = $current_year + 1;
    $current_academic_year = $current_year . '/' . $next_year;

    $current_month = date('n');
    if ($current_month >= 8 && $current_month <= 12) {
        $current_semester = 'First Semester';
    } else if ($current_month >= 1 && $current_month <= 5) {
        $current_semester = 'Second Semester';
    } else {
        $current_semester = 'Summer';
    }
    
    // Query with current semester and academic year
    $query = "SELECT l.id, l.name, 
              (SELECT COUNT(*) > 0 FROM lecturer_exemptions 
               WHERE lecturer_id = l.id 
               AND status = 'approved' 
               AND academic_year = ? 
               AND semester = ?) AS is_exempted,
              GROUP_CONCAT(e.name SEPARATOR ', ') AS expertise_areas
              FROM lecturers l
              LEFT JOIN lecturer_expertise le ON l.id = le.lecturer_id
              LEFT JOIN expertise e ON le.expertise_id = e.id
              GROUP BY l.id
              ORDER BY l.id";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $current_academic_year, $current_semester);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Allocation logic - modified to skip exempted lecturers
function allocateStudents($students, $lecturers) {
    $allocation = [];
    
    // Filter out exempted lecturers
    $activeLecturers = array_filter($lecturers, function($lecturer) {
        return !isset($lecturer['is_exempted']) || $lecturer['is_exempted'] != 1;
    });
    
    // If no active lecturers, return empty allocation
    if (empty($activeLecturers)) {
        return $allocation;
    }
    
    $numLecturers = count($activeLecturers);
    $studentsPerLecturer = ceil(count($students) / $numLecturers);

    // Initialize allocation array for active lecturers only
    foreach ($activeLecturers as $lecturer) {
        $allocation[$lecturer['id']] = [];
    }

    $unallocated = [];

    // First pass: Allocate students based on interest match
    foreach ($students as $student) {
        $allocated = false;
        foreach ($activeLecturers as $lecturer) {
            if (count($allocation[$lecturer['id']]) < $studentsPerLecturer &&
                strpos($lecturer['expertise_areas'], $student['interest']) !== false) {
                $allocation[$lecturer['id']][] = $student;
                $allocated = true;
                break;
            }
        }
        if (!$allocated) {
            $unallocated[] = $student;
        }
    }

    // Second pass: Allocate remaining students evenly
    if (!empty($unallocated)) {
        foreach ($unallocated as $student) {
            $minStudents = PHP_INT_MAX;  // Start with a large number
            $allocatedLecturer = null;

            // Find the active lecturer with the least number of students allocated
            foreach ($activeLecturers as $lecturer) {
                if (count($allocation[$lecturer['id']]) < $minStudents) {
                    $minStudents = count($allocation[$lecturer['id']]);
                    $allocatedLecturer = $lecturer['id'];
                }
            }

            // Allocate the student to this lecturer
            if ($allocatedLecturer !== null) {
                $allocation[$allocatedLecturer][] = $student;
            }
        }
    }

    return $allocation;
}

// Save allocation to database
function saveAllocationToDatabase($conn, $allocation) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Clear existing allocations
        $clearQuery = "DELETE FROM student_lecturer_allocation";
        $conn->query($clearQuery);

        // Insert new allocations
        $insertQuery = "INSERT INTO student_lecturer_allocation (student_id, lecturer_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);

        foreach ($allocation as $lecturerId => $students) {
            foreach ($students as $student) {
                $stmt->bind_param("ii", $student['id'], $lecturerId);
                $stmt->execute();
            }
        }

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
}

// Perform allocation
$students = getStudents($conn);
$lecturers = getLecturers($conn);
$allocation = allocateStudents($students, $lecturers);

// Save allocation to database
try {
    saveAllocationToDatabase($conn, $allocation);
    $_SESSION['allocation_success'] = true;
} catch (Exception $e) {
    $_SESSION['allocation_error'] = "Error saving allocation: " . $e->getMessage();
}

// Save allocation to session and redirect to results page
$_SESSION['allocation'] = $allocation;
$_SESSION['lecturers'] = $lecturers;
header("Location: results_sup.php");
exit();
?>

