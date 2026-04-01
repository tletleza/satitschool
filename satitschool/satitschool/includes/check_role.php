<?php
/**
 * ไฟล์ check_role.php: จัดการระบบบริหารจัดการสิทธิ์ (Role-Based Access Control)
 * ฟังก์ชันหลักคือ authorize() ใช้สำหรับเปิด/ปิด สิทธิ์การเข้าถึงหน้าเว็บตามบทบาทของผู้ใช้
 */

function authorize($allowed_roles) {
    // 1. ตรวจสอบว่าใน Session มีการระบุบทบาท (Role) ของผู้ใช้ที่ล็อกอินอยู่หรือไม่
    if (!isset($_SESSION['role'])) {
        // หากไม่มีข้อมูล ให้กลับไปที่หน้า Login
        header("Location: login.php");
        exit;
    }

    // 2. ตรวจสอบว่า "บทบาทปัจจุบัน" ของผู้ใช้อยู่ในรายการที่ "อนุญาตให้เข้าถึง" ได้หรือไม่
    // เช่น หากหน้าเว็บนี้อนุญาตเฉพาะ admin ค่า $allowed_roles จะเป็น ['admin']
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // หากผู้ใช้ไม่มีสิทธิ์ ให้แสดงข้อความเตือน (Alert) และดีดกลับไปที่ Dashboard (index.php)
        echo "<script>
            alert('ขออภัย! คุณไม่มีสิทธิ์เข้าใช้งานในส่วนนี้ (เฉพาะบุคคลที่ได้รับอนุญาตเท่านั้น)');
            window.location.href = 'index.php';
        </script>";
        exit;
    }
}
?>
