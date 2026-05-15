<?php
header('Content-Type: application/json');
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $proposal_id = (int)$_POST['proposal_id'];
    $amount = (float)$_POST['amount'];

    $stmt = $conn->prepare("UPDATE proposal SET budget = budget + ? WHERE proposal_id = ?");
    $stmt->bind_param("di", $amount, $proposal_id);
    $stmt->execute();

    echo json_encode([
        "status" => true,
        "message" => "Vault funded successfully"
    ]);
}
?>