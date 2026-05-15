<?php
header('Content-Type: application/json');
include '../db.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT project_name, milestone1, milestone2, milestone3, status FROM proposal WHERE proposal_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode($data);
?>