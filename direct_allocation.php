<?php
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';
require('../fpdf/fpdf.php');

// Handle deletion
if (isset($_POST['delete_allocation'])) {
    $allocation_id = $_POST['allocation_id'];
    $delete_sql = "DELETE FROM manual_course_allocations WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $allocation_id);
    
    if ($delete_stmt->execute()) {
        $success_message = "Allocation deleted successfully!";
    } else {
        $error_message = "Error deleting allocation: " . $conn->error;
    }
}

// Handle new allocation
if (isset($_POST['allocate_course'])) {
    $lecturer_id = $_POST['lecturer_id'];
    $course_id = $_POST['course_id'];
    
    // Check if lecturer is exempted
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
    
    $exemption_check = "SELECT id FROM lecturer_exemptions 
                        WHERE lecturer_id = ? 
                        AND status = 'approved' 
                        AND academic_year = ? 
                        AND semester = ?";
    $check_stmt = $conn->prepare($exemption_check);
    $check_stmt->bind_param("iss", $lecturer_id, $current_academic_year, $current_semester);
    $check_stmt->execute();
    $exemption_result = $check_stmt->get_result();
    
    if ($exemption_result->num_rows > 0) {
        $error_message = "This lecturer is exempted for the current semester and cannot be allocated courses!";
    } else {
        // Check if allocation already exists
        $check_sql = "SELECT id FROM manual_course_allocations WHERE lecturer_id = ? AND course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $lecturer_id, $course_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This course is already allocated to this lecturer!";
        } else {
            // Insert without academic_year and semester since those columns don't exist
            $sql = "INSERT INTO manual_course_allocations (lecturer_id, course_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $lecturer_id, $course_id);
            
            if ($stmt->execute()) {
                $success_message = "Course allocated successfully!";
            } else {
                $error_message = "Error allocating course: " . $conn->error;
            }
        }
    }
}

// Get current academic year and semester
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

// Fetch active lecturers (excluding exempted ones)
$lecturers_query = "
    SELECT 
        l.id, 
        l.name, 
        GROUP_CONCAT(e.name SEPARATOR ', ') AS expertise,
        CASE WHEN ex.id IS NULL THEN 0 ELSE 1 END AS is_exempted
    FROM 
        lecturers l
    LEFT JOIN 
        lecturer_expertise le ON l.id = le.lecturer_id
    LEFT JOIN 
        expertise e ON le.expertise_id = e.id
    LEFT JOIN
        lecturer_exemptions ex ON l.id = ex.lecturer_id AND ex.status = 'approved' 
                               AND ex.academic_year = ? AND ex.semester = ?
    GROUP BY 
        l.id
    ORDER BY 
        l.name
";
$stmt = $conn->prepare($lecturers_query);
$stmt->bind_param("ss", $current_academic_year, $current_semester);
$stmt->execute();
$lecturers = $stmt->get_result();

// Fetch exempted lecturers for display
$exempted_query = "
    SELECT 
        l.id, 
        l.name,
        ex.reason
    FROM 
        lecturers l
    JOIN
        lecturer_exemptions ex ON l.id = ex.lecturer_id
    WHERE
        ex.status = 'approved' AND ex.academic_year = ? AND ex.semester = ?
    ORDER BY 
        l.name
";
$stmt = $conn->prepare($exempted_query);
$stmt->bind_param("ss", $current_academic_year, $current_semester);
$stmt->execute();
$exempted_lecturers = $stmt->get_result();

// Fetch courses with description and category
$courses_query = "
    SELECT 
        c.id, 
        c.title, 
        c.course_code, 
        c.description, 
        c.credit_load,
        cat.name AS category 
    FROM 
        courses c
    LEFT JOIN 
        categories cat ON c.category_id = cat.id
    ORDER BY 
        c.title
";
$courses = $conn->query($courses_query);

// Store courses data in an array for JavaScript
$courses_data = array();
while ($course = $courses->fetch_assoc()) {
    $courses_data[$course['id']] = array(
        'title' => $course['title'],
        'code' => $course['course_code'],
        'description' => $course['description'],
        'category' => $course['category'],
        'credit_load' => $course['credit_load']
    );
}
// Reset the courses result pointer
$courses->data_seek(0);

