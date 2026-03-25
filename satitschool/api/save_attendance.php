<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$student_id = $_POST['student_id'];
$status = $_POST['status'];
$schedule_id = $_POST['schedule_id'];
$user_id = $_SESSION['user_id'];

// Security Check: ตรวจสอบว่าครูคนนี้สอนวิชานี้จริงหรือไม่ (ถ้าไม่ใช่ Admin)
if ($_SESSION['role'] === 'teacher') {
    $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $t_stmt->execute([$user_id]);
    $teacher_id = $t_stmt->fetchColumn();

    $check = $pdo->prepare("SELECT id FROM schedules WHERE id = ? AND teacher_id = ?");
    $check->execute([$schedule_id, $teacher_id]);
    if ($check->rowCount() == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access to this schedule']);
        exit;
    }
}

try {
    // UPSERT Logic: บันทึกหรืออัปเดตสถานะวันนี้
    // หมายเหตุ: ตาราง attendance มี UNIQUE(student_id, schedule_id, date)
    $stmt = $pdo->prepare("
        INSERT INTO attendances (student_id, schedule_id, date, status, recorded_by) 
        VALUES (?, ?, CURDATE(), ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_by = VALUES(recorded_by)
    ");
    $stmt->execute([$student_id, $schedule_id, $status, $user_id]);

    echo json_encode(['status' => 'success', 'message' => 'Attendance saved successfully']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
