<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sidebar</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            /* background-color: #4169E1; */
            height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .nav-link {
            color: white !important;
            padding: 15px 25px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background-color 0.3s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            font-size: 1.3rem;
        }

        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 10px;
            margin-left: 30px;
        }
    </style>
</head>
<body>

<div class="sidebar bg-primary">
    
    <div class="nav flex-column mt-5">
        <a href="dashboard.php" class="nav-link">
        <i class="bi bi-house-door"></i>
            Dashboard
        </a>
        <a href="manage_lecturers.php" class="nav-link">
        <i class="bi bi-person-lines-fill"></i>
            Manage Lecturers
        </a>
        <a href="manage_students.php" class="nav-link">
            <i class="bi bi-mortarboard"></i>
            Manage Final Year Students
        </a>
        <a href="courses.php" class="nav-link">
            <i class="bi bi-book"></i>
            Manage Courses
        </a>
    </div>
    
    <div class="logout-section ">
        <a href="logout.php" class="nav-link">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>
    </div>
</div>

<?php
// You can include this file in other PHP files using:
// include 'sidebar.php';

// Optional: Add active state based on current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<script>
    // Add active class to current page link
    document.querySelectorAll('.nav-link').forEach(link => {
        if(link.getAttribute('href') === '<?php echo $current_page; ?>') {
            link.classList.add('active');
        }
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>