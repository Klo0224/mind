<?php
require_once 'acc_mhp.php';

if (isset($_GET['id'])) {
    $MHP = getCounselorById($_GET['id']);
    echo json_encode($MHP);
}
?>