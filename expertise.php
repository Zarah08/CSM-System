<?php
include_once('../db_connection.php');

header('Content-Type: application/json');

$query = "SELECT * FROM expertise";
$stmt = $conn->prepare($query);
$stmt->execute();
$expertise_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode($expertise_list);

