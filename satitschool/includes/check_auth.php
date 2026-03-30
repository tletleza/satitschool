<?php
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ฟังก์ชันเสริมสำหรับเช็คสิทธิ์แบบรวดเร็ว
function checkRole($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: index.php?error=unauthorized");
        exit;
    }
}
?>
