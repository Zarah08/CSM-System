<?php
// session_start();
include_once('../db_connection.php');
include 'navbar.php';
require('../fpdf/fpdf.php');


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$query = "SELECT 
    l.id AS lecturer_id,
    l.name AS lecturer_name,
    c.id AS course_id,
    c.title AS course_title,
    c.course_code,
    (SELECT COUNT(*) 
     FROM lecturer_course_allocation lca2 
     WHERE lca2.lecturer_id = l.id) as course_count
FROM lecturers l
INNER JOIN lecturer_course_allocation lca ON l.id = lca.lecturer_id
INNER JOIN courses c ON lca.course_id = c.id
ORDER BY l.name, c.course_code";


$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}


$lecturerQuery = "SELECT id, name FROM lecturers";
$lecturers = $conn->query($lecturerQuery)->fetch_all(MYSQLI_ASSOC);


$allocation = [];


if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lecturerId = $row['lecturer_id'];
        if (!isset($allocation[$lecturerId])) {
            $allocation[$lecturerId] = [
                'name' => $row['lecturer_name'],
                'course_count' => $row['course_count'],
                'courses' => []
            ];
        }
        $allocation[$lecturerId]['courses'][] = [
            'course_id' => $row['course_id'],
            'course_title' => $row['course_title'],
            'course_code' => $row['course_code']
        ];
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_course'])) {
    $course_id = $_POST['course_id'];
    $lecturer_id = $_POST['lecturer_id'];
    $share_lecturer_id = $_POST['share_lecturer_id'];

    if ($share_lecturer_id == $lecturer_id) {
        echo "<script>alert('Error: Cannot share course with the same lecturer!'); window.history.back();</script>";
        exit();
    }

    $insertQuery = "INSERT INTO lecturer_course_allocation (lecturer_id, course_id)
                    VALUES (?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ii", $share_lecturer_id, $course_id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=share");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_course'])) {
    $course_id = $_POST['course_id'];
    $old_lecturer_id = $_POST['old_lecturer_id'];
    $new_lecturer_id = $_POST['new_lecturer_id'];

   
    $deleteQuery = "DELETE FROM lecturer_course_allocation WHERE lecturer_id = ? AND course_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $old_lecturer_id, $course_id);
    $stmt->execute();


    $insertQuery = "INSERT INTO lecturer_course_allocation (lecturer_id, course_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ii", $new_lecturer_id, $course_id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=reassign");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_allocation'])) {
    $course_id = $_POST['course_id'];
    $lecturer_id = $_POST['lecturer_id'];

    $deleteQuery = "DELETE FROM lecturer_course_allocation WHERE lecturer_id = ? AND course_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $lecturer_id, $course_id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF'] . "?success=remove");
    exit();
}

// Prepare data for statistics and charts
$total_courses = 0;
$total_lecturers = count($allocation);
$courses_per_lecturer = [];

foreach ($allocation as $lecturerId => $data) {
    $course_count = $data['course_count'];
    $total_courses += $course_count;
    $courses_per_lecturer[$data['name']] = $course_count;
}
$avg_courses_per_lecturer = $total_lecturers > 0 ? round($total_courses / $total_lecturers, 1) : 0;

// Generate chart colors
function generateColor() {
    return sprintf("rgb(%d, %d, %d)", rand(100, 255), rand(100, 255), rand(100, 255));
}
$chart_data = [];
foreach ($courses_per_lecturer as $lecturer => $count) {
    $chart_data[] = [
        'lecturer' => $lecturer,
        'count' => $count,
        'color' => generateColor()
    ];
}
// require('../fpdf/fpdf.php'); // Make sure this path is correct

