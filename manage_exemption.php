<?php
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Check admin privileges
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
//     // Redirect to login page or show access denied message
//     header("Location: login.php?error=unauthorized");
//     exit();
// }

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle new exemption creation
    if (isset($_POST['create_exemption'])) {
        $lecturer_id = $_POST['lecturer_id'];
        $reason = $_POST['reason'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];
        $status = $_POST['status'];
        $admin_remarks = $_POST['admin_remarks'];
        
        $query = "INSERT INTO lecturer_exemptions (lecturer_id, reason, semester, academic_year, status, admin_remarks, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $lecturer_id, $reason, $semester, $academic_year, $status, $admin_remarks);
        
        if ($stmt->execute()) {
            $success_message = "Exemption created successfully!";
            
            // Log the action
            $admin_id = $_SESSION['user_id'];
            $action = "Created exemption for lecturer ID: $lecturer_id with status: $status";
            $log_query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("is", $admin_id, $action);
            $log_stmt->execute();
        } else {
            $error_message = "Error creating exemption: " . $conn->error;
        }
    }
    
    // Handle exemption status update
    if (isset($_POST['update_exemption'])) {
        $exemption_id = $_POST['exemption_id'];
        $status = $_POST['status'];
        $admin_remarks = $_POST['admin_remarks'];
        
        // Set the user_id for the trigger
        $admin_id = $_SESSION['user_id'];
        $conn->query("SET @user_id = $admin_id");
        
        $query = "UPDATE lecturer_exemptions 
                  SET status = ?, admin_remarks = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $admin_remarks, $exemption_id);
        
        if ($stmt->execute()) {
            $success_message = "Exemption status updated successfully!";
            
            // Log the action
            $action = "Updated exemption ID: $exemption_id to status: $status";
            $log_query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("is", $admin_id, $action);
            $log_stmt->execute();
            
            // If approved, check if we need to reassign courses
            if ($status == 'approved') {
                // Get the lecturer ID
                $lecturer_query = "SELECT lecturer_id FROM lecturer_exemptions WHERE id = ?";
                $lecturer_stmt = $conn->prepare($lecturer_query);
                $lecturer_stmt->bind_param("i", $exemption_id);
                $lecturer_stmt->execute();
                $result = $lecturer_stmt->get_result();
                $lecturer_id = $result->fetch_assoc()['lecturer_id'];
                
                // Check if lecturer has course allocations
                $allocation_query = "SELECT COUNT(*) as count FROM manual_course_allocations WHERE lecturer_id = ?";
                $allocation_stmt = $conn->prepare($allocation_query);
                $allocation_stmt->bind_param("i", $lecturer_id);
                $allocation_stmt->execute();
                $result = $allocation_stmt->get_result();
                $allocation_count = $result->fetch_assoc()['count'];
                
                if ($allocation_count > 0) {
                    $warning_message = "Lecturer has $allocation_count course allocations that may need reassignment.";
                }
            }
        } else {
            $error_message = "Error updating exemption status: " . $conn->error;
        }
    }
    
    
    // Handle exemption deletion
    if (isset($_POST['delete_exemption'])) {
        $exemption_id = $_POST['exemption_id'];
        
        // Get lecturer info before deletion for logging
        $lecturer_query = "SELECT lecturer_id FROM lecturer_exemptions WHERE id = ?";
        $lecturer_stmt = $conn->prepare($lecturer_query);
        $lecturer_stmt->bind_param("i", $exemption_id);
        $lecturer_stmt->execute();
        $result = $lecturer_stmt->get_result();
        $lecturer_id = $result->fetch_assoc()['lecturer_id'];
        
        $query = "DELETE FROM lecturer_exemptions WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $exemption_id);
        
        if ($stmt->execute()) {
            $success_message = "Exemption record deleted successfully!";
            
            // Log the action
            $admin_id = $_SESSION['user_id'];
            $action = "Deleted exemption ID: $exemption_id for lecturer ID: $lecturer_id";
            $log_query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("is", $admin_id, $action);
            $log_stmt->execute();
        } else {
            $error_message = "Error deleting exemption record: " . $conn->error;
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$academic_year_filter = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query with filters
$exemptions_query = "
    SELECT 
        le.id, 
        le.lecturer_id,
        l.name AS lecturer_name, 
        l.email AS lecturer_email,
        le.reason, 
        le.semester,
        le.academic_year,
        le.status, 
        le.admin_remarks,
        le.created_at,
        le.updated_at,
        GROUP_CONCAT(DISTINCT e.name SEPARATOR ', ') AS expertise_areas
    FROM 
        lecturer_exemptions le
    JOIN 
        lecturers l ON le.lecturer_id = l.id
    LEFT JOIN
        lecturer_expertise lexp ON l.id = lexp.lecturer_id
    LEFT JOIN
        expertise e ON lexp.expertise_id = e.id
    WHERE 1=1
";

// Add filters
if ($status_filter != 'all') {
    $exemptions_query .= " AND le.status = '$status_filter'";
}

if (!empty($semester_filter)) {
    $exemptions_query .= " AND le.semester = '$semester_filter'";
}

if (!empty($academic_year_filter)) {
    $exemptions_query .= " AND le.academic_year = '$academic_year_filter'";
}

if (!empty($search_term)) {
    $exemptions_query .= " AND (l.name LIKE '%$search_term%' OR l.email LIKE '%$search_term%' OR le.reason LIKE '%$search_term%')";
}

$exemptions_query .= "
    GROUP BY 
        le.id
    ORDER BY 
        CASE 
            WHEN le.status = 'pending' THEN 1
            WHEN le.status = 'approved' THEN 2
            WHEN le.status = 'rejected' THEN 3
        END,
        le.created_at DESC
";

$exemptions = $conn->query($exemptions_query);

// Fetch lecturers for dropdown
$lecturers_query = "
    SELECT 
        l.id, 
        l.name, 
        l.email,
        GROUP_CONCAT(DISTINCT e.name SEPARATOR ', ') AS expertise_areas
    FROM 
        lecturers l
    LEFT JOIN
        lecturer_expertise lexp ON l.id = lexp.lecturer_id
    LEFT JOIN
        expertise e ON lexp.expertise_id = e.id
    GROUP BY 
        l.id
    ORDER BY 
        l.name
";
$lecturers = $conn->query($lecturers_query);

// Get unique semesters and academic years for filters
$semesters_query = "SELECT DISTINCT semester FROM lecturer_exemptions ORDER BY 
    CASE 
        WHEN semester = 'First Semester' THEN 1
        WHEN semester = 'Second Semester' THEN 2
        WHEN semester = 'Summer' THEN 3
        ELSE 4
    END";
$semesters = $conn->query($semesters_query);

$academic_years_query = "SELECT DISTINCT academic_year FROM lecturer_exemptions ORDER BY academic_year DESC";
$academic_years = $conn->query($academic_years_query);

// Get exemption statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_exemptions,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM 
        lecturer_exemptions
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get current academic year and semester for default values
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Exemption Management</title>
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
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.75em;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .status-approved {
            background-color: #198754;
            color: #fff;
        }
        .status-rejected {
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
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .tooltip-inner {
            max-width: 300px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h2 class="display-6 mb-0">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    HOD Exemption Management
                </h2>
                <p class="text-muted mt-2">
                    Manage and process lecturer exemption requests
                </p>
            </div>
            <div class="col-auto">
                
               
                
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
        
        <?php if (isset($warning_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo $warning_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card bg-light">
                    <div class="number"><?php echo $stats['total_exemptions']; ?></div>
                    <div class="label">Total Exemptions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-warning bg-opacity-25">
                    <div class="number text-warning"><?php echo $stats['pending_count']; ?></div>
                    <div class="label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-success bg-opacity-25">
                    <div class="number text-success"><?php echo $stats['approved_count']; ?></div>
                    <div class="label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-danger bg-opacity-25">
                    <div class="number text-danger"><?php echo $stats['rejected_count']; ?></div>
                    <div class="label">Rejected</div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <!-- <div class="card mb-4">
            <div class="card-body filter-form">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" name="semester" id="semester">
                            <option value="">All Semesters</option>
                            <?php while ($semester = $semesters->fetch_assoc()): ?>
                                <option value="<?php echo $semester['semester']; ?>" <?php echo $semester_filter == $semester['semester'] ? 'selected' : ''; ?>>
                                    <?php echo $semester['semester']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <select class="form-select" name="academic_year" id="academic_year">
                            <option value="">All Years</option>
                            <?php while ($year = $academic_years->fetch_assoc()): ?>
                                <option value="<?php echo $year['academic_year']; ?>" <?php echo $academic_year_filter == $year['academic_year'] ? 'selected' : ''; ?>>
                                    <?php echo $year['academic_year']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" id="search" placeholder="Lecturer name, email..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form> -->
            </div>
        </div>
        
        <!-- Exemptions Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>
                    Exemption Requests
                </h5>
                <div>
                    <span class="badge bg-secondary"><?php echo $exemptions->num_rows; ?> results</span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($exemptions->num_rows > 0): ?>
                    <form id="bulkActionForm" method="POST" action="">
                        <div class="table-responsive">
                            <table class="table table-hover" id="exemptionsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Lecturer</th>
                                        <th>Semester</th>
                                        <th>Academic Year</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($exemption = $exemptions->fetch_assoc()): ?>
                                        <tr data-status="<?php echo $exemption['status']; ?>">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input exemption-checkbox" type="checkbox" name="selected_exemptions[]" value="<?php echo $exemption['id']; ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($exemption['lecturer_name']); ?></strong>
                                                </div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($exemption['lecturer_email']); ?></div>
                                                <?php if (!empty($exemption['expertise_areas'])): ?>
                                                <div class="small text-muted text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($exemption['expertise_areas']); ?>">
                                                    <i class="bi bi-book me-1"></i><?php echo htmlspecialchars($exemption['expertise_areas']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($exemption['semester']); ?></td>
                                            <td><?php echo htmlspecialchars($exemption['academic_year']); ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($exemption['reason']); ?>">
                                                    <?php echo htmlspecialchars($exemption['reason']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $exemption['status']; ?>">
                                                    <?php echo ucfirst($exemption['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div data-bs-toggle="tooltip" title="Created: <?php echo date('M d, Y H:i', strtotime($exemption['created_at'])); ?>">
                                                    <?php echo date('M d, Y', strtotime($exemption['created_at'])); ?>
                                                </div>
                                                <?php if ($exemption['updated_at']): ?>
                                                <div class="small text-muted" data-bs-toggle="tooltip" title="Updated: <?php echo date('M d, Y H:i', strtotime($exemption['updated_at'])); ?>">
                                                    Updated: <?php echo date('M d, Y', strtotime($exemption['updated_at'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-buttons">
                                                <button type="button" class="btn btn-sm btn-info view-details" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#exemptionDetailsModal"
                                                        data-id="<?php echo $exemption['id']; ?>"
                                                        data-lecturer="<?php echo htmlspecialchars($exemption['lecturer_name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($exemption['lecturer_email']); ?>"
                                                        data-expertise="<?php echo htmlspecialchars($exemption['expertise_areas']); ?>"
                                                        data-semester="<?php echo htmlspecialchars($exemption['semester']); ?>"
                                                        data-academic-year="<?php echo htmlspecialchars($exemption['academic_year']); ?>"
                                                        data-reason="<?php echo htmlspecialchars($exemption['reason']); ?>"
                                                        data-status="<?php echo $exemption['status']; ?>"
                                                        data-remarks="<?php echo htmlspecialchars($exemption['admin_remarks'] ?? ''); ?>"
                                                        data-created="<?php echo date('M d, Y H:i', strtotime($exemption['created_at'])); ?>"
                                                        data-updated="<?php echo $exemption['updated_at'] ? date('M d, Y H:i', strtotime($exemption['updated_at'])) : ''; ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-primary update-status" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateStatusModal"
                                                        data-id="<?php echo $exemption['id']; ?>"
                                                        data-lecturer="<?php echo htmlspecialchars($exemption['lecturer_name']); ?>"
                                                        data-status="<?php echo $exemption['status']; ?>"
                                                        data-remarks="<?php echo htmlspecialchars($exemption['admin_remarks'] ?? ''); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-danger delete-exemption" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteExemptionModal"
                                                        data-id="<?php echo $exemption['id']; ?>"
                                                        data-lecturer="<?php echo htmlspecialchars($exemption['lecturer_name']); ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                
                                                
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No exemption requests found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Exemption Modal -->
    <div class="modal fade" id="createExemptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                  data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="lecturer_id" class="form-label">Lecturer</label>
                                <select class="form-select" name="lecturer_id" id="lecturer_id" required>
                                    <option value="">Select lecturer...</option>
                                    <?php while ($lecturer = $lecturers->fetch_assoc()): ?>
                                        <option value="<?php echo $lecturer['id']; ?>" data-expertise="<?php echo htmlspecialchars($lecturer['expertise_areas']); ?>">
                                            <?php echo htmlspecialchars($lecturer['name']); ?> (<?php echo htmlspecialchars($lecturer['email']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text" id="lecturerExpertiseDisplay"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="create_status" required>
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" name="academic_year" id="create_academic_year" 
                                       value="<?php echo $current_academic_year; ?>" required>
                                <div class="form-text">Format: YYYY/YYYY (e.g., 2023/2024)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" name="semester" id="create_semester" required>
                                    <option value="First Semester" <?php echo ($current_semester == 'First Semester') ? 'selected' : ''; ?>>
                                        First Semester
                                    </option>
                                    <option value="Second Semester" <?php echo ($current_semester == 'Second Semester') ? 'selected' : ''; ?>>
                                        Second Semester
                                    </option>
                                    <!-- <option value="Summer" <?php echo ($current_semester == 'Summer') ? 'selected' : ''; ?>>
                                        Summer
                                    </option> -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Exemption</label>
                            <textarea class="form-control" name="reason" id="create_reason" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_remarks" class="form-label">Administrative Remarks</label>
                            <textarea class="form-control" name="admin_remarks" id="create_admin_remarks" rows="3"></textarea>
                            <div class="form-text">Internal notes about this exemption (visible to admins only).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_exemption" class="btn btn-primary">Create Exemption</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Exemption Details Modal -->
    <div class="modal fade" id="exemptionDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Exemption Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Lecturer</h6>
                            <p id="detailLecturer"></p>
                            
                            <h6 class="fw-bold">Email</h6>
                            <p id="detailEmail"></p>
                            
                            <h6 class="fw-bold">Expertise Areas</h6>
                            <p id="detailExpertise" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Semester</h6>
                            <p id="detailSemester"></p>
                            
                            <h6 class="fw-bold">Academic Year</h6>
                            <p id="detailAcademicYear"></p>
                            
                            <h6 class="fw-bold">Status</h6>
                            <p><span id="detailStatus" class="status-badge"></span></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Reason for Exemption</h6>
                        <p id="detailReason" class="p-3 bg-light rounded"></p>
                    </div>
                    
                    <div id="detailRemarksContainer" class="mb-4">
                        <h6 class="fw-bold">Admin Remarks</h6>
                        <p id="detailRemarks" class="p-3 bg-light rounded"></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Submitted On</h6>
                            <p id="detailCreated"></p>
                        </div>
                        <div class="col-md-6" id="detailUpdatedContainer">
                            <h6 class="fw-bold">Last Updated</h6>
                            <p id="detailUpdated"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="detailsUpdateBtn">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Exemption Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="exemption_id" id="updateExemptionId">
                        
                        <div class="mb-3">
                            <label class="form-label">Lecturer</label>
                            <p id="updateLecturer" class="form-control-plaintext"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="updateStatus" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_remarks" class="form-label">Admin Remarks</label>
                            <textarea class="form-control" name="admin_remarks" id="updateRemarks" rows="3"></textarea>
                            <div class="form-text">Provide a reason for approval or rejection (optional).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_exemption" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Exemption Modal -->
    <div class="modal fade" id="deleteExemptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the exemption request for <strong id="deleteLecturer"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" name="exemption_id" id="deleteExemptionId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_exemption" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.exemption-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButton();
        });
        
        // Update bulk action button state
        document.querySelectorAll('.exemption-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionButton);
        });
        
        function updateBulkActionButton() {
            const checkboxes = document.querySelectorAll('.exemption-checkbox:checked');
            const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
            
            if (checkboxes.length > 0) {
                bulkUpdateBtn.textContent = `Update ${checkboxes.length} Selected`;
                bulkUpdateBtn.disabled = false;
            } else {
                bulkUpdateBtn.textContent = 'Update Selected';
                bulkUpdateBtn.disabled = true;
            }
        }
        
        // View details modal
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const lecturer = this.getAttribute('data-lecturer');
                const email = this.getAttribute('data-email');
                const expertise = this.getAttribute('data-expertise');
                const semester = this.getAttribute('data-semester');
                const academicYear = this.getAttribute('data-academic-year');
                const reason = this.getAttribute('data-reason');
                const status = this.getAttribute('data-status');
                const remarks = this.getAttribute('data-remarks');
                const created = this.getAttribute('data-created');
                const updated = this.getAttribute('data-updated');
                
                document.getElementById('detailLecturer').textContent = lecturer;
                document.getElementById('detailEmail').textContent = email;
                document.getElementById('detailExpertise').textContent = expertise || 'None specified';
                document.getElementById('detailSemester').textContent = semester;
                document.getElementById('detailAcademicYear').textContent = academicYear;
                document.getElementById('detailReason').textContent = reason;
                
                const statusElement = document.getElementById('detailStatus');
                statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusElement.className = 'status-badge status-' + status;
                
                document.getElementById('detailCreated').textContent = created;
                
                // Handle updated date and admin remarks
                if (updated) {
                    document.getElementById('detailUpdated').textContent = updated;
                    document.getElementById('detailUpdatedContainer').style.display = 'block';
                } else {
                    document.getElementById('detailUpdatedContainer').style.display = 'none';
                }
                
                // Handle admin remarks
                if (remarks) {
                    document.getElementById('detailRemarks').textContent = remarks;
                    document.getElementById('detailRemarksContainer').style.display = 'block';
                } else {
                    document.getElementById('detailRemarksContainer').style.display = 'none';
                }
                
                // Set up the update button to open the update modal with this exemption's data
                document.getElementById('detailsUpdateBtn').onclick = function() {
                    // Hide the details modal
                    bootstrap.Modal.getInstance(document.getElementById('exemptionDetailsModal')).hide();
                    
                    // Set the update modal data
                    document.getElementById('updateExemptionId').value = id;
                    document.getElementById('updateLecturer').textContent = lecturer;
                    document.getElementById('updateStatus').value = status;
                    document.getElementById('updateRemarks').value = remarks;
                    
                    // Show the update modal
                    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
                };
            });
        });
        
        // Update status modal
        document.querySelectorAll('.update-status').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const lecturer = this.getAttribute('data-lecturer');
                const status = this.getAttribute('data-status');
                const remarks = this.getAttribute('data-remarks');
                
                document.getElementById('updateExemptionId').value = id;
                document.getElementById('updateLecturer').textContent = lecturer;
                document.getElementById('updateStatus').value = status;
                document.getElementById('updateRemarks').value = remarks;
            });
        });
        
        // Delete exemption modal
        document.querySelectorAll('.delete-exemption').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const lecturer = this.getAttribute('data-lecturer');
                
                document.getElementById('deleteExemptionId').value = id;
                document.getElementById('deleteLecturer').textContent = lecturer;
            });
        });
        
        // Display lecturer expertise when selected
        document.getElementById('lecturer_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const expertise = selectedOption.getAttribute('data-expertise');
            
            if (expertise) {
                document.getElementById('lecturerExpertiseDisplay').textContent = 'Expertise: ' + expertise;
            } else {
                document.getElementById('lecturerExpertiseDisplay').textContent = 'No expertise areas specified';
            }
        });
        
        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            let alerts = document.getElementsByClassName('alert-dismissible');
            for (let alert of alerts) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
        
        // Submit form when filter changes
        document.getElementById('status').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('semester').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('academic_year').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
<?php include '../footer_small.php'?>
</html>
