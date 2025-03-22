<?php

include_once('../db_connection.php');
include 'navbar.php';
include ('sidebar.php');
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$message = "";
$error = "";
$students = [];
$interest_list = [];

// Ensure the correct table name
$student_table = "student"; // Change to "students" if needed

// Fetch existing interests
$query = "SELECT * FROM interest";
$stmt = $conn->prepare($query);
$stmt->execute();
$interest_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch all students
$query = "
    SELECT 
        s.id AS student_id, 
        s.name AS student_name, 
        s.reg_no AS student_reg_no, 
        i.name AS interest_name 
    FROM $student_table s
    LEFT JOIN interest i ON s.interest_id = i.id
    ORDER BY s.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Add Student and Create User
    if ($_POST['action'] === 'add_student') {
        $name = trim($_POST['name']);
        $reg_no = trim($_POST['reg_no']);
        $interest_id = $_POST['interest'] ?? 0;
        $default_password = password_hash("student123", PASSWORD_DEFAULT); // Hash password

        if (empty($name) || empty($reg_no) || empty($interest_id)) {
            $error = "All fields are required.";
        } else {
            $conn->begin_transaction();
            try {
                // Check if reg_no already exists
                $query = "SELECT COUNT(*) AS count FROM $student_table WHERE reg_no = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $reg_no);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    throw new Exception("A student with this registration number already exists.");
                }

                // Insert into students table
                $query = "INSERT INTO $student_table (name, reg_no, interest_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssi", $name, $reg_no, $interest_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert student: " . $stmt->error);
                }
                $student_id = $conn->insert_id;

                // Insert into users table
                $query = "INSERT INTO users (username, role, password) VALUES (?, 'student', ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $reg_no, $default_password);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create user account: " . $stmt->error);
                }

                // Commit transaction
                $conn->commit();
                $message = "Student added successfully.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    // Delete Student and Remove User
    if ($_POST['action'] === 'delete_student') {
        $id = intval($_POST['id']);

        if ($id > 0) {
            $conn->begin_transaction();
            try {
                // Get student reg_no before deletion
                $query = "SELECT reg_no FROM $student_table WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $reg_no = $row['reg_no'];

                // Delete from student_lecturer_allocation
                $query = "DELETE FROM student_lecturer_allocation WHERE student_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Delete from students table
                $query = "DELETE FROM $student_table WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // Delete user from users table
                $query = "DELETE FROM users WHERE username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $reg_no);
                $stmt->execute();

                $conn->commit();
                $message = "Student deleted successfully.";
                // header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error deleting student: " . $e->getMessage();
            }
        } else {
            $error = "Invalid student ID.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd
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

        .select2-container--default .select2-selection--single {
            padding: 8px;
            height: auto;
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

        .student-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .student-list .table {
            margin-bottom: 0;
        }

        .student-list th {
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

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1 class="text-center mb-4">Manage Final Year Students</h1>

        <!-- Display Messages -->
        <?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


        <!-- Form Toggle -->
        <div class="form-toggle">
            <button class="active" onclick="showForm('add')">ADD STUDENT</button>
            
        </div>

        <!-- Add Student Form -->
        <div class="form-section active" id="addForm">
            <div class="form-container">
                <form action="manage_students.php" method="POST">
                    <input type="hidden" name="action" value="add_student">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" placeholder="Enter student's full name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reg No</label>
                        <input type="text" class="form-control" name="reg_no" placeholder="Enter registration number" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Interest</label>
                        <select class="form-control select2" name="interest" required>
                            <?php foreach ($interest_list as $interest): ?>
                                <option value="<?= $interest['id'] ?>"><?= htmlspecialchars($interest['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit">ADD STUDENT</button>
                </form>
            </div>
        </div>

        

        <div class="student-list">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Reg No</th>
                <th>Interest</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $index => $student): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($student['student_name']) ?></td>
                <td><?= htmlspecialchars($student['student_reg_no']) ?></td>
                <td><?= htmlspecialchars($student['interest_name']) ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-action" 
                        onclick="editStudent(<?= htmlspecialchars(json_encode($student)) ?>)">
                        Edit
                    </button>

                    <!-- Delete Button -->
                    <form action="manage_students.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete_student">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($student['student_id']) ?>">
                        <button 
                            type="submit" 
                            class="btn btn-danger btn-action" 
                            onclick="return confirm('Are you sure you want to delete this student?');"
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
<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="manage_students.php" method="POST">
                    <input type="hidden" name="action" value="edit_student">
                    <input type="hidden" id="edit-id" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reg No</label>
                        <input type="text" class="form-control" id="edit-reg_no" name="reg_no" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Interest</label>
                        <select class="form-control select2" id="edit-interest" name="interest" required>
                            <?php foreach ($interest_list as $interest): ?>
                                <option value="<?= $interest['id'] ?>"><?= htmlspecialchars($interest['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Display Messages -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="successMessage">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>



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
        function editStudent(student) {
    // Fill modal with student details
    document.getElementById('edit-id').value = student.student_id;
    document.getElementById('edit-name').value = student.student_name;
    document.getElementById('edit-reg_no').value = student.student_reg_no;

    // Set the selected interest
    $('#edit-interest').val(student.interest_id).trigger('change');

    // Show the modal
    var editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    editModal.show();
}


        function fillUpdateForm(student) {
            showForm('update');
            
            document.getElementById('update-id').value = student.student_id;
            document.getElementById('update-name').value = student.student_name;
            document.getElementById('update-reg_no').value = student.student_reg_no;
            
            // Set the selected interest
            $('#update-interest').val(student.interest_id).trigger('change');
        }
        // Auto-dismiss success message after 5 seconds
setTimeout(() => {
    let successAlert = document.getElementById('successMessage');
    if (successAlert) {
        successAlert.style.display = 'none';
    }
}, 5000);

    </script>
</body>
</html>