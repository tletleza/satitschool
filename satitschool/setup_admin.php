<?php
require_once 'includes/db.php';

try {
    // 1. ตรวจสอบและสร้าง Role Admin ถ้ายังไม่มี
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'admin', 'System Administrator')");

    // 2. กำหนด Username และ Password สำหรับ Admin
    $username = 'admin';
    $password = 'admin123'; // คุณสามารถเปลี่ยนรหัสผ่านตรงนี้ได้
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $role_id = 1; // ID ของ Admin

    // 3. ตรวจสอบว่ามี User นี้หรือยัง
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() == 0) {
        // 4. Insert ข้อมูล Admin ลงตาราง users
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role_id, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$username, $password_hash, $role_id]);
        echo "สร้างบัญชี Admin เรียบร้อยแล้ว!<br>";
        echo "Username: <b>$username</b><br>";
        echo "Password: <b>$password</b>";
    } else {
        echo "บัญชี Admin มีอยู่ในระบบแล้ว (หากลืมรหัสผ่าน ต้องทำการ Update password_hash ในฐานข้อมูลใหม่)";
    }
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>
