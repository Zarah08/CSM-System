<?php
session_start();
include 'navbar.php';
include 'sidebar.php';

$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assume submission is successful
    $success = "The report has been submitted successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Progress Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { padding-top: 56px; }
        .fade-out {
            animation: fadeOut 5s forwards;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
    </style>
</head>
<body>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Submit Progress Report</h1>
    </div>

    <!-- 📌 Instructions Section -->
    <div class="alert alert-info">
        <h5><b>📌 Important Instructions for Progress Report Submission</b></h5>
        <ul>
            <li>🗓️ A progress report must be submitted every month.</li>
            <li>📄 Each report should include:
                <ul>
                    <li>✅ Accomplished Tasks – List of completed activities and milestones.</li>
                    <li>⚠️ Challenges Faced– Any difficulties encountered during the month.</li>
                    <li>🚀 Planned Tasks for the Next Month – Future tasks and objectives.</li>
                </ul>
            </li>
            <li>⚠️ Students must submit at least five (5) progress reports to be eligible for project defense.</li>
            <li>🚫 Failure to meet this requirement may result in ineligibility for the final defense.</li>
        </ul>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success fade-out"><?= $success ?></div>
        <script>
            setTimeout(function() {
                document.querySelector(".alert-success").style.display = "none";
            }, 5000);
        </script>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Report Number</label>
            <input type="number" class="form-control" id="title" name="title" required>
        </div>
        <!-- <div class="mb-3">
            <label for="abstract" class="form-label">Abstract</label>
            <textarea class="form-control" id="abstract" name="abstract" rows="4" required></textarea>
        </div> -->
        <div class="mb-3">
            <label for="report_file" class="form-label">Upload Report (PDF only, max 5MB)</label>
            <input type="file" class="form-control" id="report_file" name="report_file" accept=".pdf">
        </div>
        <button type="submit" class="btn btn-primary">Submit Report</button>
    </form>
</main>

</body>
</html>
