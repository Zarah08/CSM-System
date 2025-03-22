<?
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login.php"); // Redirect if not logged in or not super admin
    exit();
}
?>
<link rel="icon" type="image/jpeg" href="../buk.jpg">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <!-- Brand Name -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Registrer Admin Panel
        </a>

        <!-- Toggle Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_lecturers.php"><i class="bi bi-person-lines-fill"></i> Manage Lecturers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_students.php"><i class="bi bi-mortarboard"></i> Manage Students</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="courses.php"><i class="bi bi-book"></i> Manage Courses</a>
                </li>

                <!-- Display logged-in username -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> 
                        <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Registrer Admin' ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php" onclick="return confirm('Are you sure you want to log out?');">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- Ensure Bootstrap JavaScript is loaded -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
