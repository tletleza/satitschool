<?php
session_start();
// เคลียร์ข้อมูล Session ทั้งหมด
session_unset();
session_destroy();
// กลับหน้า Login
header("Location: login.php");
exit;
?>
