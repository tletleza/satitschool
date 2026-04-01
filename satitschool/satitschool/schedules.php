<?php
/**
 * ไฟล์ schedules.php: ระบบจัดการตารางสอนรวม (Admin)
 * ใช้สำหรับกำหนดวิชา ครูผู้สอน และห้องเรียน ในแต่ละวันและเวลา (Monday-Friday)
 */
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
authorize(['admin']); // จำกัดสิทธิ์เฉพาะ Admin เท่านั้น
require_once 'includes/db.php';

$msg = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$id]);
    $msg = "ลบข้อมูลตารางเรียนสำเร็จ!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_schedule'])) {
    $id = $_POST['id'] ?? '';
    // Auto-fetch current academic year (default 1 if empty)
    $acad_stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
    $academic_year_id = $acad_stmt->fetchColumn() ?: 1;
    
    $classroom_id = $_POST['classroom_id'];
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE schedules SET classroom_id=?, subject_id=?, teacher_id=?, day_of_week=?, start_time=?, end_time=? WHERE id=?");
        $stmt->execute([$classroom_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $id]);
        $msg = "อัปเดตตารางสอนสำเร็จ!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO schedules (classroom_id, subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$classroom_id, $subject_id, $teacher_id, $academic_year_id, $day_of_week, $start_time, $end_time]);
        $msg = "เพิ่มตารางสอนสำเร็จ!";
    }
}

// Fetch all schedules linked with names
$schedules = $pdo->query("
    SELECT sc.*, 
           c.room_name, g.level_name, c.building, c.floor, c.room_number,
           s.name as subject_name, s.subject_code,
           t.first_name, t.last_name 
    FROM schedules sc 
    JOIN classrooms c ON sc.classroom_id = c.id
    JOIN grades g ON c.grade_id = g.id
    JOIN subjects s ON sc.subject_id = s.id
    JOIN teachers t ON sc.teacher_id = t.id
    ORDER BY FIELD(sc.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), sc.start_time
")->fetchAll();

$classrooms = $pdo->query("SELECT c.id, c.room_name, g.level_name FROM classrooms c JOIN grades g ON c.grade_id = g.id")->fetchAll();
$subjects = $pdo->query("SELECT id, name, subject_code FROM subjects")->fetchAll();
$teachers = $pdo->query("SELECT id, first_name, last_name FROM teachers")->fetchAll();

$days = [
    'Monday' => 'จันทร์',
    'Tuesday' => 'อังคาร',
    'Wednesday' => 'พุธ',
    'Thursday' => 'พฤหัสบดี',
    'Friday' => 'ศุกร์',
    'Saturday' => 'เสาร์',
    'Sunday' => 'อาทิตย์'
];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-calendar-alt me-2"></i> ระบบจัดการตารางเรียน</h3>
        <button class="btn btn-primary" style="background-color: var(--primary-color); border:none;" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="clearForm()">
            <i class="fas fa-plus-circle me-1"></i> เพิ่มเวลาสอน
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm">
            <i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card card-custom">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="card-custom-header">
                        <tr>
                            <th>วัน</th>
                            <th>เวลา</th>
                            <th>วิชา</th>
                            <th>ครูผู้สอน</th>
                            <th>ชั้น/ห้องเรียน</th>
                            <th>สถานที่เรียน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($schedules as $sc): ?>
                        <tr>
                            <td><span class="badge bg-primary fs-6"><?= $days[$sc['day_of_week']] ?></span></td>
                            <td><strong><?= substr($sc['start_time'],0,5) ?> - <?= substr($sc['end_time'],0,5) ?></strong></td>
                            <td class="text-start">
                                <strong><?= htmlspecialchars($sc['subject_code']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($sc['subject_name']) ?></small>
                            </td>
                            <td class="text-start"><?= htmlspecialchars($sc['first_name'] . ' ' . $sc['last_name']) ?></td>
                            <td><?= htmlspecialchars($sc['level_name'].' | '.$sc['room_name']) ?></td>
                            <td class="text-muted small">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <?= htmlspecialchars("อ.".$sc['building']." ช.".$sc['floor']." ห.".$sc['room_number']) ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning mb-1" onclick='editSchedule(<?= json_encode($sc) ?>)'><i class="fas fa-edit"></i></button>
                                <a href="?delete=<?= $sc['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('ยืนยันลบเวลาเรียนนี้?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($schedules)): ?>
                        <tr><td colspan="6" class="py-4 text-muted">ไม่พบข้อมูลตารางเรียน กรุณาเพิ่มข้อมูล</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="schedules.php" class="modal-content">
      <div class="modal-header card-custom-header">
        <h5 class="modal-title" id="modalTitle">จัดตารางเรียน / เวลาสอน</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="sc_id">
        
        <div class="mb-3">
            <label class="form-label fw-bold">เลือกวัน</label>
            <select name="day_of_week" id="sc_day" class="form-select" required>
                <option value="">-- เลือกวัน --</option>
                <?php foreach($days as $en => $th): ?>
                <option value="<?= $en ?>"><?= $th ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="row mb-3">
            <div class="col">
                <label class="form-label fw-bold">เวลาเริ่ม</label>
                <input type="time" name="start_time" id="sc_start" class="form-control" required>
            </div>
            <div class="col">
                <label class="form-label fw-bold">เวลาสิ้นสุด</label>
                <input type="time" name="end_time" id="sc_end" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold"><i class="fas fa-book text-muted"></i> รายวิชา</label>
            <select name="subject_id" id="sc_subject" class="form-select" required>
                <option value="">-- เลือกรายวิชา --</option>
                <?php foreach($subjects as $sub): ?>
                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_code'].' : '.$sub['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold"><i class="fas fa-chalkboard-teacher text-muted"></i> ผู้สอน</label>
            <select name="teacher_id" id="sc_teacher" class="form-select" required>
                <option value="">-- เลือกครูผู้สอน --</option>
                <?php foreach($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold"><i class="fas fa-door-open text-muted"></i> ชั้นและห้องเรียน</label>
            <select name="classroom_id" id="sc_classroom" class="form-select" required>
                <option value="">-- เลือกห้องเรียน --</option>
                <?php foreach($classrooms as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['level_name'] . ' (' . $c['room_name'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" name="submit_schedule" class="btn btn-primary" style="background-color: var(--primary-color); border:none;"><i class="fas fa-save"></i> บันทึกตาราง</button>
      </div>
    </form>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').innerText = 'จัดตารางเรียนใหม่';
    document.getElementById('sc_id').value = '';
    document.getElementById('sc_day').value = '';
    document.getElementById('sc_start').value = '';
    document.getElementById('sc_end').value = '';
    document.getElementById('sc_subject').value = '';
    document.getElementById('sc_teacher').value = '';
    document.getElementById('sc_classroom').value = '';
}

function editSchedule(sc) {
    document.getElementById('modalTitle').innerText = 'แก้ไขตารางเรียน';
    document.getElementById('sc_id').value = sc.id;
    document.getElementById('sc_day').value = sc.day_of_week;
    document.getElementById('sc_start').value = sc.start_time;
    document.getElementById('sc_end').value = sc.end_time;
    document.getElementById('sc_subject').value = sc.subject_id;
    document.getElementById('sc_teacher').value = sc.teacher_id;
    document.getElementById('sc_classroom').value = sc.classroom_id;
    
    var modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
