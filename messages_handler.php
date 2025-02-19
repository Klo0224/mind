<?php
session_start();  // Make sure session is started if not already

include("auth.php");
include("config.php");
require __DIR__ . '/vendor/autoload.php'; // Pusher library

use Pusher\Pusher;

// Make sure we always return JSON
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
$pusher->trigger("chat_$student_id", 'new-message', [
    'student_id' => $student_id,
    'mhp_id' => $mhp_id,
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s')
]);

error_log("Inserted message: student_id=$student_id, mhp_id=$mhp_id, sender_type=$sender_type, receiver_type=$receiver_type");

// Helper to send JSON responses and exit
function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error
    ]);
    exit;
}

try {
    // 1) Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }

    // 2) Ensure the student is logged in (we expect $_SESSION['student_id'] to be set at login)
    if (!isset($_SESSION['student_id'])) {
        sendResponse(false, null, 'Not authenticated as a student');
    }
    $student_id = (int) $_SESSION['student_id'];

    // 3) Get MHP ID and message from POST (unified param name: 'mhp_id')
    $mhp_id  = isset($_POST['mhp_id']) ? (int) $_POST['mhp_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // 4) Validate input
    if ($mhp_id === 0 || $message === '') {
        sendResponse(false, null, 'Missing required parameters (mhp_id or message)');
    }

    // 5) Insert into new schema (student_id, mhp_id, sender_type='student', receiver_type='MHP', message)
    $sql = "
        INSERT INTO Messages
            (student_id, mhp_id, sender_type, receiver_type, message)
        VALUES
            (?, ?, 'student', 'MHP', ?)
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iis", $student_id, $mhp_id, $message);

    if ($stmt->execute()) {
        // 6) Initialize Pusher for real-time messaging
        $pusher = new Pusher(
            '561b69476711bf54f56f',  // Your Pusher key
            '10b81fe10e9b7efc75ff',  // Your Pusher secret
            '1927783',              // Your Pusher App ID
            [
                'cluster' => 'ap1',
                'useTLS'  => true
            ]
        );

        // 7) Broadcast message to the MHP's channel, e.g. "chat_<mhp_id>"
        $pusher->trigger("chat_{$mhp_id}", 'new-message', [
            'student_id' => $student_id,
            'mhp_id'     => $mhp_id,
            'message'    => $message,
            'timestamp'  => date('Y-m-d H:i:s')
        ]);

        // 8) Send success response
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