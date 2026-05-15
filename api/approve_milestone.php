<?php
header('Content-Type: application/json');
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)$_POST['proposal_id'];

    $stmt = $conn->prepare("UPDATE proposal SET status = 'active' WHERE proposal_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode([
        "status" => true,
        "message" => "Milestone approved"
    ]);
}
?>