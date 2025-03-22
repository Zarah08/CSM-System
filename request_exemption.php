<?php
session_start();
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Check user role (assuming you have a session variable for user role)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
$currentUserId = $_SESSION['user_id'] ?? 0; // Get current user ID from session

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle exemption request submission
    if (isset($_POST['submit_exemption'])) {
        $lecturer_id = $_POST['lecturer_id'];
        $reason = $_POST['reason'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'];
        $status = $isAdmin ? 'approved' : 'pending'; // Auto-approve if admin submits
        
        $query = "INSERT INTO lecturer_exemptions (lecturer_id, reason, semester, academic_year, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $lecturer_id, $reason, $semester, $academic_year, $status);
        
        if ($stmt->execute()) {
            $success_message = "Exemption request submitted successfully!";
        } else {
            $error_message = "Error submitting exemption request: " . $conn->error;
        }
    }
    
    // Handle exemption status update
    if (isset($_POST['update_exemption']) && $isAdmin) {
        $exemption_id = $_POST['exemption_id'];
        $status = $_POST['status'];
        $admin_remarks = $_POST['admin_remarks'];
        
        $query = "UPDATE lecturer_exemptions 
                  SET status = ?, admin_remarks = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $admin_remarks, $exemption_id);
        
        if ($stmt->execute()) {
            $success_message = "Exemption status updated successfully!";
        } else {
            $error_message = "Error updating exemption status: " . $conn->error;
        }
    }
    
    // Handle exemption deletion
    if (isset($_POST['delete_exemption']) && $isAdmin) {
        $exemption_id = $_POST['exemption_id'];
        
        $query = "DELETE FROM lecturer_exemptions WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $exemption_id);
        
        if ($stmt->execute()) {
            $success_message = "Exemption record deleted successfully!";
        } else {
            $error_message = "Error deleting exemption record: " . $conn->error;
        }
    }
}

// Fetch lecturers for dropdown
$lecturers_query = "SELECT id, name FROM lecturers ORDER BY name";
$lecturers = $conn->query($lecturers_query);

// Fetch exemption requests
// For admin: fetch all requests
// For lecturer: fetch only their own requests
if ($isAdmin) {
    $exemptions_query = "
        SELECT 
            le.id, 
            le.lecturer_id,
            l.name AS lecturer_name, 
            le.reason, 
            le.semester,
            le.academic_year,
            le.status, 
            le.admin_remarks,
            le.created_at,
            le.updated_at
        FROM 
            lecturer_exemptions le
        JOIN 
            lecturers l ON le.lecturer_id = l.id
        ORDER BY 
            le.created_at DESC
    ";
} else {
    $exemptions_query = "
        SELECT 
            le.id, 
            le.lecturer_id,
            l.name AS lecturer_name, 
            le.reason, 
            le.semester,
            le.academic_year,
            le.status, 
            le.admin_remarks,
            le.created_at,
            le.updated_at
        FROM 
            lecturer_exemptions le
        JOIN 
            lecturers l ON le.lecturer_id = l.id
        WHERE 
            le.lecturer_id = $currentUserId";
}

