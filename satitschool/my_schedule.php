<?php
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

// เฉพาะ Student เท่านั้น
authorize(['student']);

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลนักเรียนและห้องเรียน
$stmt = $pdo->prepare("
    SELECT s.id as student_id, c.id as classroom_id, c.room_name, g.level_name 
    FROM students s
    JOIN student_classrooms sc ON s.id = sc.student_id
    JOIN classrooms c ON sc.classroom_id = c.id
    JOIN grades g ON c.grade_id = g.id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$schedules = [];
if ($student) {
    // ดึงตารางเรียนของห้องนี้
    $stmt = $pdo->prepare("
        SELECT sc.day_of_week, sc.start_time, sc.end_time, sub.subject_code, sub.name as subject_name, t.first_name, t.last_name
        FROM schedules sc
        JOIN subjects sub ON sc.subject_id = sub.id
        JOIN teachers t ON sc.teacher_id = t.id
        WHERE sc.classroom_id = ?
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time ASC
    ");
    $stmt->execute([$student['classroom_id']]);
    $schedules = $stmt->fetchAll(PDO::FETCH_GROUP);
}

// รายการวันในสัปดาห์
$days = ['Monday' => 'จันทร์', 'Tuesday' => 'อังคาร', 'Wednesday' => 'พุธ', 'Thursday' => 'พฤหัสบดี', 'Friday' => 'ศุกร์'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 text-white p-3 rounded shadow-sm" style="background: linear-gradient(135deg, #A82323, #7e1a1a);">
        <h3 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> ตารางเรียนของฉัน</h3>
        <p class="mb-0">ห้องเรียน: <?= htmlspecialchars($student['level_name']." (".$student['room_name'].")") ?></p>
    </div>

    <div class="row g-4">
        <?php foreach($days as $day_en => $day_th): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-clock me-2"></i> วัน<?= $day_th ?></h5>
                </div>
                <div class="card-body pt-0">
                    <ul class="list-group list-group-flush">
                        <?php if (isset($schedules[$day_en])): ?>
                            <?php foreach($schedules[$day_en] as $sch): ?>
                            <li class="list-group-item px-0 py-3 border-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-light text-dark border"><?= substr($sch['start_time'], 0, 5) ?> - <?= substr($sch['end_time'], 0, 5) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($sch['subject_code']) ?></small>
                                </div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($sch['subject_name']) ?></div>
                                <div class="small text-muted"><i class="fas fa-user-tie me-1"></i> อ.<?= htmlspecialchars($sch['first_name']." ".$sch['last_name']) ?></div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item px-0 py-4 text-center text-muted border-0">ไม่มีคาบเรียน</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
