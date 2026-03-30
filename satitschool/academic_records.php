<?php
require_once 'includes/check_auth.php';
require_once 'includes/db.php';

$msg = '';

$acad_stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
$academic_year_id = $acad_stmt->fetchColumn() ?: 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grades'])) {
    $subject_id = $_POST['subject_id'];
    $classroom_id = $_POST['classroom_id'];
    $scores = $_POST['scores'] ?? []; // array [student_id => score]
    
    foreach ($scores as $student_id => $score) {
        if ($score === '' || !is_numeric($score)) continue;
        
        $score = (float)$score;
        if($score > 100) $score = 100;
        if($score < 0) $score = 0;

        if ($score >= 80) $grade = '4';
        elseif ($score >= 75) $grade = '3.5';
        elseif ($score >= 70) $grade = '3';
        elseif ($score >= 65) $grade = '2.5';
        elseif ($score >= 60) $grade = '2';
        elseif ($score >= 55) $grade = '1.5';
        elseif ($score >= 50) $grade = '1';
        else $grade = '0';

        $stmt = $pdo->prepare("
            INSERT INTO academic_records (student_id, subject_id, academic_year_id, midterm_score, final_score, grade, recorded_by) 
            VALUES (?, ?, ?, 0, ?, ?, ?)
            ON DUPLICATE KEY UPDATE final_score = VALUES(final_score), grade = VALUES(grade), recorded_by = VALUES(recorded_by)
        ");
        $stmt->execute([$student_id, $subject_id, $academic_year_id, $score, $grade, $_SESSION['user_id']]);
    }
    $msg = "บันทึกผลการเรียนเรียบร้อยแล้ว! (ระบบคำนวณเกรดให้อัตโนมัติ)";
}

$subjects = $pdo->query("SELECT id, name, subject_code FROM subjects")->fetchAll();
$classrooms = $pdo->query("SELECT c.id, c.room_name, g.level_name FROM classrooms c JOIN grades g ON c.grade_id = g.id")->fetchAll();

$sel_subject = $_GET['subject_id'] ?? '';
$sel_classroom = $_GET['classroom_id'] ?? '';

