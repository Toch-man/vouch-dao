<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {

    $wallet_name        = trim($_POST['wallet_name'] ?? '');
    $wallet_address     = trim($_POST['wallet_address'] ?? '');
    $project_name       = trim($_POST['project_name'] ?? '');
    $duration           = (int)($_POST['duration'] ?? 0);
    $budget             = (float)($_POST['budget'] ?? 0);
    $milestone1         = trim($_POST['milestone1'] ?? '');
    $milestone2         = trim($_POST['milestone2'] ?? '');
    $milestone3         = trim($_POST['milestone3'] ?? '');
    $contributor_wallet = trim($_POST['contributor_wallet'] ?? '');
    $resolver_wallet    = trim($_POST['resolver_wallet'] ?? '');
    $escrow_id          = trim($_POST['escrow_id'] ?? '');

    $errors = [];

    if (empty($project_name))   $errors[] = "Project name is required.";
    if ($duration < 1)          $errors[] = "Duration must be at least 1 week.";
    if ($budget < 100)          $errors[] = "Budget must be at least 100.";
    if (empty($milestone1) || empty($milestone2) || empty($milestone3))
                                $errors[] = "All milestones are required.";
    if (empty($contributor_wallet)) $errors[] = "Contributor wallet is required.";

    if (empty($errors)) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER, DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("
                INSERT INTO proposal 
                (wallet_name, wallet_address, project_name, duration, budget,
                 milestone1, milestone2, milestone3,
                 contributor_wallet, resolver_wallet, escrow_id,
                 status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([
                $wallet_name,
                $wallet_address,
                $project_name,
                $duration,
                $budget,
                $milestone1,
                $milestone2,
                $milestone3,
                $contributor_wallet,
                $resolver_wallet,
                $escrow_id,
            ]);

            $proposal_id = $pdo->lastInsertId();
            $_SESSION['success'] = "Proposal submitted! ID: #$proposal_id";
            header("Location: proposal.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

} else {
    header("Location: index.php");
    exit;
}
?>