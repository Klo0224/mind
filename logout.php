<?php
session_start();
session_unset();
session_destroy();

header("Cache-Control: no-cache, no-store, max-age=0, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: landingpage.php");
exit();
?>
