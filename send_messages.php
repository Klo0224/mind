<?php
session_start();
require_once("auth.php");
require_once("config.php");
require __DIR__ . '/vendor/autoload.php';

use Pusher\Pusher;

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['email'])) {
        throw new Exception("Not authenticated");
    }

    // Get current user info
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT id, firstName, lastName FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found");
    }

    $student_id = $user['id'];
    $mhp_id = isset($_POST['mhp_id']) ? (int)$_POST['mhp_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Validate input
    if ($mhp_id === 0 || $message === '') {
        throw new Exception("Missing required parameters");
    }

    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO Messages 
        (student_id, mhp_id, sender_type, receiver_type, message, is_read) 
        VALUES (?, ?, 'student', 'MHP', ?, 0)
    ");
    $stmt->bind_param("iis", $student_id, $mhp_id, $message);
    
    if ($stmt->execute()) {
        // Get MHP info for Pusher
        $mhp_stmt = $conn->prepare("SELECT fname, lname FROM MHP WHERE id = ?");
        $mhp_stmt->bind_param("i", $mhp_id);
        $mhp_stmt->execute();
        $mhp_result = $mhp_stmt->get_result();
        $mhp = $mhp_result->fetch_assoc();
        
        // Initialize Pusher
        $pusher = new Pusher(
            '561b69476711bf54f56f', 
            '10b81fe10e9b7efc75ff', 
            '1927783',
            [
                'cluster' => 'ap1',
                'useTLS'  => true
            ]
        );

        // Trigger event to MHP's channel
        $pusher->trigger("mhp_chat_$mhp_id", 'new-message', [
            'student_id' => $student_id,
            'mhp_id' => $mhp_id,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'student_name' => $user['firstName'] . ' ' . $user['lastName'],
            'mhp_name' => $mhp['fname'] . ' ' . $mhp['lname']
        ]);

        echo json_encode([
            'success' => true,
            'message_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception("Failed to send message: " . $stmt->error);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>