// Fetch current allocations - removed academic_year and semester from the query
$allocations_query = "
    SELECT 
        ca.id, 
        l.name as lecturer_name, 
        c.title as course_title, 
        c.course_code, 
        c.credit_load
    FROM 
        manual_course_allocations ca 
    JOIN 
        lecturers l ON ca.lecturer_id = l.id 
    JOIN 
        courses c ON ca.course_id = c.id 
    ORDER BY 
        l.name
";
$allocations = $conn->query($allocations_query);

// Count statistics
$total_allocations = $allocations->num_rows;
$exempted_count = $exempted_lecturers->num_rows;
$active_count = $lecturers->num_rows - $exempted_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Allocation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            margin-left: 18%;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 2px solid #f0f0f0;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
        }
        .delete-btn:hover {
            color: #bd2130;
        }
        .search-box {
            margin-bottom: 1rem;
        }
        #courseDetails {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.25rem;
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
        .exempted-option {
            background-color: #ffeeee;
            color: #dc3545;
            font-style: italic;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.75em;
            text-transform: uppercase;
        }
        .status-active {
            background-color: #198754;
            color: #fff;
        }
        .status-exempted {
            background-color: #dc3545;
            color: #fff;
        }
        .stats-card {
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card .number {
            font-size: 2rem;
            font-weight: 700;
        }
        .stats-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .exempted-list {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h2 class="display-6 mb-0">
                    <i class="bi bi-calendar2-check me-2"></i>
                    Course Allocation System
                </h2>
                <p class="text-muted mt-2">Manage course allocations for lecturers</p>
                <div class="btn-group">
                    <button class="btn btn-primary" id="downloadPdfBtn">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Download Course Allocation
                    </button>
                    <a href="statistics.php" class="btn btn-success ms-2">
                        <i class="bi bi-bar-chart-line me-2"></i>View Statistics
                    </a>
                    
                </div>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Current Allocation Period:</strong> <?php echo $current_semester; ?>, <?php echo $current_academic_year; ?>
                    <br>
                    <span class="small">Only active lecturers can be allocated courses. Exempted lecturers are excluded from allocation.</span>
                </div>
            </div>
        </div> -->

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card bg-light">
                    <div class="number text-primary"><?php echo $active_count; ?></div>
                    <div class="label">Active Lecturers</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card bg-danger bg-opacity-25">
                    <div class="number text-danger"><?php echo $exempted_count; ?></div>
                    <div class="label">Exempted Lecturers</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card bg-success bg-opacity-25">
                    <div class="number text-success"><?php echo $total_allocations; ?></div>
                    <div class="label">Total Allocations</div>
                </div>
            </div>
        </div>

        <?php if ($exempted_count > 0): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#exemptedList">
                    <i class="bi bi-eye me-2"></i>
                    View <?php echo $exempted_count; ?> Exempted Lecturers
                </button>
                
                <div class="collapse mt-2" id="exemptedList">
                    <div class="card card-body exempted-list">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Lecturer</th>
                                        <th>Reason for Exemption</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($exempted = $exempted_lecturers->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-person-x text-danger me-2"></i>
                                            <?php echo htmlspecialchars($exempted['name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($exempted['reason']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-plus-circle me-2"></i>
                            New Allocation
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="lecturer_id" class="form-label">Select Lecturer</label>
                                <select class="form-select" name="lecturer_id" id="lecturer_id" required>
                                    <option value="">Choose lecturer...</option>
                                    <?php 
                                    // Reset pointer
                                    $lecturers->data_seek(0);
                                    while ($lecturer = $lecturers->fetch_assoc()): 
                                        $is_exempted = $lecturer['is_exempted'] == 1;
                                        $option_class = $is_exempted ? 'exempted-option' : '';
                                        $disabled = $is_exempted ? 'disabled' : '';
                                    ?>
                                        <option value="<?php echo $lecturer['id']; ?>" 
                                                data-expertise="<?php echo htmlspecialchars($lecturer['expertise']); ?>"
                                                data-exempted="<?php echo $is_exempted ? '1' : '0'; ?>"
                                                class="<?php echo $option_class; ?>"
                                                <?php echo $disabled; ?>>
                                            <?php echo htmlspecialchars($lecturer['name']); ?>
                                            <?php if ($is_exempted): ?> (Exempted)<?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div id="exemptionWarning" class="form-text text-danger" style="display: none;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This lecturer is exempted and cannot be allocated courses.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lecturer Research Area</label>
                                <p id="lecturerExpertise" class="form-text"></p>
                            </div>
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Select Course</label>
                                <select class="form-select" name="course_id" id="course_id" required>
                                    <option value="">Choose course...</option>
                                    <?php while ($course = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Course Details Section -->
                            <div id="courseDetails" class="mb-3">
                                <h6 class="fw-bold">Course Details</h6>
                                <div class="mb-2">
                                    <span class="fw-semibold">Category:</span> 
                                    <span id="courseCategory" class="badge bg-secondary ms-1"></span>
                                </div>
                                <div class="mb-2">
                                    <span class="fw-semibold">Credit Load:</span> 
                                    <span id="courseCreditLoad"></span>
                                </div>
                                <div>
                                    <span class="fw-semibold">Description:</span>
                                    <p id="courseDescription" class="mt-1 mb-0 small"></p>
                                </div>
                            </div>
                            
                            <button type="submit" name="allocate_course" class="btn btn-primary w-100" id="allocateBtn">
                                <i class="bi bi-plus-circle me-2"></i>
                                Allocate Course
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            Current Allocations
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="search-box">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search allocations...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="allocationsTable">
                                <thead>
                                    <tr>
                                        <th>Lecturer</th>
                                        <th>Course</th>
                                        <th>Code</th>
                                        <th>Credit Load</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($allocations->num_rows == 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No allocations found</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php while ($allocation = $allocations->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allocation['lecturer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($allocation['course_title']); ?></td>
                                                <td><?php echo htmlspecialchars($allocation['course_code']); ?></td>
                                                <td><?php echo htmlspecialchars($allocation['credit_load']); ?></td>
                                                <td>
                                                    <button class="btn btn-link text-danger p-0" 
                                                            onclick="confirmDelete(<?php echo $allocation['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this allocation?
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" name="allocation_id" id="deleteAllocationId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_allocation" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Course data from PHP
        const coursesData = <?php echo json_encode($courses_data); ?>;
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchQuery = this.value.toLowerCase();
            let table = document.getElementById('allocationsTable');
            let rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                let row = rows[i];
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchQuery) ? '' : 'none';
            }
        });

        // Delete confirmation
        function confirmDelete(allocationId) {
            document.getElementById('deleteAllocationId').value = allocationId;
            let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            let alerts = document.getElementsByClassName('alert-dismissible');
            for (let alert of alerts) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);

        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            window.location.href = 'course_download.php';
        });

        // Display lecturer expertise and check exemption status
        document.getElementById('lecturer_id').addEventListener('change', function() {
            let selectedOption = this.options[this.selectedIndex];
            let expertise = selectedOption.getAttribute('data-expertise');
            let isExempted = selectedOption.getAttribute('data-exempted') === '1';
            
            document.getElementById('lecturerExpertise').textContent = expertise || 'No expertise specified';
            
            // Show warning if lecturer is exempted
            const exemptionWarning = document.getElementById('exemptionWarning');
            const allocateBtn = document.getElementById('allocateBtn');
            
            if (isExempted) {
                exemptionWarning.style.display = 'block';
                allocateBtn.disabled = true;
            } else {
                exemptionWarning.style.display = 'none';
                allocateBtn.disabled = false;
            }
        });
        
        // Display course details when a course is selected
        document.getElementById('course_id').addEventListener('change', function() {
            const courseId = this.value;
            const courseDetailsDiv = document.getElementById('courseDetails');
            
            if (courseId && coursesData[courseId]) {
                // Get course data
                const course = coursesData[courseId];
                
                // Update the UI with course details
                document.getElementById('courseCategory').textContent = course.category || 'Not categorized';
                document.getElementById('courseCreditLoad').textContent = course.credit_load || 'N/A';
                document.getElementById('courseDescription').textContent = course.description || 'No description available';
                
                // Show the course details section
                courseDetailsDiv.style.display = 'block';
            } else {
                // Hide the course details section if no course is selected
                courseDetailsDiv.style.display = 'none';
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
<?php include '../footer_small.php'?>
</html>

