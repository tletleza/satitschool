<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

$schedule_id = $_GET['schedule_id'] ?? 0;

// ดึงตัวแปรห้องเรียนจาก schedule_id
$stmt = $pdo->prepare("SELECT classroom_id FROM schedules WHERE id = ?");
$stmt->execute([$schedule_id]);
$classroom_id = $stmt->fetchColumn();

// ดึงนักเรียนในห้องนั้น พร้อมสถานะการเช็คชื่อวันนี้ (ถ้ามี)
$stmt = $pdo->prepare("
    SELECT s.id as student_id, s.student_code, s.first_name, s.last_name, sc.roll_number, att.status 
    FROM students s
    JOIN student_classrooms sc ON s.id = sc.student_id
    LEFT JOIN attendances att ON s.id = att.student_id AND att.schedule_id = ? AND att.date = CURDATE()
    WHERE sc.classroom_id = ?
    ORDER BY sc.roll_number ASC, s.student_code ASC
");
$stmt->execute([$schedule_id, $classroom_id]);
$list = $stmt->fetchAll();

echo json_encode($list);
?>
