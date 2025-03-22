<?php
session_start();
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Check if logged in as lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit();
}

// Get lecturer ID
$lecturer_id = isset($_GET['lecturer_id']) ? intval($_GET['lecturer_id']) : $_SESSION['user_id'];

// Fetch assigned students
$query = "SELECT s.id, s.name, s.reg_no, e.name AS interest_area
          FROM student_lecturer_allocation sla
          JOIN student s ON sla.student_id = s.id
          LEFT JOIN expertise e ON s.interest_id = e.id
          WHERE sla.lecturer_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; 
        margin-left: 20%;
    margin-top: 8%;}
        .content-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-container">
            <h2 class="text-center mb-4"><i class="bi bi-people"></i> My Students</h2>

            <?php if (!empty($students)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Name</th>
                            <th>Interest Area</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['reg_no']) ?></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['interest_area'] ?? 'Not Specified') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">No students assigned.</p>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>