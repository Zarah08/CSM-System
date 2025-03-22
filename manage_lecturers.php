<?php

include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$message = "";
$error = "";
$lecturers = [];
$expertise_list = [];

// Fetch existing expertise
$query = "SELECT * FROM expertise";
$stmt = $conn->prepare($query);
$stmt->execute();
$expertise_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all lecturers with expertise
$query = "
    SELECT 
        lecturers.id AS lecturer_id, 
        lecturers.name AS lecturer_name, 
        lecturers.email AS lecturer_email, 
        GROUP_CONCAT(expertise.id SEPARATOR ',') AS expertise_ids, 
        GROUP_CONCAT(expertise.name SEPARATOR ', ') AS expertise_areas
    FROM lecturers
    LEFT JOIN lecturer_expertise ON lecturers.id = lecturer_expertise.lecturer_id
    LEFT JOIN expertise ON lecturer_expertise.expertise_id = expertise.id
    GROUP BY lecturers.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$lecturers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_lecturer') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $expertise_ids = $_POST['expertise'] ?? [];
        $default_password = password_hash("lecturer123", PASSWORD_DEFAULT); // Hash password

        if (empty($name) || empty($email) || empty($expertise_ids)) {
            $error = "All fields are required, and at least one expertise must be selected.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $conn->begin_transaction();
            try {
                // Check if email already exists in users table
                $query = "SELECT COUNT(*) AS count FROM users WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    throw new Exception("A user with this email already exists.");
                }

                // Insert into lecturers table
                $query = "INSERT INTO lecturers (name, email) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $name, $email);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert lecturer: " . $stmt->error);
                }
                $lecturer_id = $conn->insert_id;

                // Insert into users table with default password
                $query = "INSERT INTO users (username, role, password) VALUES (?, 'lecturer', ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $email, $default_password);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create user account: " . $stmt->error);
                }

                // Assign expertise
                $query = "INSERT INTO lecturer_expertise (lecturer_id, expertise_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                foreach ($expertise_ids as $expertise_id) {
                    $stmt->bind_param("ii", $lecturer_id, $expertise_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to map expertise: " . $stmt->error);
                    }
                }

                // Commit transaction
                $conn->commit();
                $message = "Lecturer added successfully.";
                echo "<script type='text/javascript'>
                loadPage('manageLecturers')
                </script>"
           ;
           
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
                echo "<script type='text/javascript'>
                loadPage('manageLecturers')
                </script>";
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_lecturer') {
        $id = intval($_POST['id']); // Ensure it's an integer to prevent SQL injection

        if ($id > 0) {
            $conn->begin_transaction();
            try {
                // Debugging: Check if ID is received
                error_log("Attempting to delete lecturer ID: " . $id);

                // Check if lecturer exists
                $query = "SELECT email FROM lecturers WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $lecturer = $result->fetch_assoc();

                if (!$lecturer) {
                    throw new Exception("Lecturer not found.");
                }

                $lecturer_email = $lecturer['email'];

                // Temporarily disable foreign key checks to prevent constraint issues
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                // First delete from users table
                $query = "DELETE FROM users WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $lecturer_email);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete user: " . $stmt->error);
                }

                // Then delete from lecturer_courses table
                $query = "DELETE FROM lecturer_courses WHERE lecturer_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete from lecturer_courses: " . $stmt->error);
                }

                // Then delete from lecturer_expertise table
                $query = "DELETE FROM lecturer_expertise WHERE lecturer_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete from lecturer_expertise: " . $stmt->error);
                }

                // Finally delete from lecturers table
                $query = "DELETE FROM lecturers WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete from lecturers: " . $stmt->error);
                }

                // Re-enable foreign key checks
                $conn->query("SET FOREIGN_KEY_CHECKS=1");

                $conn->commit();
                $message = "Lecturer deleted successfully.";
                
                // Debugging: Log successful deletion
                error_log("Lecturer with ID $id successfully deleted.");

                header("Location: " . $_SERVER['PHP_SELF']); // Refresh page
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error deleting lecturer: " . $e->getMessage();
                error_log("Error deleting lecturer: " . $e->getMessage());
            }
        } else {
            $error = "Invalid lecturer ID.";
            error_log("Invalid lecturer ID: " . $id);
        }
    }
}


    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'edit_lecturer') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $expertise_ids = $_POST['expertise'] ?? [];

        if (empty($id) || empty($name) || empty($email) || empty($expertise_ids)) {
            $error = "All fields are required.";
        } else {
            $conn->begin_transaction();
            try {
                // Fetch existing email
                $query = "SELECT email FROM lecturers WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if (!$row) {
                    throw new Exception("Lecturer not found.");
                }

                $old_email = $row['email'];

                // Update lecturer details
                $query = "UPDATE lecturers SET name = ?, email = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssi", $name, $email, $id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update lecturer: " . $stmt->error);
                }

                // Update email in users table
                $query = "UPDATE users SET username = ? WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $email, $old_email);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update user email: " . $stmt->error);
                }

                // Remove existing expertise mappings
                $query = "DELETE FROM lecturer_expertise WHERE lecturer_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Insert new expertise mappings
                $query = "INSERT INTO lecturer_expertise (lecturer_id, expertise_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                foreach ($expertise_ids as $expertise_id) {
                    $stmt->bind_param("ii", $id, $expertise_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update expertise: " . $stmt->error);
                    }
                }

                $conn->commit();
                $message = "Lecturer updated successfully.";
                header("Location: " . $_SERVER['PHP_SELF']); 
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error updating lecturer: " . $e->getMessage();
            }
        }
    }
}


