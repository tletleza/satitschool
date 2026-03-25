<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/check_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$subject_id = $_POST['subject_id'];
$classroom_id = $_POST['classroom_id'];
$scores = $_POST['scores'] ?? [];

$acad_stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
$academic_year_id = $acad_stmt->fetchColumn() ?: 1;

try {
    $pdo->beginTransaction();

    foreach ($scores as $student_id => $score) {
        if ($score === '' || !is_numeric($score)) continue;

        $score = (float)$score;
        if($score > 100) $score = 100;
        if($score < 0) $score = 0;

        // PHP Grade Logic (Security check before saving)
        if ($score >= 80) $grade = '4';
        elseif ($score >= 75) $grade = '3.5';
        elseif ($score >= 70) $grade = '3';
        elseif ($score >= 65) $grade = '2.5';
        elseif ($score >= 60) $grade = '2';
        elseif ($score >= 55) $grade = '1.5';
        elseif ($score >= 50) $grade = '1';
        else $grade = '0';

        // ใช้ INSERT ... ON DUPLICATE KEY UPDATE (ตามที่สั่ง)
        // หมายเหตุ: ตาราง academic_records ของเรามี UNIQUE(student_id, subject_id, academic_year_id)
        $stmt = $pdo->prepare("
            INSERT INTO academic_records (student_id, subject_id, academic_year_id, midterm_score, final_score, grade, recorded_by) 
            VALUES (?, ?, ?, 0, ?, ?, ?)
            ON DUPLICATE KEY UPDATE final_score = VALUES(final_score), grade = VALUES(grade), recorded_by = VALUES(recorded_by)
        ");
        $stmt->execute([$student_id, $subject_id, $academic_year_id, $score, $grade, $_SESSION['user_id']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
