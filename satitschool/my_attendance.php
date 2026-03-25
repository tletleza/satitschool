<?php
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

authorize(['student']); // เฉพาะนักเรียน

$user_id = $_SESSION['user_id'];

// ดึงรหัสนักเรียนจาก user_id
$st_stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$st_stmt->execute([$user_id]);
$student_id = $st_stmt->fetchColumn();

// ดึงประวัติการเข้าเรียนทั้งหมดของนักเรียนท่านนี้
$stmt = $pdo->prepare("
    SELECT att.*, s.name as subject_name, s.subject_code, t.first_name, t.last_name
    FROM attendances att
    JOIN schedules sc ON att.schedule_id = sc.id
    JOIN subjects s ON sc.subject_id = s.id
    JOIN teachers t ON sc.teacher_id = t.id
    WHERE att.student_id = ?
    ORDER BY att.date DESC, sc.id ASC
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();

$status_labels = [
    'present' => ['label' => 'มาเรียน', 'class' => 'success'],
    'absent' => ['label' => 'ขาดเรียน', 'class' => 'danger'],
    'late' => ['label' => 'มาสาย', 'class' => 'warning'],
    'leave' => ['label' => 'ลาพัก', 'class' => 'info']
];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <h3 style="color: var(--primary-color);"><i class="fas fa-user-clock me-2"></i> ประวัติการเข้าเรียนของฉัน</h3>

    <div class="card card-custom border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>วันที่</th>
                            <th>วิชา</th>
                            <th>ผู้สอน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $h): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($h['date'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($h['subject_code']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($h['subject_name']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($h['first_name'].' '.$h['last_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $status_labels[$h['status']]['class'] ?> fs-6">
                                    <?= $status_labels[$h['status']]['label'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($history)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีข้อมูลการเข้าเรียน</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
