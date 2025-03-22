<?php
;
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Ensure the user is logged in as a super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'registrer_admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch user details from the database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle password update
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $message = "Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Admin Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .content {
            margin-left: 260px;
            padding: 20px;
            width: calc(100% - 260px);
        }

        .profile-card {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 10px;
            object-fit: cover;
        }

        .btn-edit {
            margin-top: 10px;
            font-size: 14px;
        }

        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="content">
    <div class="profile-card">
        <img src="https://via.placeholder.com/120" alt="Profile Picture" class="profile-img">
        <h3><?= htmlspecialchars($user['username']) ?></h3>
        <p class="text-muted">Super Admin</p>
        <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['username']) ?></p>

        <button class="btn btn-primary btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            <i class="bi bi-pencil"></i> Edit Profile
        </button>

        <button class="btn btn-danger btn-edit" onclick="logout()">
            <i class="bi bi-box-arrow-right"></i> Logout
        </button>
    </div>

    <!-- Password Change Section -->
    <div class="profile-card">
        <h4>Change Password</h4>
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
        <form action="profile.php" method="POST">
            <input type="hidden" name="change_password">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Update Password</button>
        </form>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="profile_name" class="form-label">Username</label>
                        <input type="text" class="form-control" id="profile_name" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="profile_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="profile_email" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function logout() {
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "../logout.php";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
