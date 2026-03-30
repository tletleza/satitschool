            </div> 
            <!-- จบ พื้นที่เนื้อหาในแต่ละหน้า -->
            
            <!-- Footer -->
            <footer class="footer mt-4">
                <div class="container-fluid">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> โรงเรียนสาธิตวิทยา. สงวนลิขสิทธิ์.</p>
                </div>
            </footer>
        </div> <!-- จบ โครงสร้าง Content ขวามือ -->
    </div> <!-- จบ wrapper โครงร่างหลัก -->

    <!-- Global User Profile Modal -->
    <div class="modal fade" id="userProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-cog me-2"></i> การตั้งค่าบัญชีผู้ใช้</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="globalProfileForm" enctype="multipart/form-data">
                    <div class="modal-body bg-light">
                        <div class="text-center mb-4">
                            <?php if (!empty($_SESSION['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" class="rounded-circle shadow-sm border border-3 border-white mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle shadow-sm bg-white border border-3 border-primary d-inline-flex justify-content-center align-items-center mb-2" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user fa-3x text-primary"></i>
                                </div>
                            <?php endif; ?>
                            <h5 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h5>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? '')); ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">อัปเดตรูปประจำตัว</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/jpeg, image/png, image/webp">
                            <small class="text-muted">ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรูป</small>
                        </div>
                        
                        <hr>
                        <h6 class="fw-bold text-danger mb-3"><i class="fas fa-lock me-2"></i> เปลี่ยนรหัสผ่าน</h6>
                        
                        <?php if($_SESSION['role'] !== 'admin'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control border-secondary" name="current_password" placeholder="เพื่อยืนยันตัวตน ก่อนเปลี่ยนรหัส">
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" name="new_password" placeholder="สร้างรหัสผ่านใหม่">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
                        </div>
                        <div id="profileErrorBox" class="alert alert-danger d-none py-2 mb-0 mt-3" style="font-size: 0.9rem;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom script สำหรับ Toggle Sidebar -->
    <script src="js/script.js"></script>
    <script>
    $(document).ready(function() {
        $('#globalProfileForm').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $('#profileErrorBox').addClass('d-none');
            
            $.ajax({
                url: 'api/update_profile.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    if(res.success) {
                        alert('อัปเดตข้อมูลบัญชีสำเร็จ!');
                        location.reload();
                    } else {
                        $('#profileErrorBox').removeClass('d-none').html('<i class="fas fa-exclamation-triangle me-1"></i> ' + res.message);
                    }
                },
                error: function() {
                    $('#profileErrorBox').removeClass('d-none').html('<i class="fas fa-exclamation-triangle me-1"></i> เกิดข้อผิดพลาดในการเชื่อมต่อ 서버');
                }
            });
        });
    });
    </script>
</body>
</html>
