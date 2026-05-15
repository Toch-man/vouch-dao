<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$address = trim($_POST['wallet_address'] ?? '');
$name    = trim($_POST['wallet_name'] ?? '');
$chain   = trim($_POST['chain_id'] ?? 'stellar-testnet');

/* Stellar address validation */
if (!preg_match('/^G[A-Z2-7]{55}$/', $address)) {
    header('Location: index.php?error=invalid_address');
    exit;
}

/* store session */
session_regenerate_id(true);

$_SESSION['wallet_address'] = $address;
$_SESSION['wallet_name']    = $name;
$_SESSION['chain_id']       = $chain;
$_SESSION['xlm_balance']    = 0; // initial placeholder

header('Location: dashboard.php');
exit;