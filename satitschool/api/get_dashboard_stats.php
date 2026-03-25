<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

try {
    $teacher_id = null;
    if ($role === 'teacher') {
        $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $t_stmt->execute([$user_id]);
        $teacher_id = $t_stmt->fetchColumn();
    }

    // 1. Query Attendance Status (Today)
    $att_sql = "SELECT status, COUNT(*) as count FROM attendances ";
    $att_params = [];
    
    // ถ้าเป็นครู ให้กรองเฉพาะวิชาที่ตัวเองสอน
    if ($role === 'teacher') {
        $att_sql .= "JOIN schedules ON attendances.schedule_id = schedules.id WHERE schedules.teacher_id = ? AND ";
        $att_params[] = $teacher_id;
    } else {
        $att_sql .= "WHERE ";
    }
    $att_sql .= "date = CURDATE() GROUP BY status";
    
    $att_stmt = $pdo->prepare($att_sql);
    $att_stmt->execute($att_params);
    $attendance_data = $att_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $attendance_stats = [
        'present' => (int)($attendance_data['present'] ?? 0),
        'absent' => (int)($attendance_data['absent'] ?? 0),
        'late' => (int)($attendance_data['late'] ?? 0),
        'leave' => (int)($attendance_data['leave'] ?? 0)
    ];

    // 2. Query Grade Distribution
    // ถ้าเป็นครู ให้กรองเฉพาะวิชาที่ตัวเองสอน
    $grd_sql = "SELECT grade, COUNT(*) as count FROM academic_records ";
    $grd_params = [];
    if ($role === 'teacher') {
        // กรองจากวิชาที่ครูคนนี้มีในตารางสอน
        $grd_sql .= "WHERE subject_id IN (SELECT DISTINCT subject_id FROM schedules WHERE teacher_id = ?) ";
        $grd_params[] = $teacher_id;
    }
    $grd_sql .= "GROUP BY grade";
    
    $grd_stmt = $pdo->prepare($grd_sql);
    $grd_stmt->execute($grd_params);
    $grades_raw = $grd_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $possible_grades = ['4', '3.5', '3', '2.5', '2', '1.5', '1', '0'];
    $grade_stats = [];
    foreach ($possible_grades as $g) {
        $grade_stats[$g] = (int)($grades_raw[$g] ?? 0);
    }

    // 3. Counts สำหรับ Card
    $summary = [];
    if($role === 'admin') {
        $summary['total_students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $summary['total_teachers'] = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
        $summary['total_subjects'] = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    } else {
        // ครูเห็นเฉพาะนักเรียนในห้องที่ตัวเองสอน (คร่าวๆ)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM student_classrooms sc JOIN schedules sch ON sc.classroom_id = sch.classroom_id WHERE sch.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $summary['total_students'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) FROM schedules WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $summary['total_subjects'] = $stmt->fetchColumn();
        
        $summary['total_teachers'] = 1; // ตัวเอง
    }

    echo json_encode([
        'success' => true,
        'attendance' => $attendance_stats,
        'grades' => $grade_stats,
        'summary' => $summary,
        'role' => $role,
        'timestamp' => date('H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