?>

<div class="container ml-5 ">
    

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lecturers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd;
        }
        
        body {
            background-color: #f8f9fa;
        }

        .content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-left: 20%;
        }

        .form-toggle {
            display: flex;
            gap: 2px;
            background: #f1f3f5;
            padding: 2px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .form-toggle button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: none;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-toggle button.active {
            background: var(--primary-blue);
            color: white;
        }

        .form-container {
            padding: 0 20px;
            margin-bottom: 40px;
        }

        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .select2-container--default .select2-selection--multiple {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            margin-top: 20px;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }

        .lecturer-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .lecturer-list .table {
            margin-bottom: 0;
        }

        .lecturer-list th {
            background-color: var(--primary-blue);
            color: white;
            font-weight: 500;
            border: none;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
        }

        .table > :not(caption) > * > * {
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="content ml-5">
        <h1 class="text-center mb-4">Manage Lecturers</h1>

        <!-- Form Toggle -->
        <div class="form-toggle">
            <button class="active" onclick="showForm('add')">ADD LECTURER</button>
            
        </div>

        <!-- Add Lecturer Form -->
        <div class="form-section active" id="addForm">
            <div class="form-container">
                <form action="manage_lecturers.php" method="POST">
                    <input type="hidden" name="action" value="add_lecturer">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" placeholder="Enter lecturer's full name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter lecturer's email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Expertise</label>
                        <select multiple class="form-control select2" name="expertise[]" required>
                            <?php foreach ($expertise_list as $expertise): ?>
                                <option value="<?= $expertise['id'] ?>"><?= htmlspecialchars($expertise['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple options.</small>
                    </div>

                    <button type="submit" class="btn-submit">ADD LECTURER</button>
                </form>
            </div>
        </div>


        <!-- Lecturers List -->
        <div class="lecturer-list">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Areas of Expertise</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lecturers as $index => $lecturer): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($lecturer['lecturer_name']) ?></td>
                <td><?= htmlspecialchars($lecturer['lecturer_email']) ?></td>
                <td><?= htmlspecialchars($lecturer['expertise_areas']) ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-action" 
                        onclick="editLecturer(<?= htmlspecialchars(json_encode($lecturer)) ?>)">
                        Edit
                    </button>

                    <!-- Delete Button -->
                    <form action="manage_lecturers.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_lecturer">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($lecturer['lecturer_id']) ?>">
                        <button 
                            type="submit" 
                            class="btn btn-danger btn-action" 
                            onclick="return confirm('Are you sure you want to delete this lecturer?');"
                        >
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- Edit Lecturer Modal -->
<div class="modal fade" id="editLecturerModal" tabindex="-1" aria-labelledby="editLecturerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLecturerModalLabel">Edit Lecturer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="manage_lecturers.php" method="POST">
                    <input type="hidden" name="action" value="edit_lecturer">
                    <input type="hidden" id="edit-id" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit-email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Expertise</label>
                        <select multiple class="form-control select2" id="edit-expertise" name="expertise[]" required>
                            <?php foreach ($expertise_list as $expertise): ?>
                                <option value="<?= $expertise['id'] ?>"><?= htmlspecialchars($expertise['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });

        function showForm(type) {
            // Update button states
            document.querySelectorAll('.form-toggle button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show/hide forms
            document.querySelectorAll('.form-section').forEach(form => form.classList.remove('active'));
            document.getElementById(type + 'Form').classList.add('active');
        }
        function editLecturer(lecturer) {
    // Fill modal with lecturer details
    document.getElementById('edit-id').value = lecturer.lecturer_id;
    document.getElementById('edit-name').value = lecturer.lecturer_name;
    document.getElementById('edit-email').value = lecturer.lecturer_email;

    const expertiseSelect = document.getElementById('edit-expertise');

    // Clear previous selections
    for (let option of expertiseSelect.options) {
        option.selected = false;
    }

    // Select expertise options
    if (lecturer.expertise_ids) {
        let expertiseArray = lecturer.expertise_ids.split(',');
        for (let option of expertiseSelect.options) {
            if (expertiseArray.includes(option.value)) {
                option.selected = true;
            }
        }
    }

    $('#edit-expertise').trigger('change');

    // Show the modal
    var editModal = new bootstrap.Modal(document.getElementById('editLecturerModal'));
    editModal.show();
}


        function fillUpdateForm(lecturer) {
            showForm('update');
            
            document.getElementById('update-id').value = lecturer.lecturer_id;
            document.getElementById('update-name').value = lecturer.lecturer_name;
            document.getElementById('update-email').value = lecturer.lecturer_email;

            const expertiseSelect = document.getElementById('update-expertise');
            
            // Clear previous selections
            for (let option of expertiseSelect.options) {
                option.selected = false;
            }

            // Select expertise options
            if (lecturer.expertise_ids) {
                let expertiseArray = lecturer.expertise_ids.split(',');
                for (let option of expertiseSelect.options) {
                    if (expertiseArray.includes(option.value)) {
                        option.selected = true;
                    }
                }
            }

            $('#update-expertise').trigger('change');

        }
    </script>
</body>
</html>