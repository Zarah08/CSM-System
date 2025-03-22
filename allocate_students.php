<?php
// Connect to the database
include_once('../db_connection.php');
include 'navbar.php';
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Matching System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin-left: 10%;
        }
        .main-content {
            flex: 1;
        }
        .card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        
        .btn-allocate {
            transition: all 0.3s ease;
        }
        .btn-allocate:hover {
            transform: scale(1.05);
        }
        #loader {
            display: none;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <div class="container main-content">
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <h1 class="card-title mb-4">
                            <i class="fas fa-users me-2 text-primary"></i>
                            Supervisor Matching System
                        </h1>
                        <p class="card-text mb-4">
                            Click the button below to start matching Final Year Students with Supervisors.
                        </p>
                        <form action="allocate_sup.php" method="POST" id="allocationForm">
                            <button type="submit" class="btn btn-primary btn-lg btn-allocate" id="allocateBtn">
                                <i class="fas fa-random me-2"></i>
                                Match Final Year Students with Supervisors
                            </button>
                        </form>
                        <div id="loader" class="mt-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class=""></span>
                            </div>
                            <!-- <p class="mt-2">Please wait while allocation is in progress...</p> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('allocationForm').addEventListener('submit', function(e) {
            // document.getElementById('allocateBtn').style.display = 'none';
            // document.getElementById('loader').style.display = 'block';
        });
    </script>
</body>
<?php include '../footer_small.php'?>
</html>