<?php
// session_start();
include_once('../db_connection.php');

// Ensure user is logged in as a student
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$student_id = $_SESSION['user_id'];

// Fetch current student details
$query = "SELECT name, profile_picture, interest_id FROM student WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch available interests
$interest_query = "SELECT id, name FROM interest";
$interests = $conn->query($interest_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .profile-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 0 auto 10px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container">
    <div class="profile-container">
        <h3 class="text-center"><i class="bi bi-pencil"></i> Edit Profile</h3>

        <!-- Display Profile Picture -->
        <img src="<?= !empty($student['profile_picture']) ? '../uploads/'.$student['profile_picture'] : '../uploads/default.png' ?>" class="profile-img" alt="Profile Picture">

        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Change Profile Picture</label>
                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
            </div>

            <div class="mb-3">
                <label for="interest" class="form-label">Select Interest Area</label>
                <select class="form-control" id="interest" name="interest">
                    <option value="">-- Select Interest --</option>
                    <?php while ($row = $interests->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= ($student['interest_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>
