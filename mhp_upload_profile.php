<?php
session_start();
include("connect.php");

header('Content-Type: application/json');

// Debug session
error_log("Session email: " . $_SESSION['email']);
error_log("Session ID: " . (isset($_SESSION['mhp_id']) ? $_SESSION['mhp_id'] : "Not Set"));

if (!isset($_SESSION['mhp_id'])) {
    echo json_encode(['success' => false, 'error' => 'User session invalid']);
    exit;
}

try {
    $mhp_id = $_SESSION['mhp_id'];

    // Check for valid uploaded file
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or an upload error occurred.');
    }

    $file = $_FILES['profile_image'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Allowed file types
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExt, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.');
    }

    // Generate unique file name
    $newFileName = "mhp_profile_{$mhp_id}_" . time() . ".$fileExt";
    $uploadDir = 'uploads/mhp_profiles/';
    $uploadPath = $uploadDir . $newFileName;

    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // Update profile_image in the database
    $stmt = $conn->prepare("UPDATE mhp SET profile_image = ? WHERE id = ?");
    $stmt->bind_param("si", $uploadPath, $mhp_id);

    if (!$stmt->execute()) {
        throw new Exception('Database update failed: ' . $stmt->error);
    }

    // Respond with the new image path
    echo json_encode([
        'success' => true,
        'filepath' => $uploadPath
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>