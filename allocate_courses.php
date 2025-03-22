<?php
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';
// Fetch lecturers
function fetchLecturers($conn) {
    $query = "SELECT l.id, l.name, l.email, 
    GROUP_CONCAT(DISTINCT e.name ORDER BY e.name ASC SEPARATOR ', ') AS expertise_names
FROM lecturers l
LEFT JOIN lecturer_expertise le ON l.id = le.lecturer_id
LEFT JOIN expertise e ON le.expertise_id = e.id
LEFT JOIN lecturer_exemptions lx ON l.id = lx.lecturer_id
WHERE lx.lecturer_id IS NULL  -- Exclude lecturers that exist in exemptions
GROUP BY l.id
ORDER BY l.name
";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}


// Fetch courses
function fetchCourses($conn) {
    $query = "SELECT id, title, course_code, description FROM courses ORDER BY title";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}


$lecturers = fetchLecturers($conn);
$courses = fetchCourses($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Allocation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            margin-left: 18%;
        }
        .card {
            transition: box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .btn-allocate {
            transition: all 0.3s ease;
        }
        .btn-allocate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .dropdown-menu {
            min-width: 5rem;
        }
        .dropdown-item {
            text-align: center;
        }
        /* Loader styles */
        .loader-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .loader {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .loader-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #ffffff;
            margin: 0 10px;
            animation: bounce 0.5s ease-in-out infinite;
        }
        .loader-circle:nth-child(2) {
            width: 30px;
            height: 30px;
            animation-delay: 0.1s;
        }
        .loader-circle:nth-child(3) {
            width: 40px;
            height: 40px;
            animation-delay: 0.2s;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .loader-text {
            color: #ffffff;
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-university me-2"></i>
                Course Matching System
            </a>
        </div>
    </nav>

    <!-- Loader -->
    <div class="loader-container">
        <div class="loader">
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
        </div>
        <div class="loader-text">Please wait while courses are being allocated...</div>
    </div>

    <div class="container my-5">
        <h1 class="text-center mb-5">
            <i class="fas fa-chalkboard-teacher me-2"></i>
            Allocate Courses to Lecturers
        </h1>

        <div class="row justify-content-center mb-5">
            <div class="col-md-6 text-center">
                <form action="allocate_c.php" method="POST" id="allocationForm">
                    <button type="submit" name="allocate" class="btn btn-primary btn-lg btn-allocate">
                        <i class="fas fa-play me-2"></i>
                        Start Matching
                    </button>
                </form>
            </div>
        </div>

        <div class="row">
    <!-- Lecturers List -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0">
                    <i class="fas fa-users me-2"></i>
                    Lecturers
                </h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($lecturers as $lecturer): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user me-2 text-primary"></i>
                                <a href="#" class="text-decoration-none fw-bold"
                                    onclick="showLecturerModal(<?php echo htmlspecialchars(json_encode($lecturer)); ?>)">
                                    <?php echo htmlspecialchars($lecturer['name']); ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($lecturer['expertise_names'] ?: 'No expertise'); ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill">More Info</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Courses List -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h2 class="h4 mb-0">
                    <i class="fas fa-book me-2"></i>
                    Courses
                </h2>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($courses as $course): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-book-open me-2 text-success"></i>
                                <a href="#" class="text-decoration-none fw-bold"
                                    onclick="showCourseModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?></small>
                            </div>
                            <span class="badge bg-success rounded-pill">More Info</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Lecturer Modal -->
<div class="modal fade" id="lecturerModal" tabindex="-1" aria-labelledby="lecturerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="lecturerModalLabel">Lecturer Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <span id="lecturer-name"></span></p>
                <p><strong>Email:</strong> <span id="lecturer-email"></span></p>
                <p><strong>Expertise:</strong> <span id="lecturer-expertise"></span></p>
            </div>
        </div>
    </div>
</div>

<!-- Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="courseModalLabel">Course Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Course Title:</strong> <span id="course-title"></span></p>
                <p><strong>Course Code:</strong> <span id="course-code"></span></p>
                <p><strong>Description:</strong> <span id="course-description"></span></p>
            </div>
        </div>
    </div>
</div>


    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Course and Supervisor Matching System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      function showLecturerModal(lecturer) {
    document.getElementById('lecturer-name').textContent = lecturer.name;
    document.getElementById('lecturer-email').textContent = lecturer.email || 'Not Available';
    document.getElementById('lecturer-expertise').textContent = lecturer.expertise_names || 'No expertise';

    var lecturerModal = new bootstrap.Modal(document.getElementById('lecturerModal'));
    lecturerModal.show();
}

function showCourseModal(course) {
    document.getElementById('course-title').textContent = course.title;
    document.getElementById('course-code').textContent = course.course_code;
    document.getElementById('course-description').textContent = course.description || 'No description available';

    var courseModal = new bootstrap.Modal(document.getElementById('courseModal'));
    courseModal.show();
}


    </script>