if (isset($_GET['download_pdf'])) {
    ob_end_clean(); // Clears any previously output content
    ob_start(); // Starts output buffering

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, 'Course Allocation Report', 0, 1, 'C');
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            $this->Ln(10);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

    // Create PDF
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    foreach ($allocation as $lecturerId => $data) {
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Lecturer: ' . $data['name'], 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Total Courses: ' . $data['course_count'], 0, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(90, 10, 'Course Title', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Course Code', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 11);
        foreach ($data['courses'] as $course) {
            $pdf->Cell(90, 10, $course['course_title'], 1, 0, 'L');
            $pdf->Cell(30, 10, $course['course_code'], 1, 1, 'C');
        }

        $pdf->Ln(15);
    }

    ob_end_clean(); // Ensure no previous output
    $pdf->Output('D', 'course_allocation.pdf');
    exit();
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Allocation Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
        .btn-group-sm > .btn, .btn-sm {
            margin: 0 2px;
        }
        #statsContent {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="dashboard.php" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            <h2>Course Allocation Results</h2>
            <div>
                
                <a href="?download_pdf=1" class="btn btn-primary">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </a>
                <button id="toggleStats" class="btn btn-primary">
                    <i class="bi bi-bar-chart-fill me-2"></i> Show Statistics
                </button>
            </div>
        </div>

       
        <div id="statisticsSection" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- <h3>Statistics</h3> -->
                
                
            </div>
            <div id="statsContent" style="display: none;">
            <h3>Statistics</h3>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card ">
                            <div class="card-body">
                                <h5 class="card-title">Total Courses</h5>
                                <p class="card-text display-4"><?php echo $total_courses; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card ">
                            <div class="card-body">
                                <h5 class="card-title">Total Lecturers</h5>
                                <p class="card-text display-4"><?php echo $total_lecturers; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card ">
                            <div class="card-body">
                                <h5 class="card-title">Avg. Courses per Lecturer</h5>
                                <p class="card-text display-4"><?php echo $avg_courses_per_lecturer; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Courses per Lecturer</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="coursesPerLecturerChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Allocation Distribution</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="allocationDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                $message = match($_GET['success']) {
                    'share' => 'Course shared successfully!',
                    'reassign' => 'Course reassigned successfully!',
                    'remove' => 'Course removed successfully!',
                    default => 'Operation completed successfully!'
                };
                echo $message;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($allocation)): ?>
            <div class="alert alert-info">
                No course allocations found.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Lecturer Name</th>
                                    <th>Course Title</th>
                                    <th>Course Code</th>
                                    <th>Course count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocation as $lecturerId => $data): ?>
                                    <?php foreach ($data['courses'] as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['name']); ?></td>
                                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                            <td><?php echo (int)$data['course_count']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#shareModal" 
                                                        data-course-id="<?php echo (int)$course['course_id']; ?>" 
                                                        data-lecturer-id="<?php echo (int)$lecturerId; ?>">
                                                    <i class="bi bi-share"></i> Share
                                                </button>
                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#reassignModal"
                                                        data-course-id="<?php echo (int)$course['course_id']; ?>"
                                                        data-lecturer-id="<?php echo (int)$lecturerId; ?>">
                                                    <i class="bi bi-arrow-left-right"></i> Reassign
                                                </button>
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#removeModal"
                                                        data-course-id="<?php echo (int)$course['course_id']; ?>"
                                                        data-lecturer-id="<?php echo (int)$lecturerId; ?>">
                                                    <i class="bi bi-x-circle"></i> Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <select name="share_lecturer_id" class="form-select" required>
                            <option value="">Select lecturer</option>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <option value="<?php echo (int)$lecturer['id']; ?>">
                                    <?php echo htmlspecialchars($lecturer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="course_id" id="shareCourseId">
                        <input type="hidden" name="lecturer_id" id="shareLecturerId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="share_course" class="btn btn-primary">Share Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="reassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reassign Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <select name="new_lecturer_id" class="form-select" required>
                            <option value="">Select new lecturer</option>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <option value="<?php echo (int)$lecturer['id']; ?>">
                                    <?php echo htmlspecialchars($lecturer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="course_id" id="reassignCourseId">
                        <input type="hidden" name="old_lecturer_id" id="reassignLecturerId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reassign_course" class="btn btn-primary">Reassign Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Remove Modal -->
    <div class="modal fade" id="removeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove Course Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to remove this course allocation?</p>
                        <input type="hidden" name="course_id" id="removeCourseId">
                        <input type="hidden" name="lecturer_id" id="removeLecturerId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="remove_allocation" class="btn btn-danger">Remove</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center mt-5 py-3">
        <p>&copy; <?php echo date('Y'); ?> Course and Supervisor Matching System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            const toggleBtn = document.getElementById('toggleStats');
            const statsContent = document.getElementById('statsContent');

            toggleBtn.addEventListener('click', function() {
                if (statsContent.style.display === 'none') {
                    statsContent.style.display = 'block';
                    toggleBtn.textContent = 'Hide Statistics';
                    
                    initializeCharts();
                } else {
                    statsContent.style.display = 'none';
                    toggleBtn.textContent = 'Show Statistics';
                }
            });

            // Function to initialize charts
            function initializeCharts() {
                
                const coursesPerLecturerCtx = document.getElementById('coursesPerLecturerChart').getContext('2d');
                const allocationDistributionCtx = document.getElementById('allocationDistributionChart').getContext('2d');

                
                const chartData = <?php echo json_encode($chart_data); ?>;
                const labels = chartData.map(item => item.lecturer);
                const data = chartData.map(item => item.count);
                const colors = chartData.map(item => item.color);

                // Courses per Lecturer Bar Chart
                new Chart(coursesPerLecturerCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Courses',
                            data: data,
                            backgroundColor: colors,
                            borderColor: colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Allocation Distribution Pie Chart
                new Chart(allocationDistributionCtx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderColor: colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Course Allocation Distribution'
                            }
                        }
                    }
                });
            }
            document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleStats');
            const statsContent = document.getElementById('statsContent');
            const btnIcon = toggleBtn.querySelector('i');
            const btnText = toggleBtn.lastChild;

            toggleBtn.addEventListener('click', function() {
                if (statsContent.style.display === 'none') {
                    statsContent.style.display = 'block';
                    btnIcon.classList.remove('bi-eye-fill');
                    btnIcon.classList.add('bi-eye-slash-fill');
                    btnText.textContent = ' Hide Statistics';
                    initializeCharts();
                } else {
                    statsContent.style.display = 'none';
                    btnIcon.classList.remove('bi-eye-slash-fill');
                    btnIcon.classList.add('bi-eye-fill');
                    btnText.textContent = ' Show Statistics';
                }
            });

            function initializeCharts() {
                // Your existing chart initialization code
            }

            // Rest of your existing JavaScript
        });

            // Modal handling
            const shareModal = document.getElementById('shareModal');
            shareModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const courseId = button.getAttribute('data-course-id');
                const lecturerId = button.getAttribute('data-lecturer-id');
                document.getElementById('shareCourseId').value = courseId;
                document.getElementById('shareLecturerId').value = lecturerId;
            });

            const reassignModal = document.getElementById('reassignModal');
            reassignModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const courseId = button.getAttribute('data-course-id');
                const lecturerId = button.getAttribute('data-lecturer-id');
                document.getElementById('reassignCourseId').value = courseId;
                document.getElementById('reassignLecturerId').value = lecturerId;
            });

            const removeModal = document.getElementById('removeModal');
            removeModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const courseId = button.getAttribute('data-course-id');
                const lecturerId = button.getAttribute('data-lecturer-id');
                document.getElementById('removeCourseId').value = courseId;
                document.getElementById('removeLecturerId').value = lecturerId;
            });
        });
    </script>
</body>
</html>
