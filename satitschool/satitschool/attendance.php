<?php
/**
 * ไฟล์ attendance.php: ระบบเช็คชื่อเข้าชั้นเรียน (โดยครู)
 * ใช้สำหรับบันทึกการมาเรียน (มา, สาย, ลา, ขาด) ของนักเรียนในคาบเรียนที่สอน
 */
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

// อนุญาตเฉพาะ Admin และ Teacher (ครู) เท่านั้นที่เข้าถึงหน้านี้ได้
authorize(['admin', 'teacher']);

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// ดึงชื่อวันปัจจุบันเป็นภาษาอังกฤษ (Monday, Tuesday, ...) เพื่อกรอง Schedules
$today_day = date('l'); 

// ดึงรายชื่อตารางสอน (RBAC: ครูเห็นเฉพาะของตัวเองในวันปัจจุบัน)
$query = "SELECT sc.id as schedule_id, s.name as subject_name, s.subject_code, c.room_name, g.level_name, sc.start_time, sc.end_time
          FROM schedules sc
          JOIN subjects s ON sc.subject_id = s.id
          JOIN classrooms c ON sc.classroom_id = c.id
          JOIN grades g ON c.grade_id = g.id
          WHERE sc.day_of_week = ?";

$params = [$today_day];

if ($role === 'teacher') {
    $t_stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $t_stmt->execute([$user_id]);
    $teacher_id = $t_stmt->fetchColumn();
    $query .= " AND sc.teacher_id = " . (int)$teacher_id;
}
$query .= " ORDER BY sc.start_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// จัดกลุ่มข้อมูลตามระดับชั้นเพื่อความง่ายในการเรียกดู
$grouped_schedules = [];
foreach ($schedules as $s) {
    $level = $s['level_name'];
    if (!isset($grouped_schedules[$level])) {
        $grouped_schedules[$level] = [];
    }
    $grouped_schedules[$level][] = $s;
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- DataTables CSS สำหรับการค้นหาชื่อนักเรียนในห้องใหญ่ -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-user-check me-2"></i> ระบบบันทึกเวลาเรียน (Attendance)</h3>
        <div class="text-end">
            <span class="badge bg-primary fs-6 shadow-sm"><i class="fas fa-calendar-alt me-1"></i> วัน<?= date('l') ?>ที่ <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- ส่วนเลือกตารางสอน -->
    <div class="card card-custom border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-10">
                    <label class="form-label fw-bold">เลือกตารางสอนของคุณในวันนี้</label>
                    <select id="select_schedule" class="form-select form-select-lg border-2">
                        <option value="">-- กรุณาเลือกคาบเรียน --</option>
                        <?php foreach($grouped_schedules as $level => $schs): ?>
                            <optgroup label="<?= htmlspecialchars($level) ?>">
                                <?php foreach($schs as $s): ?>
                                <option value="<?= $s['schedule_id'] ?>">
                                    <?= htmlspecialchars("เวลา ".substr($s['start_time'],0,5)."-".substr($s['end_time'],0,5)." | ห้อง ".$s['room_name']." | ".$s['subject_code']." ".$s['subject_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                        <?php if(empty($schedules)): ?>
                            <option value="" disabled>ไม่มีตารางสอนในวันนี้</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="btn_load_attendance" class="btn btn-primary btn-lg w-100 shadow-sm" style="background-color: var(--primary-color); border:none;">
                        <i class="fas fa-users-viewfinder me-1"></i> ดึงรายชื่อ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางบันทึกเวลาเรียน -->
    <div id="attendance_area" style="display:none;">
        <div class="card card-custom border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark"><i class="fas fa-list-ol me-2 text-primary"></i> รายชื่อนักเรียน</h5>
                    <button id="btn_mark_all_present" class="btn btn-outline-success fw-bold shadow-sm">
                        <i class="fas fa-check-double me-1"></i> เช็คชื่อว่ามาเรียนทุกคน (Mark All)
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="attendanceTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-center">
                            <tr>
                                <th width="60">เลขที่</th>
                                <th width="120">รหัส</th>
                                <th class="text-start">ชื่อ-นามสกุล</th>
                                <th width="400">สถานะการเข้าเรียนวันนี้</th>
                            </tr>
                        </thead>
                        <tbody id="student_attendance_list">
                            <!-- AJAX content here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast สำหรับแจ้งเตือน Real-time -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="saveToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i> บันทึกข้อมูลเรียบร้อยแล้ว
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    const toast = new bootstrap.Toast(document.getElementById('saveToast'));
    let attTable = null;

    // โหลดรายชื่อนักเรียน
    $('#btn_load_attendance').click(function(){
        let sch_id = $('#select_schedule').val();
        if(!sch_id) return alert('กรุณาเลือกคาบเรียนก่อนครับ');

        $.ajax({
            url: 'api/get_attendance_list.php',
            type: 'GET',
            data: { schedule_id: sch_id },
            success: function(response){
                let html = '';
                response.forEach(function(st){
                    const statuses = [
                        {val: 'present', label: 'มา', color: 'success', icon: 'check'},
                        {val: 'absent',  label: 'ขาด', color: 'danger',  icon: 'times'},
                        {val: 'late',    label: 'สาย', color: 'warning', icon: 'clock'},
                        {val: 'leave',   label: 'ลา',  color: 'info',    icon: 'envelope'}
                    ];
                    
                    let bg = '<div class="btn-group w-100 shadow-sm" role="group">';
                    statuses.forEach(s => {
                        let checked = (st.status === s.val) ? 'checked' : '';
                        bg += `
                            <input type="radio" class="btn-check btn-attendance" 
                                   name="st_${st.student_id}" id="st_${st.student_id}_${s.val}" 
                                   value="${s.val}" data-student="${st.student_id}" ${checked}>
                            <label class="btn btn-outline-${s.color} py-2" for="st_${st.student_id}_${s.val}">
                                <i class="fas fa-${s.icon} me-1 small"></i>${s.label}
                            </label>
                        `;
                    });
                    bg += '</div>';

                    html += `
                        <tr>
                            <td class="text-center fw-bold text-muted">${st.roll_number || '-'}</td>
                            <td class="text-center">${st.student_code}</td>
                            <td><strong class="text-dark">${st.first_name} ${st.last_name}</strong></td>
                            <td>${bg}</td>
                        </tr>
                    `;
                });
                
                if(attTable) attTable.destroy();
                $('#student_attendance_list').html(html);
                $('#attendance_area').fadeIn();
                
                // Initialize DataTable
                attTable = $('#attendanceTable').DataTable({
                    "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json" },
                    "pageLength": 50,
                    "columnDefs": [{ "orderable": false, "targets": 3 }]
                });
            }
        });
    });

    // ปุ่ม Mark All as Present
    $('#btn_mark_all_present').click(function(){
        const sch_id = $('#select_schedule').val();
        if(!confirm('คุณต้องการเช็คชื่อว่านักเรียนทุกคนในห้องนี้ "มาเรียน" ใช่หรือไม่?')) return;

        // ดึงนักเรียนทั้งหมดในหน้านี้ (ใช้ jQuery ค้นหา input radio ที่มีค่า present)
        $('.btn-attendance[value="present"]').each(function(){
            if(!$(this).is(':checked')) {
                $(this).prop('checked', true).trigger('change');
            }
        });
        alert('ระบบกำลังประมวลผลการเช็คชื่อ "มาเรียน" ให้ทุกคนแบบ Real-time...');
    });

    // บันทึกสถานะรายบุคคล (AJAX Toggle)
    $(document).on('change', '.btn-attendance', function(){
        const st_id = $(this).data('student');
        const stat = $(this).val();
        const sch_id = $('#select_schedule').val();

        $.ajax({
            url: 'api/save_attendance.php',
            type: 'POST',
            data: { student_id: st_id, status: stat, schedule_id: sch_id },
            success: function(res){
                if(res.status === 'success') {
                    toast.show();
                } else {
                    alert('Error: ' + res.message);
                }
            }
        });
    });
});
</script>

<style>
/* ตกแต่ง UI ให้ดูพรีเมียมขึ้น */
#attendanceTable thead th { border: none; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
.btn-attendance + label { font-size: 0.85rem; border-width: 2px; }
.btn-check:checked + label { color: #fff !important; }
.btn-outline-success:hover, .btn-check:checked + .btn-outline-success { background-color: #6D9E51; border-color: #6D9E51; }
.btn-outline-danger:hover, .btn-check:checked + .btn-outline-danger { background-color: #A82323; border-color: #A82323; }
.btn-outline-warning:hover, .btn-check:checked + .btn-outline-warning { background-color: #FB9B8F; border-color: #FB9B8F; color: #fff; }
.btn-outline-info:hover, .btn-check:checked + .btn-outline-info { background-color: #5bc0de; border-color: #5bc0de; }
</style>

<?php require_once 'includes/footer.php'; ?>
