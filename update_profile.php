<?php
session_start();
include_once('../db_connection.php');

// Ensure user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Handle interest update
if (isset($_POST['interest']) && !empty($_POST['interest'])) {
    $interest_id = $_POST['interest'];
    $stmt = $conn->prepare("UPDATE student SET interest_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $interest_id, $student_id);
    $stmt->execute();
}

// Handle profile picture upload
if (!empty($_FILES['profile_picture']['name'])) {
    $target_dir = "../uploads/";
    $file_name = basename($_FILES["profile_picture"]["name"]);
    $file_path = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    // Allowed file types
    $allowed_types = ["jpg", "jpeg", "png", "gif"];

    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $file_path)) {
            // Update database with new profile picture
            $stmt = $conn->prepare("UPDATE student SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $file_name, $student_id);
            $stmt->execute();
        } else {
            echo "<script>alert('Error uploading file'); window.location.href = 'edit_profile.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid file type! Only JPG, JPEG, PNG & GIF allowed.'); window.location.href = 'edit_profile.php';</script>";
        exit();
    }
}

// Redirect back to edit profile page with success message
echo "<script>alert('Profile updated successfully!'); window.location.href = 'edit_profile.php';</script>";
exit();
?>
