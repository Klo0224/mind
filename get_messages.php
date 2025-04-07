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

    // Get messages between student and MHP
    $stmt = $conn->prepare("
        SELECT message, sender_type, timestamp 
        FROM Messages 
        WHERE (student_id = ? AND mhp_id = ?)
        ORDER BY timestamp ASC
    ");
    $stmt->bind_param("ii", $student_id, $mhp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>