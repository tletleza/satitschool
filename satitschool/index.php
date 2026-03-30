<?php 
require_once 'includes/check_auth.php'; 
require_once 'includes/check_role.php';
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- Content Wrapper -->
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: var(--primary-color);"><i class="fas fa-th-large me-2"></i> ระบบโรงเรียนสาธิต : Dashboard</h2>
        <div class="badge bg-white text-dark shadow-sm p-3 border">
            <i class="fas fa-sync fa-spin me-2 text-primary"></i> 
            Auto-Sync (3s) | Last Update: <span id="last_sync">-</span>
        </div>
    </div>

    <?php if($_SESSION['role'] !== 'student'): ?>
    <div class="row g-4 mb-4">
        <!-- สถิติแบบตัวเลข (Summary) -->
        <div class="col-md-6 col-xl-4">
            <div class="card card-custom border-0 shadow-sm" style="background: linear-gradient(45deg, #A82323, #FB9B8F); color:#fff;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-4 ps-2">
                        <i class="fas fa-user-check fa-3x" style="opacity: 0.8;"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-white-50 text-uppercase fw-bold">มาเรียนวันนี้</h6>
                        <h2 id="total_present" class="mb-0 fw-bold">0</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-4">
            <div class="card card-custom border-0 shadow-sm" style="background: linear-gradient(45deg, #11998E, #38EF7D); color:#fff;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-4 ps-2">
                        <i class="fas fa-users fa-3x" style="opacity: 0.8;"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-white-50 text-uppercase fw-bold">นักเรียนทั้งหมด</h6>
                        <h2 id="total_students_card" class="mb-0 fw-bold">0</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-xl-4">
            <div class="card card-custom border-0 shadow-sm" style="background: linear-gradient(45deg, #2980B9, #6DD5FA); color:#fff;">
                <div class="card-body d-flex align-items-center">
                    <div class="me-4 ps-2">
                        <i class="fas fa-chalkboard-teacher fa-3x" style="opacity: 0.8;"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-white-50 text-uppercase fw-bold">ครูทั้งหมด</h6>
                        <h2 id="total_teachers_card" class="mb-0 fw-bold">0</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ข่าวสารโรงเรียน (Announcements) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom shadow-sm border-0" style="border-left: 5px solid #ffc107 !important;">
                <div class="card-header bg-white text-dark fw-bold py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn text-warning me-2"></i> ข่าวสารและประกาศ โรงเรียนสาธิตวิทยา</h5>
                    <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
                        <button class="btn btn-sm btn-warning fw-bold shadow-sm" id="btnAddNews">
                            <i class="fas fa-plus-circle me-1"></i> จัดการข่าวสาร
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body" id="newsContainer">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br>
                        กำลังโหลดข่าวสาร...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if($_SESSION['role'] !== 'student'): ?>
    <div class="row g-4 mb-4">
        <!-- กราฟวงกลม (Attendance) -->
        <div class="col-lg-5">
            <div class="card card-custom shadow-sm border-0 h-100">
                <div class="card-header card-custom-header">
                    <i class="fas fa-chart-pie me-2"></i> สถิติการมาเรียนของวันปัจจุบัน
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div style="height: 300px; width: 100%;">
                        <canvas id="attendancePie"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟแท่ง (Grades) -->
        <div class="col-lg-7">
            <div class="card card-custom shadow-sm border-0 h-100">
                <div class="card-header card-custom-header">
                    <i class="fas fa-chart-bar me-2"></i> สถิติการกระจายของเกรด (Bar Chart)
                </div>
                <div class="card-body">
                    <div style="height: 300px; width: 100%;">
                        <canvas id="gradesBar"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    <?php if($_SESSION['role'] !== 'student'): ?>
    // 1. Initial Attendance Chart
    const attCtx = document.getElementById('attendancePie').getContext('2d');
    const attendanceChart = new Chart(attCtx, {
        type: 'doughnut',
        data: {
            labels: ['มา', 'ขาด', 'สาย', 'ลา'],
            datasets: [{
                data: [0,0,0,0],
                backgroundColor: ['#6D9E51', '#A82323', '#FB9B8F', '#FEFFD3'],
                borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 2. Initial Grades Chart
    const grdCtx = document.getElementById('gradesBar').getContext('2d');
    const gradesChart = new Chart(grdCtx, {
        type: 'bar',
        data: {
            labels: ['4','3.5','3','2.5','2','1.5','1','0'],
            datasets: [{
                label: 'จำนวนนักเรียน (คน)',
                data: [0,0,0,0,0,0,0,0],
                backgroundColor: '#A82323',
                borderRadius: 5
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 3. AJAX Data Fetch Function
    function fetchDashboardData() {
        $.ajax({
            url: 'api/get_dashboard.php',
            method: 'GET',
            success: function(res) {
                if(res.success) {
                    // Update Attendance
                    attendanceChart.data.datasets[0].data = [
                        res.attendance.present, 
                        res.attendance.absent, 
                        res.attendance.late, 
                        res.attendance.leave
                    ];
                    attendanceChart.update();

                    // Update Grades
                    const g = res.grades;
                    gradesChart.data.datasets[0].data = [
                        g['4'], g['3.5'], g['3'], g['2.5'], g['2'], g['1.5'], g['1'], g['0']
                    ];
                    gradesChart.update();

                    // Update UI Labels
                    $('#total_present').text(res.attendance.present);
                    $('#total_students_card').text(res.totals.students);
                    $('#total_teachers_card').text(res.totals.teachers);
                    $('#last_sync').text(res.timestamp);
                }
            }
        });
    }

    // Polling every 3 seconds (ตามโจทย์)
    fetchDashboardData();
    setInterval(fetchDashboardData, 3000);
    <?php endif; ?>

    // --- News System ---
    function loadNews() {
        $.ajax({
            url: 'api/get_news.php',
            type: 'GET',
            success: function(res) {
                if(res.success) {
                    let html = '';
                    const role = '<?= $_SESSION['role'] ?>';
                    
                    if(res.data.length === 0) {
                        html = '<div class="text-center py-4 text-muted">ยังไม่มีข่าวสารในขณะนี้</div>';
                    } else {
                        res.data.forEach(function(item) {
                            let imgHtml = '';
                            if(item.image_url) {
                                imgHtml = `<div class="mt-3"><img src="${item.image_url}" class="img-fluid rounded shadow-sm" style="max-height: 350px; width: auto; object-fit: cover;"></div>`;
                            }
                            
                            let controls = '';
                            if(role === 'admin' || role === 'teacher') {
                                controls = `
                                    <div class="mt-3 text-end">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-news" data-id="${item.id}" data-title="${item.title}" data-content="${item.content}" data-image="${item.image_url || ''}"><i class="fas fa-edit me-1"></i> แก้ไข</button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-news" data-id="${item.id}"><i class="fas fa-trash-alt me-1"></i> ลบ</button>
                                    </div>
                                `;
                            }

                            // Format Date
                            let d = new Date(item.created_at);
                            let day = String(d.getDate()).padStart(2, '0');
                            let monthList = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                            let month = monthList[d.getMonth()];
                            
                            html += `
                            <div class="d-flex flex-column flex-md-row align-items-start border-bottom pb-4 mb-4">
                                <div class="bg-light text-primary rounded shadow-sm p-3 me-md-4 mb-3 mb-md-0 text-center" style="min-width: 90px;">
                                    <h3 class="mb-0 fw-bold">${day}</h3>
                                    <span class="text-uppercase fw-bold">${month}</span>
                                </div>
                                <div class="w-100">
                                    <h4 class="fw-bold text-dark mb-2">${item.title}</h4>
                                    <p class="text-muted small mb-3"><i class="fas fa-user-edit me-1"></i> โพสต์โดย: ${item.creator_name}</p>
                                    <p class="text-dark mb-0 fs-6" style="white-space: pre-wrap; line-height: 1.6;">${item.content}</p>
                                    ${imgHtml}
                                    ${controls}
                                </div>
                            </div>`;
                        });
                    }
                    $('#newsContainer').html(html);
                }
            }
        });
    }

    loadNews();

    $('#btnAddNews').click(function() {
        $('#newsForm')[0].reset();
        $('#news_id').val('');
        $('#current_image_info').html('');
        $('#newsModal').modal('show');
    });

    $(document).on('click', '.btn-edit-news', function() {
        $('#news_id').val($(this).data('id'));
        $('#news_title').val($(this).data('title'));
        $('#news_content').val($(this).data('content'));
        
        let img = $(this).data('image');
        if(img) {
            $('#current_image_info').html(`<i class="fas fa-image me-1"></i> รูปปัจจุบัน: <a href="${img}" target="_blank">คลิกเพื่อดูรูป</a> (ต้องการเปลี่ยนให้อัปโหลดใหม่)`);
        } else {
            $('#current_image_info').html('');
        }
        
        $('#newsModal').modal('show');
    });

    $('#newsForm').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        $.ajax({
            url: 'api/save_news.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if(res.success) {
                    $('#newsModal').modal('hide');
                    loadNews();
                } else {
                    alert('Error: ' + res.message);
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-news', function() {
        if(confirm('คุณแน่ใจหรือไม่ว่าต้องการลบประกาศนี้? สำคัญ: รูปภาพที่เกี่ยวข้องจะถูกลบไปด้วย!')) {
            let id = $(this).data('id');
            $.ajax({
                url: 'api/delete_news.php',
                type: 'POST',
                data: JSON.stringify({id: id}),
                contentType: 'application/json',
                success: function(res) {
                    if(res.success) {
                        loadNews();
                    } else {
                        alert('Error: ' + res.message);
                    }
                }
            });
        }
    });

});
</script>

<!-- News Modal -->
<?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher'): ?>
<div class="modal fade" id="newsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> จัดการข่าวสารประกาศ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newsForm">
                <div class="modal-body">
                    <input type="hidden" id="news_id" name="news_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">หัวข้อข่าว</label>
                        <input type="text" class="form-control" name="title" id="news_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">รายละเอียด</label>
                        <textarea class="form-control" name="content" id="news_content" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">รูปภาพประกอบ (ถ้ามี)</label>
                        <input type="file" class="form-control border-secondary" name="image" id="news_image" accept="image/*">
                        <div class="form-text mt-2 text-primary fw-bold" id="current_image_info"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> บันทึกประกาศ</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
