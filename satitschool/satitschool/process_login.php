<?php
/**
 * ไฟล์ process_login.php: หัวใจหลักของการตรวจสอบสิทธิ์ (Authentication Logic)
 * ทำหน้าที่รับข้อมูลจากฟอร์ม login, ตรวจสอบรหัสผ่านที่เข้ารหัส (Hash), 
 * และสร้าง Session เพื่อให้ระบบจำได้ว่าใครกำลังใช้งานอยู่
 */
session_start();
require_once 'includes/db.php'; // นำเข้าการเชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. ตรวจสอบเบื้องต้นว่ามีการกรอกข้อมูลมาหรือไม่
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header("Location: login.php");
        exit;
    }

    try {
        // Prepare Statement ป้องกัน SQL Injection แน่นอน 100%
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // ตรวจสอบเช็ครหัสผ่าน Hash ด้วยฟังก์ชัน password_verify
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // เช็คสถานะการใช้งานบัญชี
            if ($user['status'] !== 'active') {
                $_SESSION['error'] = 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อแอดมิน';
                header("Location: login.php");
                exit;
            }

            // บันทึกเวลาล็อกอินล่าสุด
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $updateStmt->execute(['id' => $user['id']]);

            // สร้าง Session เก็บรหัสและสิทธิ์ (Role)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['profile_picture'] = $user['profile_picture'];

            // ถ้าสำเร็จให้ Redirect ไปหน้า Dashboard หลัก
            header("Location: index.php");
            exit;

        } else {
            // ไม่พบบัญชีหรือรหัสผิดพลาด
            $_SESSION['error'] = 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง!';
            header("Location: login.php");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
        header("Location: login.php");
        exit;
    }
} else {
    // ถ้าไม่ได้มาจาก POST ปกติ
    header("Location: login.php");
    exit;
}
?>
