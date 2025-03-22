<?php
// Include database connection
include_once('../db_connection.php');

include 'navbar.php';
include 'sidebar.php';

// Fetch lecturers with their expertise
function fetchLecturers($conn) {
    $query = "
        SELECT 
            lecturers.id AS lecturer_id,
            lecturers.name AS lecturer_name,
            lecturers.email AS lecturer_email,
            GROUP_CONCAT(expertise.name SEPARATOR ', ') AS expertise_areas
        FROM lecturer_expertise
        JOIN lecturers ON lecturer_expertise.lecturer_id = lecturers.id
        JOIN expertise ON lecturer_expertise.expertise_id = expertise.id
        GROUP BY lecturers.id
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch student information
function fetchStudents($conn) {
    $query = "
        SELECT 
            id AS student_id, 
            name AS student_name, 
            reg_no AS registration_number, 
            interest_id AS interest_area
        FROM student
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch course information
function fetchCourses($conn) {
    $query = "
        SELECT 
            c.id AS course_id,
            c.title AS course_title,
            c.course_code,
            c.credit_load,
            cat.name AS category_name
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        ORDER BY c.title
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

$lecturers = fetchLecturers($conn);
$students = fetchStudents($conn);
$courses = fetchCourses($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
       .container {
        margin-left: 250px; /* sidebar width (250px) + extra spacing (20px) */
        width: calc(100% - 270px); /* Adjust container width to account for sidebar */
        padding: 20px;
    }

    .dashboard-header {
        margin-left: 250px; /* Match sidebar width */
        width: calc(100% - 250px);
        position: relative;
    }

    /* Ensure the body doesn't have any default margins */
    body {
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
    }

    /* Adjust sidebar z-index to ensure it stays on top */
    .sidebar {
        z-index: 1030;
    }

    /* Add responsive adjustments */
    @media (max-width: 768px) {
        .container,
        .dashboard-header {
            margin-left: 0;
            width: 100%;
        }
    }
    </style>
</head>
<body>
    <div class="mt-5">
        <div class="container ">
            <!-- <h1 class="text-center text-white bg-primary"><i class="fas fa-tachometer-alt"></i> Super Admin Dashboard</h1> -->
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-tie"></i> Total Lecturers</h5>
                        <p class="card-text display-4"><?php echo count($lecturers); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-graduate"></i> Total Students</h5>
                        <p class="card-text display-4"><?php echo count($students); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-book"></i> Total Courses</h5>
                        <p class="card-text display-4"><?php echo count($courses); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lecturers Table -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-chalkboard-teacher"></i> Lecturers and Their Areas of Expertise</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Lecturer Name</th>
                                <th>Email</th>
                                <th>Areas of Expertise</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lecturers as $index => $lecturer): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($lecturer['lecturer_name']) ?></td>
                                    <td><?= htmlspecialchars($lecturer['lecturer_email']) ?></td>
                                    <td><?= htmlspecialchars($lecturer['expertise_areas']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h3><i class="fas fa-user-graduate"></i> Student Information</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Registration Number</th>
                                <th>Interest Area ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                                    <td><?= htmlspecialchars($student['registration_number']) ?></td>
                                    <td><?= htmlspecialchars($student['interest_area']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Courses Table -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h3><i class="fas fa-book"></i> Course Information</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Course Title</th>
                                <th>Course Code</th>
                                <th>Credit Load</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $index => $course): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($course['course_title']) ?></td>
                                    <td><?= htmlspecialchars($course['course_code']) ?></td>
                                    <td><?= htmlspecialchars($course['credit_load']) ?></td>
                                    <td><?= htmlspecialchars($course['category_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>