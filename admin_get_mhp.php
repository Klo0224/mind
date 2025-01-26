<?php
require_once 'admin_mhp_acc.php';

if (isset($_GET['id'])) {
    $MHP = getCounselorById($_GET['id']);
    echo json_encode($MHP);
}
?>