$exemptions = $conn->query($exemptions_query);

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
    $current_semester = 'Academic Year';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Exemption Management</title>
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
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
        }
        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h2 class="display-6 mb-0">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    Lecturer Exemption Request
                </h2>
                <p class="text-muted mt-2">
                    <?php if ($isAdmin): ?>
                        Manage exemption requests from lecturers
                    <?php else: ?>
                        Request exemption from course allocation
                    <?php endif; ?>
                </p>
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

        <div class="row">
            <!-- Exemption Request Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            <?php echo $isAdmin ? "Create Exemption" : "Request Exemption"; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="lecturer_id" class="form-label">Lecturer</label>
                                <?php if ($isAdmin): ?>
                                    <select class="form-select" name="lecturer_id" id="lecturer_id" required>
                                        <option value="">Select lecturer...</option>
                                        <?php while ($lecturer = $lecturers->fetch_assoc()): ?>
                                            <option value="<?php echo $lecturer['id']; ?>">
                                                <?php echo htmlspecialchars($lecturer['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="hidden" name="lecturer_id" value="<?php echo $currentUserId; ?>">
                                    <input type="text" class="form-control" value="<?php echo $_SESSION['user_name'] ?? 'Current User'; ?>" disabled>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" name="academic_year" id="academic_year" 
                                       value="<?php echo $current_academic_year; ?>" required>
                                <div class="form-text">Format: YYYY/YYYY (e.g., 2023/2024)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" name="semester" id="semester" required>
                                    <option value="First Semester" <?php echo ($current_semester == 'First Semester') ? 'selected' : ''; ?>>
                                        First Semester
                                    </option>
                                    <option value="Second Semester" <?php echo ($current_semester == 'Second Semester') ? 'selected' : ''; ?>>
                                        Second Semester
                                    </option>
                                    <option value="year" <?php echo ($current_semester == 'year') ? 'selected' : ''; ?>>
                                        Academic Year
                                    </option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Exemption</label>
                                <textarea class="form-control" name="reason" id="reason" rows="4" required></textarea>
                                <div class="form-text">Please provide a detailed explanation for your exemption request.</div>
                            </div>
                            
                            <button type="submit" name="submit_exemption" class="btn btn-primary w-100">
                                <i class="bi bi-send me-2"></i>
                                <?php echo $isAdmin ? "Create Exemption" : "Submit Request"; ?>
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php if (!$isAdmin): ?>
                <div class="card mt-4">
                
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Exemption Requests List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            <?php echo $isAdmin ? "All Exemption Requests" : "Your Exemption Requests"; ?>
                        </h5>
                        <?php if ($isAdmin): ?>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" id="filterAll">All</button>
                            <button class="btn btn-sm btn-outline-warning" id="filterPending">Pending</button>
                            <button class="btn btn-sm btn-outline-success" id="filterApproved">Approved</button>
                            <button class="btn btn-sm btn-outline-danger" id="filterRejected">Rejected</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($exemptions->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="exemptionsTable">
                                    <thead>
                                        <tr>
                                            <?php if ($isAdmin): ?>
                                            <th>Lecturer</th>
                                            <?php endif; ?>
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
                                                <?php if ($isAdmin): ?>
                                                <td><?php echo htmlspecialchars($exemption['lecturer_name']); ?></td>
                                                <?php endif; ?>
                                                <td><?php echo htmlspecialchars($exemption['semester']); ?></td>
                                                <td><?php echo htmlspecialchars($exemption['academic_year']); ?></td>
                                                <td>
                                                    <?php 
                                                    $reason = htmlspecialchars($exemption['reason']);
                                                    echo (strlen($reason) > 50) ? substr($reason, 0, 50) . '...' : $reason; 
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $exemption['status']; ?>">
                                                        <?php echo ucfirst($exemption['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($exemption['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info view-details" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#exemptionDetailsModal"
                                                            data-id="<?php echo $exemption['id']; ?>"
                                                            data-lecturer="<?php echo htmlspecialchars($exemption['lecturer_name']); ?>"
                                                            data-semester="<?php echo htmlspecialchars($exemption['semester']); ?>"
                                                            data-academic-year="<?php echo htmlspecialchars($exemption['academic_year']); ?>"
                                                            data-reason="<?php echo htmlspecialchars($exemption['reason']); ?>"
                                                            data-status="<?php echo $exemption['status']; ?>"
                                                            data-remarks="<?php echo htmlspecialchars($exemption['admin_remarks'] ?? ''); ?>"
                                                            data-created="<?php echo date('M d, Y H:i', strtotime($exemption['created_at'])); ?>"
                                                            data-updated="<?php echo $exemption['updated_at'] ? date('M d, Y H:i', strtotime($exemption['updated_at'])) : ''; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($isAdmin): ?>
                                                    <button class="btn btn-sm btn-primary update-status" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#updateStatusModal"
                                                            data-id="<?php echo $exemption['id']; ?>"
                                                            data-lecturer="<?php echo htmlspecialchars($exemption['lecturer_name']); ?>"
                                                            data-status="<?php echo $exemption['status']; ?>"
                                                            data-remarks="<?php echo htmlspecialchars($exemption['admin_remarks'] ?? ''); ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    
                                                    <button class="btn btn-sm btn-danger delete-exemption" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteExemptionModal"
                                                            data-id="<?php echo $exemption['id']; ?>"
                                                            data-lecturer="<?php echo htmlspecialchars($exemption['lecturer_name']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No exemption requests found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
                            
                            <h6 class="fw-bold">Semester</h6>
                            <p id="detailSemester"></p>
                            
                            <h6 class="fw-bold">Academic Year</h6>
                            <p id="detailAcademicYear"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Status</h6>
                            <p><span id="detailStatus" class="status-badge"></span></p>
                            
                            <h6 class="fw-bold">Submitted On</h6>
                            <p id="detailCreated"></p>
                            
                            <div id="detailUpdatedContainer">
                                <h6 class="fw-bold">Last Updated</h6>
                                <p id="detailUpdated"></p>
                            </div>
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
                    
                    <div class="timeline">
                        <h6 class="fw-bold mb-3">Request Timeline</h6>
                        <div class="timeline-item">
                            <p class="mb-1">Request submitted</p>
                            <p class="timeline-date" id="timelineCreated"></p>
                        </div>
                        <div class="timeline-item" id="timelineUpdatedContainer" style="display: none;">
                            <p class="mb-1" id="timelineStatusText"></p>
                            <p class="timeline-date" id="timelineUpdated"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal (Admin Only) -->
    <?php if ($isAdmin): ?>
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
    
    <!-- Delete Exemption Modal (Admin Only) -->
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
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View details modal
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const lecturer = this.getAttribute('data-lecturer');
                const semester = this.getAttribute('data-semester');
                const academicYear = this.getAttribute('data-academic-year');
                const reason = this.getAttribute('data-reason');
                const status = this.getAttribute('data-status');
                const remarks = this.getAttribute('data-remarks');
                const created = this.getAttribute('data-created');
                const updated = this.getAttribute('data-updated');
                
                document.getElementById('detailLecturer').textContent = lecturer;
                document.getElementById('detailSemester').textContent = semester;
                document.getElementById('detailAcademicYear').textContent = academicYear;
                document.getElementById('detailReason').textContent = reason;
                
                const statusElement = document.getElementById('detailStatus');
                statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusElement.className = 'status-badge status-' + status;
                
                document.getElementById('detailCreated').textContent = created;
                document.getElementById('timelineCreated').textContent = created;
                
                // Handle updated date and admin remarks
                if (updated) {
                    document.getElementById('detailUpdated').textContent = updated;
                    document.getElementById('detailUpdatedContainer').style.display = 'block';
                    
                    document.getElementById('timelineUpdated').textContent = updated;
                    document.getElementById('timelineStatusText').textContent = 'Request ' + status;
                    document.getElementById('timelineUpdatedContainer').style.display = 'block';
                } else {
                    document.getElementById('detailUpdatedContainer').style.display = 'none';
                    document.getElementById('timelineUpdatedContainer').style.display = 'none';
                }
                
                // Handle admin remarks
                if (remarks) {
                    document.getElementById('detailRemarks').textContent = remarks;
                    document.getElementById('detailRemarksContainer').style.display = 'block';
                } else {
                    document.getElementById('detailRemarksContainer').style.display = 'none';
                }
            });
        });
        
        <?php if ($isAdmin): ?>
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
        
        // Filter buttons
        document.getElementById('filterAll').addEventListener('click', function() {
            filterTable('all');
            setActiveFilter(this);
        });
        
        document.getElementById('filterPending').addEventListener('click', function() {
            filterTable('pending');
            setActiveFilter(this);
        });
        
        document.getElementById('filterApproved').addEventListener('click', function() {
            filterTable('approved');
            setActiveFilter(this);
        });
        
        document.getElementById('filterRejected').addEventListener('click', function() {
            filterTable('rejected');
            setActiveFilter(this);
        });
        
        function filterTable(status) {
            const rows = document.querySelectorAll('#exemptionsTable tbody tr');
            
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function setActiveFilter(button) {
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.remove('btn-warning');
                btn.classList.remove('btn-success');
                btn.classList.remove('btn-danger');
                
                if (btn.id === 'filterAll') {
                    btn.classList.add('btn-outline-primary');
                } else if (btn.id === 'filterPending') {
                    btn.classList.add('btn-outline-warning');
                } else if (btn.id === 'filterApproved') {
                    btn.classList.add('btn-outline-success');
                } else if (btn.id === 'filterRejected') {
                    btn.classList.add('btn-outline-danger');
                }
            });
            
            button.classList.remove('btn-outline-primary');
            button.classList.remove('btn-outline-warning');
            button.classList.remove('btn-outline-success');
            button.classList.remove('btn-outline-danger');
            
            if (button.id === 'filterAll') {
                button.classList.add('btn-primary');
            } else if (button.id === 'filterPending') {
                button.classList.add('btn-warning');
            } else if (button.id === 'filterApproved') {
                button.classList.add('btn-success');
            } else if (button.id === 'filterRejected') {
                button.classList.add('btn-danger');
            }
        }
        <?php endif; ?>
        
        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            let alerts = document.getElementsByClassName('alert');
            for (let alert of alerts) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    </script>
</body>
<?php include '../footer_small.php'?>
</html>

