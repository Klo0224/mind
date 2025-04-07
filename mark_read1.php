<?php
session_start();
require_once("auth.php");
require_once("config.php");

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['email'])) {
        throw new Exception("Not authenticated");
    }

    // Get current user ID
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found");
    }

    $student_id = $user['id'];
    $mhp_id = isset($_POST['mhp_id']) ? (int)$_POST['mhp_id'] : 0;

    if ($mhp_id === 0) {
        throw new Exception("Missing mhp_id");
    }

    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE Messages 
        SET is_read = 1 
        WHERE student_id = ? AND mhp_id = ? AND is_read = 0
    ");
    $stmt->bind_param("ii", $student_id, $mhp_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'updated' => $stmt->affected_rows
        ]);
    } else {
        throw new Exception("Failed to mark messages as read: " . $stmt->error);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>