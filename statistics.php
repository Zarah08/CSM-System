<?php
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Fetch statistics data
// 1. Total allocations
$total_query = "SELECT COUNT(*) as total FROM manual_course_allocations";
$total_result = $conn->query($total_query);
$total_allocations = $total_result->fetch_assoc()['total'];

// 2. Courses per lecturer
$lecturer_load_query = "
    SELECT 
        l.name as lecturer_name,
        COUNT(ca.id) as course_count,
        SUM(c.credit_load) as total_credit_load
    FROM 
        lecturers l
    LEFT JOIN 
        manual_course_allocations ca ON l.id = ca.lecturer_id
    LEFT JOIN 
        courses c ON ca.course_id = c.id
    GROUP BY 
        l.id
    ORDER BY 
        total_credit_load DESC
";
$lecturer_load_result = $conn->query($lecturer_load_query);

// 3. Courses by category
$category_query = "
    SELECT 
        IFNULL(cat.name, 'Uncategorized') as category_name,
        COUNT(ca.id) as allocation_count
    FROM 
        manual_course_allocations ca
    JOIN 
        courses c ON ca.course_id = c.id
    LEFT JOIN 
        categories cat ON c.category_id = cat.id
    GROUP BY 
        cat.name
    ORDER BY 
        allocation_count DESC
";
$category_result = $conn->query($category_query);

// 4. Most allocated courses
$popular_courses_query = "
    SELECT 
        c.course_code,
        c.title,
        COUNT(ca.id) as allocation_count
    FROM 
        courses c
    JOIN 
        manual_course_allocations ca ON c.id = ca.course_id
    GROUP BY 
        c.id
    ORDER BY 
        allocation_count DESC
    LIMIT 5
";
$popular_courses_result = $conn->query($popular_courses_query);

// After the existing queries, add these new queries for unallocated resources

// 5. Unallocated courses
$unallocated_courses_query = "
    SELECT 
        c.id,
        c.course_code,
        c.title,
        c.credit_load,
        IFNULL(cat.name, 'Uncategorized') as category_name
    FROM 
        courses c
    LEFT JOIN 
        manual_course_allocations ca ON c.id = ca.course_id
    LEFT JOIN 
        categories cat ON c.category_id = cat.id
    WHERE 
        ca.id IS NULL
    ORDER BY 
        c.course_code
";
$unallocated_courses_result = $conn->query($unallocated_courses_query);
$unallocated_courses_count = $unallocated_courses_result->num_rows;

// 6. Unallocated lecturers
$unallocated_lecturers_query = "
    SELECT 
        l.id,
        l.name,
        GROUP_CONCAT(e.name SEPARATOR ', ') AS expertise
    FROM 
        lecturers l
    LEFT JOIN 
        manual_course_allocations ca ON l.id = ca.lecturer_id
    LEFT JOIN 
        lecturer_expertise le ON l.id = le.lecturer_id
    LEFT JOIN 
        expertise e ON le.expertise_id = e.id
    WHERE 
        ca.id IS NULL
    GROUP BY 
        l.id
    ORDER BY 
        l.name
";
$unallocated_lecturers_result = $conn->query($unallocated_lecturers_query);
$unallocated_lecturers_count = $unallocated_lecturers_result->num_rows;

// Prepare data for charts
$lecturer_labels = [];
$lecturer_data = [];
$lecturer_colors = [];

while ($row = $lecturer_load_result->fetch_assoc()) {
    $lecturer_labels[] = $row['lecturer_name'];
    $lecturer_data[] = $row['total_credit_load'];
    // Generate random colors for the chart
    $lecturer_colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

// Reset pointer for table display
$lecturer_load_result->data_seek(0);

// Category data for pie chart
$category_labels = [];
$category_data = [];
$category_colors = [];

while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['category_name'];
    $category_data[] = $row['allocation_count'];
    $category_colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

// Reset pointer for table display
$category_result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation Statistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Add quick action buttons to allocate unallocated resources -->
        <!-- Add this after the Back to Allocations button in the header section -->

        <div class="row mb-4">
            <div class="col">
                <h2 class="display-6 mb-0">
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Allocation Statistics
                </h2>
                <p class="text-muted mt-2">Overview and analysis of course allocations</p>
                <div class="btn-group">
                    <a href="direct_allocation.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Allocations
                    </a>
                    <?php if ($unallocated_courses_count > 0 || $unallocated_lecturers_count > 0): ?>
                        <a href="manual_course_allocation.php" class="btn btn-warning ms-2">
                            <i class="bi bi-plus-circle me-2"></i>Allocate Courses
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="stats-number"><?php echo $total_allocations; ?></div>
                    <div class="stats-label">Total Allocations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="stats-number"><?php echo $lecturer_load_result->num_rows; ?></div>
                    <div class="stats-label">Lecturers with Allocations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="stats-number"><?php echo $unallocated_courses_count; ?></div>
                    <div class="stats-label">Unallocated Courses</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="stats-number"><?php echo $unallocated_lecturers_count; ?></div>
                    <div class="stats-label">Unallocated Lecturers</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bar-chart me-2"></i>
                            Credit Load by Lecturer
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="lecturerChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pie-chart me-2"></i>
                            Allocations by Category
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Tables Row -->
        <!-- <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-lines-fill me-2"></i>
                            Lecturer Workload
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lecturer</th>
                                        <th>Courses</th>
                                        <th>Credit Load</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $lecturer_load_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['lecturer_name']); ?></td>
                                            <td><?php echo $row['course_count']; ?></td>
                                            <td><?php echo $row['total_credit_load'] ?: 0; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-book me-2"></i>
                            Most Allocated Courses
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Title</th>
                                        <th>Allocations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $popular_courses_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo $row['allocation_count']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Unallocated Resources Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-book-half me-2"></i>
                            Unallocated Courses
                            <span class="badge bg-secondary ms-2"><?php echo $unallocated_courses_count; ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($unallocated_courses_count > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Title</th>
                                            <th>Credit Load</th>
                                            <th>Category</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $unallocated_courses_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                <td><?php echo $row['credit_load']; ?></td>
                                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                All courses have been allocated to lecturers.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-x me-2"></i>
                            Unallocated Lecturers
                            <span class="badge bg-secondary ms-2"><?php echo $unallocated_lecturers_count; ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($unallocated_lecturers_count > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Expertise</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $unallocated_lecturers_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['expertise'] ?: 'None specified'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                All lecturers have been assigned courses.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Lecturer Chart
        const lecturerCtx = document.getElementById('lecturerChart').getContext('2d');
        new Chart(lecturerCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($lecturer_labels); ?>,
                datasets: [{
                    label: 'Total Credit Load',
                    data: <?php echo json_encode($lecturer_data); ?>,
                    backgroundColor: <?php echo json_encode($lecturer_colors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Credit Load'
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: <?php echo json_encode($category_colors); ?>,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>
<?php include '../footer_small.php'?>
</html>

