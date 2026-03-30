<?php
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

// เฉพาะ Student เท่านั้น (Admin/Teacher ดูผ่านหน้าอื่น)
authorize(['student']);

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลนักเรียนและห้องเรียนปัจจุบัน
$stmt = $pdo->prepare("
    SELECT s.id as student_id, s.student_code, s.first_name, s.last_name, c.room_name, g.level_name 
    FROM students s
    JOIN student_classrooms sc ON s.id = sc.student_id
    JOIN classrooms c ON sc.classroom_id = c.id
    JOIN grades g ON c.grade_id = g.id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$grades = [];
if ($student) {
    // ดึงเกรดทุวิชาในปีการศึกษาปัจจุบัน
    $stmt = $pdo->prepare("
        SELECT sub.subject_code, sub.name as subject_name, sub.credit, ar.midterm_score, ar.final_score, ar.grade
        FROM academic_records ar
        JOIN subjects sub ON ar.subject_id = sub.id
        WHERE ar.student_id = ?
        ORDER BY sub.subject_code ASC
    ");
    $stmt->execute([$student['student_id']]);
    $grades = $stmt->fetchAll();
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="card card-custom border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #6D9E51, #bcd9a2);">
        <div class="card-body text-white">
            <h3 class="mb-1"><i class="fas fa-poll-h me-2"></i> ผลการเรียนของฉัน</h3>
            <p class="mb-0 opacity-75">
                <?= htmlspecialchars($student['student_code']) ?> : <?= htmlspecialchars($student['first_name']." ".$student['last_name']) ?> | 
                <?= htmlspecialchars($student['level_name']." (".$student['room_name'].")") ?>
            </p>
        </div>
    </div>

    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="120">รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th class="text-center" width="100">หน่วยกิต</th>
                            <th class="text-center" width="150">คะแนนเก็บ</th>
                            <th class="text-center" width="150">คะแนนปลายภาค</th>
                            <th class="text-center" width="100">เกรด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grades)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">ยังไม่มีข้อมูลผลการเรียน</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($grades as $g): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($g['subject_code']) ?></td>
                            <td><?= htmlspecialchars($g['subject_name']) ?></td>
                            <td class="text-center"><?= number_format($g['credit'], 1) ?></td>
                            <td class="text-center text-muted"><?= $g['midterm_score'] ?></td>
                            <td class="text-center text-muted"><?= $g['final_score'] ?></td>
                            <td class="text-center">
                                <span class="badge fs-6 <?= ($g['grade'] == '0' ? 'bg-danger' : 'bg-success') ?> px-3">
                                    <?= $g['grade'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
