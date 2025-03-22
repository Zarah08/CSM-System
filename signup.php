<?php
include 'header.php';
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $conn->begin_transaction();

        if ($role === 'student') {
            $username = $_POST['registration_number'];
            $name = $_POST['name'];
            $interest_id = $_POST['interest_id'];

            // Insert into 'users' table
            $query = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $username, $password, $role);
            $stmt->execute();

            $userId = $conn->insert_id;

            // Insert into 'student' table
            $query = "INSERT INTO student (id, reg_no, interest_id, name) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isis", $userId, $username, $interest_id, $name);
            $stmt->execute();

        } elseif ($role === 'lecturer') {
            $username = $_POST['email']; // Use email as username for lecturers
            $name = $_POST['name'];
            $expertise = isset($_POST['expertise']) ? $_POST['expertise'] : [];

            // Insert into 'users' table
            $query = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $username, $password, $role);
            $stmt->execute();

            $userId = $conn->insert_id;

            // Insert into 'lecturers' table
            $query = "INSERT INTO lecturers (id, name, email) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $userId, $name, $username);
            $stmt->execute();

            // Insert expertise into 'lecturer_expertise' table
            if (!empty($expertise)) {
                $query = "INSERT INTO lecturer_expertise (lecturer_id, expertise_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                foreach ($expertise as $exp_id) {
                    $stmt->bind_param("ii", $userId, $exp_id);
                    $stmt->execute();
                }
            }
        } else {
            throw new Exception("Invalid role specified.");
        }

        $conn->commit();
        echo "<script>alert('Sign-up successful! You can now log in.'); window.location.href = 'login.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}

// Fetch existing expertise
$query = "SELECT * FROM expertise";
$stmt = $conn->prepare($query);
$stmt->execute();
$expertise_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .nav-pills .nav-link {
            border-radius: 8px;
            padding: 1rem 2rem;
            font-weight: 500;
            color: #666;
        }
        .nav-pills .nav-link.active {
            background-color: #4070f4;
        }
        .interest-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .interest-item {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .interest-number {
            background-color: #4070f4;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #4070f4;
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #3060e0;
        }
        h1.card-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="card">
                    <h1 class="card-title text-center">Sign Up</h1>
                    
                    <ul class="nav nav-pills nav-justified mb-4" id="signupTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="student-tab" data-bs-toggle="pill" data-bs-target="#student" type="button">STUDENT</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="lecturer-tab" data-bs-toggle="pill" data-bs-target="#lecturer" type="button">LECTURER</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="signupTabContent">
                        <!-- Student Sign-Up Form -->
                        <div class="tab-pane fade show active" id="student">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="mb-3">Interest Categories</h5>
                                    <ul class="interest-list">
                                        <li class="interest-item">Programming <span class="interest-number">1</span></li>
                                        <li class="interest-item">Mathematics <span class="interest-number">2</span></li>
                                        <li class="interest-item">Machine Learning <span class="interest-number">3</span></li>
                                        <li class="interest-item">Data Science <span class="interest-number">4</span></li>
                                        <li class="interest-item">Artificial Intelligence <span class="interest-number">5</span></li>
                                        <li class="interest-item">Research <span class="interest-number">6</span></li>
                                        <li class="interest-item">General <span class="interest-number">7</span></li>
                                        <li class="interest-item">Algorithms <span class="interest-number">8</span></li>
                                    </ul>
                                </div>
                                <div class="col-md-8">
                                    <form action="" method="POST">
                                        <input type="hidden" name="role" value="student">
                                        <div class="mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="name" placeholder="Enter your full name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Registration Number</label>
                                            <input type="text" class="form-control" name="registration_number" placeholder="Eg. CST/19/COM/001" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="password" placeholder="Enter a password" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label">Select Your Interest</label>
                                            <select class="form-select form-control" name="interest_id" required>
                                                <option value="">Choose an interest category</option>
                                                <option value="1">Programming</option>
                                                <option value="2">Mathematics</option>
                                                <option value="3">Machine Learning</option>
                                                <option value="4">Data Science</option>
                                                <option value="5">Artificial Intelligence</option>
                                                <option value="6">Research</option>
                                                <option value="7">General</option>
                                                <option value="8">Algorithms</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">SIGN UP AS STUDENT</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Lecturer Sign-Up Form -->
                        <div class="tab-pane fade" id="lecturer">
                            <form action="" method="POST">
                                <input type="hidden" name="role" value="lecturer">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Expertise</label>
                                    <select multiple class="form-select" name="expertise[]" required>
                                        <?php foreach ($expertise_list as $expertise): ?>
                                            <option value="<?= $expertise['id'] ?>"><?= htmlspecialchars($expertise['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple options.</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">SIGN UP AS LECTURER</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'footer_small.php'; ?>
```