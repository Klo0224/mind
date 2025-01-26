<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["profile_image"]["name"]);
    
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
        $_SESSION['counselor-image'] = $targetFile; // Store in session

        // Save in database
        $conn = new mysqli("localhost", "root", "", "_Mindsoothe");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "UPDATE mhp SET profile_image='$targetFile' WHERE id=1";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["success" => true, "filepath" => $targetFile]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
        $conn->close();
    } else {
        echo json_encode(["success" => false]);
    }
}
?>
