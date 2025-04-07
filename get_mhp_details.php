<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("config.php"); // DB connection

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "error" => "Missing MHP ID"]);
    exit;
}

$mhpId = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT fname, lname, department, profile_image FROM MHP WHERE id = ?");
$stmt->bind_param("i", $mhpId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "mhp" => [
            "fname" => htmlspecialchars($row['fname']),
            "lname" => htmlspecialchars($row['lname']),
            "department" => htmlspecialchars($row['department']),
            "profile_image" => htmlspecialchars($row['profile_image'])
        ]
    ]);
} else {
    echo json_encode(["success" => false, "error" => "MHP not found"]);
}

$conn->close();
?>