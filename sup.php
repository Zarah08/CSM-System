<?php
include '../db_connection.php';

// Get all students
$students_query = "SELECT * FROM students ORDER BY name";
$students_result = $conn->query($students_query);

// Get all lecturers
$lecturers_query = "SELECT * FROM lecturers ORDER BY name";
$lecturers_result = $conn->query($lecturers_query);

// Get all interests
$interests_query = "SELECT * FROM interests ORDER BY name";
$interests_result = $conn->query($interests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student-Lecturer Matching System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Student-Lecturer Matching System</h1>
        
        <!-- Matching Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Find Matching Lecturers</h5>
                <form action="match.php" method="POST">
                    <div class="mb-3">
                        <label for="student" class="form-label">Select Student</label>
                        <select class="form-select" name="student_id" required>
                            <option value="">Choose a student...</option>
                            <?php while($student = $students_result->fetch_assoc()): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['name'] . ' (' . $student['reg_no'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Find Matches</button>
                </form>
            </div>
        </div>

        <!-- Interest Areas Table -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Available Interest Areas</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Interest Area</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($interest = $interests_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $interest['id']; ?></td>
                                    <td><?php echo htmlspecialchars($interest['name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>