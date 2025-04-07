<?php
session_start();
include("config.php");
require __DIR__ . '/vendor/autoload.php';



$pusher = new Pusher\Pusher(
    '561b69476711bf54f56f', // App key
    'your_app_secret',      // App secret
    'your_app_id',          // App ID
    [
        'cluster' => 'ap1',
        'encrypted' => true
    ]
);

$socket_id = $_POST['socket_id'];
$channel_name = $_POST['channel_name'];

// Validate the user has access to this channel
$parts = explode('-', $channel_name);
if (count($parts) !== 4 || $parts[0] !== 'private' || $parts[1] !== 'chat') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$student_id = $parts[2] ?? null;
$mhp_id = $parts[3] ?? null;

// Verify the student is part of this chat
if ($student_id && $mhp_id && $student_id == $_SESSION['student_id']) {
    $auth = $pusher->socket_auth($channel_name, $socket_id);
    echo $auth;
} else {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
?>