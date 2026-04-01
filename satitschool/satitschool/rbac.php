<?php
/**
 * ไฟล์ rbac.php: ระบบจัดการสิทธิ์การเข้าถึง (Role-Based Access Control)
 * สำหรับ Super Admin เท่านั้น ใช้ในการกำหนดบทบาทของแต่ละบัญชีผู้ใช้ในระบบ
 */
require_once 'includes/check_auth.php';
require_once 'includes/check_role.php';
require_once 'includes/db.php';

// เฉพาะ Admin เท่านั้นที่เข้าถึงหน้านี้ได้ (Super Admin Check)
authorize(['admin']);

$role = $_SESSION['role'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 style="color: var(--primary-color);"><i class="fas fa-users-cog me-2"></i> จัดการสิทธิ์การใช้งาน (RBAC Management)</h3>
        <p class="text-muted mb-0">จัดการบทบาทและสถานะการเข้าใช้งานของบุคลากรและนักเรียน</p>
    </div>

    <div class="card card-custom border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="rbacTable" class="table table-hover align-middle w-100">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="50">ID</th>
                            <th>ชื่อผู้ใช้ (Username)</th>
                            <th>รหัสผ่าน (Password)</th>
                            <th>บทบาทปัจจุบัน (Role)</th>
                            <th>สถานะ (Status)</th>
                            <th>เข้าใช้งานล่าสุด</th>
                            <th width="150">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- ดึงข้อมูลด้วย AJAX DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับแก้ไขบทบาทและสถานะ -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i> แก้ไขสิทธิ์ผู้ใช้งาน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRoleForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อผู้ใช้:</label>
                        <input type="text" id="edit_username" class="form-control-plaintext ps-2 border rounded bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger"><i class="fas fa-lock me-1"></i> รีเซ็ตรหัสผ่าน (New Password):</label>
                        <input type="text" name="new_password" id="edit_new_password" class="form-control border-2 border-danger" placeholder="ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน">
                        <div class="form-text text-danger">หากต้องการตั้งรหัสผ่านใหม่ให้พิมพ์ที่นี่ จากนั้นกดบันทึก</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">กำหนดบทบาท (Role):</label>
                        <select name="role_id" id="edit_role_id" class="form-select border-2">
                            <option value="1">Administrator</option>
                            <option value="2">Teacher</option>
                            <option value="3">Student</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">สถานะการเข้าใช้งาน (Status):</label>
                        <select name="status" id="edit_status" class="form-select border-2">
                            <option value="active">Active (ปกติ)</option>
                            <option value="inactive">Inactive (ปิดการใช้งาน)</option>
                            <option value="suspended">Suspended (ระงับการเข้าถึง)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DataTables & Scripts -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Initialize DataTable
    const table = $('#rbacTable').DataTable({
        "processing": true,
        "serverSide": false, // ใช้ Client-side เพราะข้อมูล 250 คนยังพอไหว
        "ajax": {
            "url": "api/get_users_list.php",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id", "className": "text-center" },
            { "data": "username", "className": "fw-bold text-primary" },
            { 
                "data": null,
                "render": function() { return '<span class="text-muted"><i class="fas fa-key small me-1"></i> ****** (ถูกเข้ารหัส)</span>'; },
                "className": "text-center" 
            },
            { 
                "data": "role_name",
                "render": function(data) {
                    let badge = 'bg-secondary';
                    if(data === 'admin') badge = 'bg-dark';
                    if(data === 'teacher') badge = 'bg-primary';
                    if(data === 'student') badge = 'bg-success';
                    return `<span class="badge ${badge} text-uppercase px-3">${data}</span>`;
                },
                "className": "text-center"
            },
            { 
                "data": "status",
                "render": function(data) {
                    let color = (data === 'active') ? 'success' : 'danger';
                    return `<span class="text-${color} fw-bold"><i class="fas fa-circle small me-1"></i> ${data}</span>`;
                },
                "className": "text-center"
            },
            { "data": "last_login", "render": d => d || '-', "className": "text-center" },
            {
                "data": null,
                "render": function(data) {
                    return `
                        <button class="btn btn-sm btn-outline-primary btn-edit" 
                                data-id="${data.id}" 
                                data-username="${data.username}" 
                                data-role="${data.role_id}" 
                                data-status="${data.status}">
                            <i class="fas fa-edit me-1"></i> แก้ไขสิทธิ์
                        </button>
                    `;
                },
                "className": "text-center"
            }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json" }
    });

    // 2. Open Modal for Editing
    $(document).on('click', '.btn-edit', function() {
        $('#edit_user_id').val($(this).data('id'));
        $('#edit_username').val($(this).data('username'));
        $('#edit_role_id').val($(this).data('role'));
        $('#edit_status').val($(this).data('status'));
        $('#edit_new_password').val(''); // Clear password field
        $('#editRoleModal').modal('show');
    });

    // 3. Save Changes via AJAX
    $('#editRoleForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/update_user_role.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.success) {
                    alert('อัปเดตสิทธิ์ผู้ใช้งานสำเร็จ!');
                    $('#editRoleModal').modal('hide');
                    table.ajax.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + res.message);
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
