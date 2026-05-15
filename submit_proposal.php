<?php
// submit_proposal.php

session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {

    // Get form values
    $wallet_name     = trim($_POST['wallet_name'] ?? '');
    $wallet_address  = trim($_POST['wallet_address'] ?? '');
    $project_name    = trim($_POST['project_name'] ?? '');
    $duration        = (int)($_POST['duration'] ?? 0);
    $budget          = (float)($_POST['budget'] ?? 0);
    $milestone1      = trim($_POST['milestone1'] ?? '');
    $milestone2      = trim($_POST['milestone2'] ?? '');
    $milestone3      = trim($_POST['milestone3'] ?? '');

    $errors = [];

    // Basic Validation
    if (empty($project_name)) {
        $errors[] = "Project name is required.";
    }
    if ($duration < 1) {
        $errors[] = "Duration must be at least 1 week.";
    }
    if ($budget < 100) {
        $errors[] = "Budget must be at least $100.";
    }
    if (empty($milestone1) || empty($milestone2) || empty($milestone3)) {
        $errors[] = "All milestones are required.";
    }

    if (empty($errors)) {
        try {
            // PDO Connection using constants from db.php
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("
                INSERT INTO proposal 
                (wallet_name, wallet_address, project_name, duration, budget, 
                 milestone1, milestone2, milestone3, status, created_at)
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
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

            $_SESSION['success'] = "✅ Proposal submitted successfully! Proposal ID: #$proposal_id";
            
            header("Location: proposals.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }

    // Return with errors
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    header("Location: error.php");
    exit;
}
?>