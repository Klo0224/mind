<?php
session_start();
include("connect.php");

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['mhp_id'])) {
        throw new Exception("User session invalid.");
    }

    $mhp_id = $_SESSION['mhp_id'];

    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] != UPLOAD_ERR_OK) {
        throw new Exception("File upload error.");
    }

    $targetDir = "uploads/";
    $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($fileType), $allowedTypes)) {
        throw new Exception("Invalid file type: " . $fileType);
    }

    if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
        throw new Exception("Failed to save uploaded file.");
    }

    // Make sure the new path is stored in the database
    $stmt = $conn->prepare("UPDATE mhp SET profile_image = ? WHERE id = ?");
    $stmt->bind_param("si", $targetFilePath, $mhp_id);
    if (!$stmt->execute()) {
        throw new Exception("Database update failed.");
    }
    $stmt->close();

    echo json_encode([
        'success' => true, 
        'image_url' => $targetFilePath,
        'filepath' => $targetFilePath,
        'timestamp' => time() // Add timestamp to help with cache busting
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
