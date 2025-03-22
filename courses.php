<?php
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
session_start();
include_once('../db_connection.php');
include 'navbar.php';
include ('sidebar.php');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to get all categories
function getCategories($conn) {
    $query = "SELECT id, name FROM categories ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all courses with their categories
function getCourses($conn) {
    $query = "SELECT c.id, c.title, c.course_code, c.description, c.credit_load, cat.name as category_name 
              FROM courses c 
              LEFT JOIN categories cat ON c.category_id = cat.id 
              ORDER BY c.title ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Initialize variables
$success_message = "";
$error_message = "";
$courses = [];
$categories = [];

// Handle course deletion
if (isset($_POST['delete_course']) && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    $query = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    
    if ($stmt->execute()) {
        $success_message = "Course deleted successfully.";
    } else {
        $error_message = "Error deleting course: " . $conn->error;
    }
    $stmt->close();
}

// Handle form submission for adding a new course
// Handle form submission for adding a new course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $course_code = isset($_POST['course_code']) ? trim($_POST['course_code']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $credit_load = isset($_POST['credit_load']) ? intval($_POST['credit_load']) : 0;

    // Validate input
    if (empty($title) || empty($course_code) || empty($description) || $credit_load <= 0 || $category_id <= 0) {
        $error_message = "All fields are required and must be valid.";
    } else {
        // Check if the course already exists
        $query = "SELECT COUNT(*) AS count FROM courses WHERE title = ? AND course_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $title, $course_code);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $error_message = "A course with the same title and course code already exists.";
        } else {
            // Proceed with the insertion if no duplicate is found
            $query = "INSERT INTO courses (title, course_code, description, credit_load, category_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssis", $title, $course_code, $description, $credit_load, $category_id);

            if ($stmt->execute()) {
                $success_message = "Course added successfully!";
            } else {
                $error_message = "Error adding course: " . $conn->error;
            }
            $stmt->close();
        }
    }
}


// Fetch categories and courses
$categories = getCategories($conn);
$courses = getCourses($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="buk.jpg">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin-left: 17%;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container mt-5 ml-5">
        <h1 class="text-center mb-5"><i class="fas fa-graduation-cap"></i> Manage Courses</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-plus"></i> Add New Course</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="courses.php">
                            <div class="mb-3">
                                <label for="title" class="form-label">Course Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="course_code" class="form-label">Course Code</label>
                                <input type="text" class="form-control" id="course_code" name="course_code" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="credit_load" class="form-label">Credit Load</label>
                                <input type="number" class="form-control" id="credit_load" name="credit_load" min="1" max="6" required>
                            </div>
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_course" class="btn btn-primary w-100"><i class="fas fa-save"></i> Add Course</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-list"></i> Course List</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Credit Load</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($courses)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No courses found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . (strlen($course['description']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo htmlspecialchars($course['credit_load']); ?></td>
                                                <td><?php echo htmlspecialchars($course['category_name']); ?></td>
                                                <td>
                                                    <form method="POST" action="courses.php" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                        <button type="submit" name="delete_course" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

