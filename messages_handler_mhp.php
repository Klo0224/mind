<?php
include("auth.php");
include("config.php");
require __DIR__ . '/vendor/autoload.php'; // Pusher library

use Pusher\Pusher;

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error
    ]);
    exit;
}

try {
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }

    // Ensure MHP is logged in
    if (!isset($_SESSION['mhp_id'])) {
        sendResponse(false, null, 'Not authenticated');
    }

    // The MHP's ID from the MHP table
    $mhp_id = (int) $_SESSION['mhp_id'];

    // The student's ID comes from POST as 'receiver_id'
    $student_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message    = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Validate input
    if ($student_id === 0 || $message === '') {
        sendResponse(false, null, 'Missing required parameters');
    }

    // Insert using the new schema: (student_id, mhp_id, sender_type, receiver_type, message)
    $sql = "
        INSERT INTO Messages 
            (student_id, mhp_id, sender_type, receiver_type, message)
        VALUES 
            (?, ?, 'MHP', 'student', ?)
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // We have 3 placeholders: i, i, s
    $stmt->bind_param("iis", $student_id, $mhp_id, $message);

    if ($stmt->execute()) {
        // Initialize Pusher for real-time messaging
        $pusher = new Pusher(
            '561b69476711bf54f56f', 
            '10b81fe10e9b7efc75ff', 
            '1927783',
            [
                'cluster' => 'ap1',
                'useTLS'  => true
            ]
        );

        // Broadcast message to the student's channel
        // For example, rename 'sender_id' to 'mhp_id' for clarity
        $pusher->trigger("chat_$student_id", 'new-message', [
            'mhp_id'    => $mhp_id,
            'student_id'=> $student_id,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Send success response
        sendResponse(true, [
            'message_id' => $conn->insert_id,
            'timestamp'  => date('Y-m-d H:i:s')
        ]);
    } else {
        sendResponse(false, null, 'Failed to send message: ' . $stmt->error);
    }

} catch (Exception $e) {
    sendResponse(false, null, $e->getMessage());
}

?>