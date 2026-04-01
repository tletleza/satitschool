<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

try {
    $teacher_id = null;
    if ($role === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $teacher_id = $stmt->fetchColumn();
    }

    // 1. Attendance Today (RBAC: Teacher filters by their schedules)
    $att_sql = "SELECT status, COUNT(*) as count FROM attendances a ";
    $params = [];
    if ($role === 'teacher') {
        $att_sql .= "JOIN schedules s ON a.schedule_id = s.id WHERE s.teacher_id = ? AND ";
        $params[] = $teacher_id;
    } else {
        $att_sql .= "WHERE ";
    }
    $att_sql .= "date = CURDATE() GROUP BY status";
    
    $stmt = $pdo->prepare($att_sql);
    $stmt->execute($params);
    $attendance_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $attendance_stats = [
        'present' => (int)($attendance_raw['present'] ?? 0),
        'absent' => (int)($attendance_raw['absent'] ?? 0),
        'late' => (int)($attendance_raw['late'] ?? 0),
        'leave' => (int)($attendance_raw['leave'] ?? 0)
    ];

    // 2. Grade Distribution (RBAC: Teacher filters by their subjects)
    $grd_sql = "SELECT grade, COUNT(*) as count FROM academic_records ";
    $params = [];
    if ($role === 'teacher') {
        $grd_sql .= "WHERE subject_id IN (SELECT DISTINCT subject_id FROM schedules WHERE teacher_id = ?) ";
        $params[] = $teacher_id;
    }
    $grd_sql .= "GROUP BY grade";
    
    $stmt = $pdo->prepare($grd_sql);
    $stmt->execute($params);
    $grades_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $all_grades = ['4', '3.5', '3', '2.5', '2', '1.5', '1', '0'];
    $grade_stats = [];
    foreach ($all_grades as $g) {
        $grade_stats[$g] = (int)($grades_raw[$g] ?? 0);
    }

    // 3. Total Counts (Global)
    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_teachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();

    echo json_encode([
        'success' => true,
        'attendance' => $attendance_stats,
        'grades' => $grade_stats,
        'totals' => [
            'students' => (int)$total_students,
            'teachers' => (int)$total_teachers
        ],
        'timestamp' => date('H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
