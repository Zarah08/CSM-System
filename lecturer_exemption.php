<?php
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';
// Handle Exemption Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lecturer_id = $_POST['lecturer_id'];
    $reason = $_POST['reason'] ?? '';

    // Check if the lecturer is already exempted
    $checkQuery = "SELECT * FROM lecturer_exemptions WHERE lecturer_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $lecturer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert into the exemptions table
        $query = "INSERT INTO lecturer_exemptions (id, lecturer_id, reason) VALUES (NULL, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $lecturer_id, $reason);
        $stmt->execute();
        $message = "Lecturer exempted successfully!";
    } else {
        $message = "Lecturer is already exempted!";
    }
}

// Remove Exemption
if (isset($_GET['remove_id'])) {
    $lecturer_id = $_GET['remove_id'];
    $query = "DELETE FROM lecturer_exemptions WHERE lecturer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $lecturer_id);
    $stmt->execute();
    header("Location: exemption.php");
    exit();
}

// Fetch All Lecturers
$query = "SELECT * FROM lecturers";
$result = $conn->query($query);
$lecturers = $result->fetch_all(MYSQLI_ASSOC);

// Fetch Exempted Lecturers
$query = "SELECT l.id, l.name, le.reason FROM lecturer_exemptions le JOIN lecturers l ON le.lecturer_id = l.id";
$result = $conn->query($query);
$exemptedLecturers = $result->fetch_all(MYSQLI_ASSOC);
if ($stmt->execute()) {
    $message = "Lecturer exempted successfully!";
} else {
    $message = "Error: " . $stmt->error;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Exemption</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<style>
    body{
        margin-left: 20%;
    }
</style>
<body>

<div class="container mt-3">
    <h2 class="text-center">Lecturer Exemption</h2>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Exemption Form
    <div class="card p-4">
        <h4>Exempt a Lecturer</h4>
        <form method="post">
            <div class="mb-3">
                <label for="lecturer_id" class="form-label">Select Lecturer</label>
                <select name="lecturer_id" id="lecturer_id" class="form-control" required>
                    <option value="">-- Select Lecturer --</option>
                    <?php foreach ($lecturers as $lecturer): ?>
                        <option value="<?= $lecturer['id'] ?>"><?= htmlspecialchars($lecturer['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="reason" class="form-label">Reason (Optional)</label>
                <textarea name="reason" id="reason" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Exempt Lecturer</button>
        </form>
    </div> -->

    <!-- Exempted Lecturers List -->
    <div class="mt-5">
        <h4>Exempted Lecturers</h4>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Lecturer Name</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($exemptedLecturers)): ?>
                    <?php foreach ($exemptedLecturers as $index => $lecturer): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($lecturer['name']) ?></td>
                            <td><?= htmlspecialchars($lecturer['reason']) ?></td>
                            <td>
                                <a href="?remove_id=<?= $lecturer['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to remove exemption?')">
                                    Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No exempted lecturers</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include '../footer_small.php'?>
</html>
