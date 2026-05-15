<?php
include 'db.php'; // your database connection file

if (isset($_GET['id']) && isset($_GET['status'])) {

    $id = (int)$_GET['id'];
    $status = trim($_GET['status']);

    // allow only valid statuses
    $allowed = ['active', 'pending', 'dispute'];

    if (in_array($status, $allowed)) {

        $stmt = $conn->prepare("UPDATE proposal SET status = ? WHERE proposal_id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            header("Location: proposal.php");
            exit;
        } else {
            echo "Failed to update status.";
        }

        $stmt->close();
    } else {
        echo "Invalid status value.";
    }

} else {
    echo "Missing required parameters.";
}
?>