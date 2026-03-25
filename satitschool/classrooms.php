<?php
require_once 'includes/check_auth.php';
require_once 'includes/db.php';

// Auto-seed for grades and academic year
try {
    $pdo->exec("INSERT IGNORE INTO academic_years (id, year, term, is_current) VALUES (1, 2567, 1, 1)");
    $pdo->exec("INSERT IGNORE INTO grades (id, level_name) VALUES (1, 'ม.1'), (2, 'ม.2'), (3, 'ม.3'), (4, 'ม.4'), (5, 'ม.5'), (6, 'ม.6')");
} catch(Exception $e) {}

$msg = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM classrooms WHERE id = ?")->execute([$id]);
        $msg = "ลบข้อมูลห้องเรียนสำเร็จ!";
    } catch(Exception $e) {
        $msg = "ไม่อนุญาตให้ลบห้องเรียนที่มีตารางเรียนหรือนักเรียนสังกัดอยู่";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_classroom'])) {
    $id = $_POST['id'] ?? '';
    $grade_id = $_POST['grade_id'];
    $room_name = $_POST['room_name'];
    $advisor_id = $_POST['advisor_id'] ?: null; // Handle empty generic value

    if ($id) {
        $stmt = $pdo->prepare("UPDATE classrooms SET grade_id=?, room_name=?, advisor_id=? WHERE id=?");
        $stmt->execute([$grade_id, $room_name, $advisor_id, $id]);
        $msg = "แก้ไขข้อมูลห้องเรียนสำเร็จ!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO classrooms (grade_id, room_name, advisor_id, academic_year_id) VALUES (?, ?, ?, 1)");
        $stmt->execute([$grade_id, $room_name, $advisor_id]);
        $msg = "เพิ่มห้องเรียนสำเร็จ!";
    }
}

$classrooms = $pdo->query("
    SELECT c.*, g.level_name, t.first_name, t.last_name 
    FROM classrooms c 
    JOIN grades g ON c.grade_id = g.id 
    LEFT JOIN teachers t ON c.advisor_id = t.id
    ORDER BY g.id ASC, c.room_name ASC
")->fetchAll();

$grades = $pdo->query("SELECT * FROM grades ORDER BY id")->fetchAll();
$teachers = $pdo->query("SELECT id, first_name, last_name FROM teachers")->fetchAll();
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-door-open me-2"></i> จัดการระดับชั้นและห้องเรียน</h3>
        <button class="btn btn-primary" style="background-color: var(--primary-color); border:none;" data-bs-toggle="modal" data-bs-target="#classroomModal" onclick="clearForm()">
            <i class="fas fa-plus-circle me-1"></i> เพิ่มห้องเรียน
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
                <table class="table table-hover align-middle">
                    <thead class="card-custom-header">
                        <tr>
                            <th>ระดับชั้น</th>
                            <th>ชื่อห้องปฏิบัติการ / ห้องเรียน</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($classrooms as $c): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($c['level_name']) ?></span></td>
                            <td><strong><?= htmlspecialchars($c['room_name']) ?></strong></td>
                            <td>
                                <?php if($c['first_name']): ?>
                                    <i class="fas fa-chalkboard-teacher text-muted me-1"></i> <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">ยังไม่ระบุ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning mb-1" onclick='editClassroom(<?= json_encode($c) ?>)'><i class="fas fa-edit"></i> แก้ไข</button>
                                <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('ยืนยันลบห้องเรียนนี้?')"><i class="fas fa-trash"></i> ลบ</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($classrooms)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลห้องเรียนในระบบ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="classroomModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="classrooms.php" class="modal-content">
      <div class="modal-header card-custom-header">
        <h5 class="modal-title" id="modalTitle">เพิ่มห้องเรียนใหม่</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="c_id">
        
        <div class="mb-3">
            <label class="form-label fw-bold">ระดับชั้น</label>
            <select name="grade_id" id="c_grade" class="form-select" required>
                <option value="">-- เลือกระดับชั้น --</option>
                <?php foreach($grades as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['level_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ชื่อห้องเรียน (ตัวอย่าง: ม.1/1 หรือ ห้อง Lab คอมพิวเตอร์)</label>
            <input type="text" name="room_name" id="c_room" class="form-control" placeholder="ระบุชื่อห้อง" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">ครูที่ปรึกษา / ผู้ประจำห้อง (เว้นว่างได้)</label>
            <select name="advisor_id" id="c_advisor" class="form-select">
                <option value="">-- ไม่ระบุ --</option>
                <?php foreach($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" name="submit_classroom" class="btn btn-primary" style="background-color: var(--primary-color); border:none;"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
      </div>
    </form>
  </div>
</div>

<script>
function clearForm() {
    document.getElementById('modalTitle').innerText = 'เพิ่มห้องเรียนใหม่';
    document.getElementById('c_id').value = '';
    document.getElementById('c_grade').value = '';
    document.getElementById('c_room').value = '';
    document.getElementById('c_advisor').value = '';
}

function editClassroom(c) {
    document.getElementById('modalTitle').innerText = 'แก้ไขห้องเรียน';
    document.getElementById('c_id').value = c.id;
    document.getElementById('c_grade').value = c.grade_id;
    document.getElementById('c_room').value = c.room_name;
    document.getElementById('c_advisor').value = c.advisor_id || '';
    
    var modal = new bootstrap.Modal(document.getElementById('classroomModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
