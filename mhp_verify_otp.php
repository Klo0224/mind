<?php
session_start();

if (isset($_POST['verifyOTP'])) {
    $enteredOTP = $_POST['otp'];

    // Check if the OTP matches the one stored in the session
    if ($enteredOTP == $_SESSION['otp']) {
        echo "<script type='text/javascript'>
                alert('OTP is verified, You can now change you password..');
                window.location.href = 'mhp_reset_password.html';
              </script>";
        exit();
    } else {
        // echo "Invalid OTP. Please try again.";
        echo "<script type='text/javascript'>
                alert('Invalid OTP. Please try again..');
                window.location.href = 'mhp_enter_otp.html';
                </script>";
    }
}
?>
