<?php
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
session_start();

// Check if allocation data exists
if (!isset($_SESSION['allocation']) || !isset($_SESSION['lecturers'])) {
    die("No allocation data found. Please allocate courses first.");
}

$allocation = $_SESSION['allocation'];
$lecturers = $_SESSION['lecturers'];

// Define variables that are missing in your code
$totalStudents = 0;
foreach ($allocation as $lecturer_id => $allocated_students) {
    $totalStudents += count($allocated_students);
}

// Define these variables if they don't exist
$current_semester = $_SESSION['current_semester'] ?? 'Current Semester';
$current_academic_year = $_SESSION['current_academic_year'] ?? date('Y') . '/' . (date('Y') + 1);
$exemptedLecturers = $_SESSION['exemptedLecturers'] ?? [];

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .card-header { background-color: #f8f9fa; }
        .expertise { font-style: italic; color: #6c757d; }
        .student-interest { font-weight: bold; color: #28a745; }
        .card { opacity: 0; transition: opacity 0.5s ease-in-out; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Allocation Results</h1>
        <div class="row mb-4">
            <div class="col-md-6">
                <a href="allocate_students.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Allocation</a>
            </div>
            <div class="col-md-6 text-end">
                <a href="download.php" class="btn btn-success"><i class="fas fa-download"></i> Download Results</a>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col">
                <h2 class="display-6 mb-0">
                    <i class="fas fa-people-fill me-2"></i>
                    Supervisor Allocation Results
                </h2>
                <p class="text-muted mt-2">
                    <?php echo htmlspecialchars($current_semester); ?>, <?php echo htmlspecialchars($current_academic_year); ?> • 
                    <?php echo count($allocation); ?> active supervisors • 
                    <?php echo $totalStudents; ?> students allocated
                    <?php if (count($exemptedLecturers) > 0): ?>
                        • <?php echo count($exemptedLecturers); ?> exempted supervisors
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <div class="row" id="allocation-results">
            <?php if (empty($allocation)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No allocation data available.</div>
                </div>
            <?php else: ?>
                <?php foreach ($allocation as $lecturer_id => $allocated_students): ?>
                    <?php 
                    // Find the lecturer by ID
                    $lecturer = null;
                    foreach ($lecturers as $l) {
                        if (isset($l['id']) && $l['id'] == $lecturer_id) {
                            $lecturer = $l;
                            break;
                        }
                    }
                    
                    // Skip if lecturer not found
                    if (!$lecturer) continue;
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo htmlspecialchars($lecturer['name'] ?? 'Unknown'); ?></h5>
                                <small class="expertise">Research Area: <?php echo htmlspecialchars($lecturer['expertise_areas'] ?? 'Not specified'); ?></small>
                            </div>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($allocated_students)): ?>
                                    <li class="list-group-item text-center text-muted">No students allocated</li>
                                <?php else: ?>
                                    <?php foreach ($allocated_students as $student): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['name'] ?? 'Unknown'); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['reg_no'] ?? 'No ID'); ?></small>
                                                </div>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo count($allocated_students); ?>
                                                </span>
                                            </div>
                                            <small class="student-interest">Interest: <?php echo htmlspecialchars($student['interest'] ?? 'Not specified'); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Course and Supervisor Matching System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            let delay = 0;
            
            cards.forEach((card) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                }, delay);
                delay += 100; // Reduced delay for smoother animation
            });
        });
    </script>
</body>
</html>

