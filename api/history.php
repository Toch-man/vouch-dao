<?php
header('Content-Type: application/json');
include '../db.php';

$wallet = $_GET['wallet'] ?? '';

$stmt = $conn->prepare("SELECT * FROM transactions WHERE wallet_address = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $wallet);
$stmt->execute();

$result = $stmt->get_result();

$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
?>