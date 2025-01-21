
<?php
require_once 'connect.php';

function addCounselor($fname, $lname, $email, $department) {
    global $conn;
    
    $fname = mysqli_real_escape_string($conn, $fname);
    $lname = mysqli_real_escape_string($conn, $lname);
    $email = mysqli_real_escape_string($conn, $email);
    $department = mysqli_real_escape_string($conn, $department);
    
    $query = "INSERT INTO MHP (fname, lname, email, department) VALUES ('$fname','$lname', '$email', '$department')";
    
    return mysqli_query($conn, $query);
}

function getAllCounselors() {
    global $conn;
    
    $query = "SELECT * FROM MHP ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    
    $MHP = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $MHP[] = $row;
    }
    
    return $MHP;
}

function getCounselorById($id) {
    global $conn;
    
    $id = mysqli_real_escape_string($conn, $id);
    $query = "SELECT * FROM MHP WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    
    return mysqli_fetch_assoc($result);
}

function updateCounselor($id, $fname, $lname, $email, $department) {
    global $conn;
    
    $id = mysqli_real_escape_string($conn, $id);
    $fname = mysqli_real_escape_string($conn, $fname);
    $lname = mysqli_real_escape_string($conn, $lname);
    $email = mysqli_real_escape_string($conn, $email);
    $department = mysqli_real_escape_string($conn, $department);
    
     $query = "UPDATE MHP SET fname = '$fname', lname ='$lname', email = '$email', department = '$department' WHERE id = '$id'";

    
    return mysqli_query($conn, $query);
}

function deleteCounselor($id) {
    global $conn;
    
    $id = mysqli_real_escape_string($conn, $id);
    $query = "DELETE FROM MHP WHERE id = '$id'";
    
    return mysqli_query($conn, $query);
}
?>