$students = [];
if ($sel_subject && $sel_classroom) {
    // Helper functionality for prototype: auto-populate student_classrooms if completely empty
    $check = $pdo->prepare("SELECT COUNT(*) FROM student_classrooms WHERE classroom_id = ?");
    $check->execute([$sel_classroom]);
    if ($check->fetchColumn() == 0) {
        $sys_check = $pdo->query("SELECT COUNT(*) FROM student_classrooms")->fetchColumn();
        if ($sys_check == 0 && $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() > 0) {
            $all_s = $pdo->query("SELECT id FROM students")->fetchAll();
            $rn = 1;
            foreach($all_s as $st) {
                 $pdo->exec("INSERT IGNORE INTO student_classrooms (student_id, classroom_id, roll_number) VALUES ({$st['id']}, $sel_classroom, $rn)");
                 $rn++;
            }
        }
    }

    $stmt = $pdo->prepare("
        SELECT s.id, s.student_code, s.first_name, s.last_name, sc.roll_number, ar.final_score as score, ar.grade 
        FROM students s 
        JOIN student_classrooms sc ON s.id = sc.student_id 
        LEFT JOIN academic_records ar ON s.id = ar.student_id AND ar.subject_id = ? AND ar.academic_year_id = ?
        WHERE sc.classroom_id = ?
        ORDER BY sc.roll_number ASC, s.student_code ASC
    ");
    $stmt->execute([$sel_subject, $academic_year_id, $sel_classroom]);
    $students = $stmt->fetchAll();
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-award me-2"></i> ระบบบันทึกผลการเรียน</h3>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ฟอร์มค้นหา/เลือกวิชาและชั้นเรียน -->
    <div class="card card-custom mb-4">
        <div class="card-header card-custom-header">
            <i class="fas fa-filter me-1"></i> เลือกวิชาและห้องเรียนเพื่อบันทึกคะแนน
        </div>
        <div class="card-body">
            <form method="GET" action="academic_records.php" class="row gx-3 gy-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold">รายวิชา</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- เลือกวิชา --</option>
                        <?php foreach($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= $sel_subject == $sub['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_code'].' : '.$sub['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">ชั้นเรียน / ห้องเรียน</label>
                    <select name="classroom_id" class="form-select" required>
                        <option value="">-- เลือกห้องเรียน --</option>
                        <?php foreach($classrooms as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $sel_classroom == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['level_name'] . ' (' . $c['room_name'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" style="background-color: var(--primary-color); border:none;">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางบันทึกคะแนน -->
    <?php if ($sel_subject && $sel_classroom): ?>
    <div class="card card-custom">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark"><i class="fas fa-users me-2"></i> รายชื่อนักเรียน</h5>
            <span class="badge bg-primary fs-6"><?= count($students) ?> คน</span>
        </div>
        <div class="card-body">
            <?php if(count($students) > 0): ?>
            <form method="POST" action="academic_records.php?subject_id=<?= $sel_subject ?>&classroom_id=<?= $sel_classroom ?>">
                <input type="hidden" name="subject_id" value="<?= htmlspecialchars($sel_subject) ?>">
                <input type="hidden" name="classroom_id" value="<?= htmlspecialchars($sel_classroom) ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">เลขที่</th>
                                <th style="width: 150px;">รหัสประจำตัว</th>
                                <th class="text-start">ชื่อ-นามสกุล</th>
                                <th style="width: 150px;">คะแนนดิบ (100)</th>
                                <th style="width: 100px;">เกรด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $st): ?>
                            <tr>
                                <td><?= htmlspecialchars($st['roll_number'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($st['student_code']) ?></td>
                                <td class="text-start"><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                <td>
                                    <input type="number" name="scores[<?= $st['id'] ?>]" class="form-control text-center text-primary fw-bold" 
                                           min="0" max="100" placeholder="0-100" 
                                           value="<?= isset($st['score']) ? (float)$st['score'] : '' ?>">
                                </td>
                                <td>
                                    <?php if(isset($st['grade']) && $st['grade'] !== null): ?>
                                        <h4 class="mb-0"><span class="badge bg-<?= $st['grade'] == '0' ? 'danger' : 'success' ?>"><?= htmlspecialchars($st['grade']) ?></span></h4>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end mt-4">
                    <button type="submit" name="save_grades" class="btn btn-primary px-5 py-2" style="background-color: var(--primary-color); border:none; font-size:1.1rem;">
                        <i class="fas fa-save me-2"></i> บันทึกผลการเรียน
                    </button>
                </div>
            </form>
            <?php else: ?>
                <!-- แสดงข้อความเมื่อไม่มีนักเรียนที่ผูกกับห้องนี้เลย -->
                <div class="alert alert-warning text-center my-4 py-4">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                    <h4>ไม่พบรายชื่อนักเรียนในห้องเรียนนี้</h4>
                    <p class="mb-0">กรุณาเพิ่มนักเรียนและกำหนดให้อยู่ในห้องเรียนที่ถูกต้องในระบบจัดการข้อมูลนักเรียน</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('input', function() {
        let score = parseFloat(this.value);
        let gradeCell = this.parentElement.nextElementSibling;
        
        if (isNaN(score) || score === '') {
            gradeCell.innerHTML = '<span class="text-muted fst-italic">-</span>';
            return;
        }
        
        if(score > 100) { this.value = 100; score = 100; }
        if(score < 0) { this.value = 0; score = 0; }
        
        let grade = '0';
        let color = 'success';
        
        if (score >= 80) grade = '4';
        else if (score >= 75) grade = '3.5';
        else if (score >= 70) grade = '3';
        else if (score >= 65) grade = '2.5';
        else if (score >= 60) grade = '2';
        else if (score >= 55) grade = '1.5';
        else if (score >= 50) grade = '1';
        else { grade = '0'; color = 'danger'; }
        
        gradeCell.innerHTML = '<h4 class="mb-0"><span class="badge bg-' + color + '">' + grade + '</span></h4>';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
