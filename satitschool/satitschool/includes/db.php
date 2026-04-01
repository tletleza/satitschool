<?php
/**
 * ไฟล์ db.php: ใช้สำหรับเชื่อมต่อฐานข้อมูล MySQL ด้วย PDO (PHP Data Objects)
 * ซึ่งรอนับว่าเป็นวิธีที่ปลอดภัยและทันสมัยที่สุดใน PHP
 */
$host = 'localhost';
$db   = 'satitschool';
$user = 'root';
$pass = ''; // รหัสผ่านเริ่มต้นของ XAMPP มักจะเป็นค่าว่าง
$charset = 'utf8mb4'; // รองรับภาษาไทยและอิโมจิได้อย่างสมบูรณ์

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ให้พ่น Error ออกมาเมื่อมีคำสั่ง SQL ผิดพลาด
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ให้ผลลัพธ์ที่ดึงออกมาเป็น Array ที่อ้างอิงด้วยชื่อคอลัมน์
    PDO::ATTR_EMULATE_PREPARES   => false,                 // ปิดการจำลอง Prepare Statement เพื่อความปลอดภัย (ป้องกัน SQL Injection 100%)
];

try {
    // สร้างการเชื่อมต่อเข้ากับ Database
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // หากเชื่อมต่อไม่ได้ ให้หยุดการทำงานและแจ้ง Error
    die("Database connection failed: " . $e->getMessage());
}
?>
