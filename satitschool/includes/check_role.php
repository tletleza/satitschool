<?php
// ไฟล์นี้ใช้สำหรับตรวจสอบสิทธิ์เข้าถึงหน้าเว็บ (RBAC)
// ต้องมีไฟล์ check_auth.php หรือ session_start() เรียกใช้งานก่อนหน้านี้

function authorize($allowed_roles) {
    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit;
    }

    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // หากไม่มีสิทธิ์ ให้ดีดกลับหน้า Dashboard หรือแสดง Error
        echo "<script>
            alert('คุณไม่มีสิทธิ์เข้าใช้งานในส่วนนี้');
            window.location.href = 'index.php';
        </script>";
        exit;
    }
}
?>
