<?php
// session_start();
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';

// Ensure user is logged in as a student

$student_id = $_SESSION['user_id'];

// Fetch assigned supervisor
$query = "
    SELECT l.name AS lecturer_name, l.email AS lecturer_email
    FROM student_lecturer_allocation sla
    JOIN lecturers l ON sla.lecturer_id = l.id
    WHERE sla.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$supervisor = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocated Supervisor</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            margin-top: 5%;
            margin-left: 15%;
        }
        .dashboard-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .dashboard-header {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .supervisor-info {
            background-color: #007bff;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="dashboard-container">
        <div class="dashboard-header">
            <i class="bi bi-person-bounding-box"></i> Allocated Supervisor
        </div>

        <?php if (!empty($supervisor)): ?>
            <?php
            // Fetch student's name and reg_no
            $student_query = "SELECT name, reg_no FROM student WHERE id = ?";
            $student_stmt = $conn->prepare($student_query);
            $student_stmt->bind_param("i", $student_id);
            $student_stmt->execute();
            $student_result = $student_stmt->get_result()->fetch_assoc();
            
            $student_name = $student_result['name'];
            $student_reg_no = $student_result['reg_no'];

            // Function to encode line breaks while preserving spaces
            function encodeEmailContent($text) {
                return str_replace(["\r\n", "\r", "\n"], "%0D%0A", $text);
            }

            // Prepare email content
            $email_subject = "Introduction - " . $student_reg_no;
            
            // Prepare email body with proper line breaks
            $email_body = <<<EOT
Dear Sir/Ma

I hope this email finds you well. 

My name is {$student_name} (Registration Number: {$student_reg_no}), and I have been assigned to you as my project supervisor.
Sir please could we schedule a meeting to discuss the on the project topics I have? 
I look forward to your guidance and support throughout this academic journey.


Thank you for your time and consideration.

Best regards,
{$student_name}
EOT;

            // Encode the email content
            $encoded_subject = rawurlencode($email_subject);
            $encoded_body = encodeEmailContent($email_body);
            ?>
            <div class="supervisor-info text-center">
                <h3><?= htmlspecialchars($supervisor['lecturer_name']) ?></h3>
                <p>
                    <i class="bi bi-envelope"></i> 
                    <a href="mailto:<?= htmlspecialchars($supervisor['lecturer_email']) ?>?subject=<?= $encoded_subject ?>&body=<?= $encoded_body ?>" class="text-white">
                        <?= htmlspecialchars($supervisor['lecturer_email']) ?>
                    </a>
                </p>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No supervisor assigned yet.</p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function()) {
            const toggleBtn = document.getElementById('toggleStats');
            const statsContent = document.getElementById('statsContent');
            const btnIcon = toggleBtn.querySelector('i');
            const btnText = toggleBtn.lastChild;

            toggleBtn.addEventListener('click', function() {
                if (statsContent.style.display === 'none') {
                    statsContent.style.display = 'block';
                    btnIcon.classList.remove('bi-eye-fill');
                    btnIcon.classList.add('bi-eye-slash-fill');
                    btnText.textContent = ' Hide Statistics';
                    initializeCharts();
                } else {
                    statsContent.style.display = 'none';
                    btnIcon.classList.remove('bi-eye-slash-fill');
                    btnIcon.classList.add('bi-eye-fill');
                    btnText.textContent = ' Show Statistics';
                }
            });
</script>
</body>
</html>