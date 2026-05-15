<?php
// api/create_proposal.php

header('Content-Type: application/json');
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);
    exit;
}

try {
    // PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get POST values
    $wallet_name    = trim($_POST['wallet_name'] ?? '');
    $wallet_address = trim($_POST['wallet_address'] ?? '');
    $project_name   = trim($_POST['project_name'] ?? '');
    $duration       = (int)($_POST['duration'] ?? 0);
    $budget         = (float)($_POST['budget'] ?? 0);
    $milestone1     = trim($_POST['milestone1'] ?? '');
    $milestone2     = trim($_POST['milestone2'] ?? '');
    $milestone3     = trim($_POST['milestone3'] ?? '');

    $errors = [];

    // Validation
    if (empty($wallet_name)) {
        $errors[] = "Wallet name is required";
    }

    if (empty($wallet_address)) {
        $errors[] = "Wallet address is required";
    }

    if (empty($project_name)) {
        $errors[] = "Project name is required";
    }

    if ($duration < 1) {
        $errors[] = "Duration must be at least 1 week";
    }

    if ($budget < 100) {
        $errors[] = "Budget must be at least 100";
    }

    if (empty($milestone1) || empty($milestone2) || empty($milestone3)) {
        $errors[] = "All milestones are required";
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "errors" => $errors
        ]);
        exit;
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO proposal 
        (wallet_name, wallet_address, project_name, duration, budget, milestone1, milestone2, milestone3, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $wallet_name,
        $wallet_address,
        $project_name,
        $duration,
        $budget,
        $milestone1,
        $milestone2,
        $milestone3
    ]);

    $proposal_id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Proposal created successfully",
        "proposal_id" => $proposal_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
}
?>