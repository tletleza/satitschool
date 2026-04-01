<?php
/**
 * ไฟล์ check_auth.php: ใช้ตรวจสอบการเข้าสู่ระบบ (Authentication)
 * ระบบจะเริ่ม Session เพื่อดึงข้อมูลผู้ใช้ และตรวจสอบว่ามีการ Login ทิ้งไว้หรือไม่
 */
// เริ่ม Session ถ้ายังไม่ได้เริ่ม (เพื่อป้องกัน error session_start() ซ้ำ)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามีรหัสผู้ใช้ (user_id) ใน Session หรือไม่
if (!isset($_SESSION['user_id'])) {
    // กรณีที่ 1: ถ้าเป็นหน้า API (เรียกผ่าน AJAX) ให้ส่งค่ากลับเป็น JSON แทนการ Redirect ไปหน้า Login
    if (strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized: กรุณาเข้าสู่ระบบใหม่']);
        exit;
    }
    // กรณีที่ 2: ถ้าเป็นหน้าเว็บปกติ ให้ดีดกลับไปหน้า Login.php ทันที
    header("Location: login.php");
    exit;
}

// ฟังก์ชันเสริมสำหรับเช็คสิทธิ์แบบรวดเร็ว
if (!function_exists('checkRole')) {
    function checkRole($allowed_roles) {
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
            if (strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized: คุณไม่มีสิทธิ์เข้าถึง']);
                exit;
            }
            header("Location: index.php?error=unauthorized");
            exit;
        }
    }
}
