<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

$classroom_id = $_GET['classroom_id'] ?? 0;
$subject_id = $_GET['subject_id'] ?? 0;

$acad_stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
$academic_year_id = $acad_stmt->fetchColumn() ?: 1;

$stmt = $pdo->prepare("
    SELECT s.id, s.student_code, s.first_name, s.last_name, sc.roll_number, ar.final_score as score, ar.grade 
    FROM students s 
    JOIN student_classrooms sc ON s.id = sc.student_id 
    LEFT JOIN academic_records ar ON s.id = ar.student_id AND ar.subject_id = ? AND ar.academic_year_id = ?
    WHERE sc.classroom_id = ?
    ORDER BY sc.roll_number ASC, s.student_code ASC
");
$stmt->execute([$subject_id, $academic_year_id, $classroom_id]);
$students = $stmt->fetchAll();

echo json_encode($students);
?>
