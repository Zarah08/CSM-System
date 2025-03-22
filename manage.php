<?php
include_once('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'add_lecturer') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $department = $_POST['department'];

        $sql = "INSERT INTO lecturers (name, email, department) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $email, $department]);
        header("Location: manage_lecturers.php");
    } elseif ($action === 'delete_lecturer') {
        $id = $_POST['id'];

        $sql = "DELETE FROM lecturers WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        header("Location: manage_lecturers.php");
    } elseif ($action === 'add_student') {
        $name = $_POST['name'];
        $reg_no = $_POST['reg_no'];
        $interest_id = $_POST['interest_id'];

        $sql = "INSERT INTO student (name, reg_no, interest_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $reg_no, $interest_id]);
        header("Location: manage_students.php");
    } elseif ($action === 'delete_student') {
        $id = $_POST['id'];

        $sql = "DELETE FROM student WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        header("Location: manage_students.php");
    } elseif ($action === 'add_course') {
        $title = $_POST['title'];
        $course_code = $_POST['course_code'];
        $description = $_POST['description'];
        $credit_load = $_POST['credit_load'];
        $category_id = $_POST['category_id'];

        $sql = "INSERT INTO courses (title, course_code, description, credit_load, category_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $course_code, $description, $credit_load, $category_id]);
        header("Location: courses.php");
    } elseif ($action === 'delete_course') {
        $id = $_POST['id'];

        $sql = "DELETE FROM courses WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        header("Location: courses.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>