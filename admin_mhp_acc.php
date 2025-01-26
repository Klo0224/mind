
<?php
require_once 'connect.php';

function addCounselor($fname, $lname, $email, $department) {
    global $conn;
    
    // Sanitize input data to prevent SQL injection
    $fname = mysqli_real_escape_string($conn, $fname);
    $lname = mysqli_real_escape_string($conn, $lname);
    $email = mysqli_real_escape_string($conn, $email);
    $department = mysqli_real_escape_string($conn, $department);

    $defaultPassword = md5('123');

    // Check if the email already exists
    $checkQuery = "SELECT email FROM MHP WHERE email = '$email'";
    $result = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($result) > 0) {
        return "error_email_exists";  // Return error if email exists
    }

    // Insert new counselor if email doesn't exist
    $query = "INSERT INTO MHP (fname, lname, email, department, password) 
              VALUES ('$fname','$lname', '$email', '$department', '$defaultPassword')";

    if (mysqli_query($conn, $query)) {
        return "success";  // Return success message
    } else {
        return "error";  // Return error message
    }
}



function getAllCounselors() {
    global $conn;

    $query = "SELECT * FROM MHP ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);

    $counselors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $counselors[] = $row;
    }

    return $counselors;
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
    
    // $query = "UPDATE MHP SET fname = '$fname' '$lname', email = '$email', department = '$department' WHERE id = '$id'";
    $query = "UPDATE MHP SET fname = '$fname', lname= '$lname', email = '$email', department = '$department' WHERE id = '$id'";
    
    return mysqli_query($conn, $query);
}

function deleteCounselor($id) {
    global $conn;
    
    $id = mysqli_real_escape_string($conn, $id);
    $query = "DELETE FROM MHP WHERE id = '$id'";
    
    return mysqli_query($conn, $query);
}
?>