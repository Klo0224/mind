<?php
session_start();
$_SESSION = array();
session_close();
header("location: landingpage.html");
?>