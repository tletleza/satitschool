<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$role = $_SESSION['role'] ?? '';

// กำหนดเงื่อนไขการแสดงผลกลุ่มเมนู
$is_admin = ($role === 'admin');
$is_teacher = ($role === 'teacher');
$is_student = ($role === 'student');

$basic_pages = ['teachers.php', 'students.php', 'subjects.php', 'classrooms.php']; 
$is_basic = in_array($current_page, $basic_pages);
?>
        <!-- Sidebar โครงสร้างซ้ายมือ -->
        <nav class="sidebar">
            <div class="sidebar-header d-flex align-items-center justify-content-center py-4">
                <img src="favicon.png" alt="Logo" class="me-3 shadow-sm rounded bg-white p-1" style="width: 45px; height: 45px; object-fit: contain;">
                <h4 class="mb-0 fw-bold">โรงเรียนสาธิต</h4>
            </div>

            <ul class="list-unstyled components">
                <li class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
                    <a href="index.php"><i class="fas fa-chart-pie fa-fw me-2"></i> Dashboard</a>
                </li>

                <?php if ($is_admin): ?>
                <!-- เมนูสำหรับ Admin เท่านั้น -->
                <li>
                    <a href="#basicDataSubmenu" data-bs-toggle="collapse" aria-expanded="<?= $is_basic ? 'true' : 'false' ?>" class="dropdown-toggle <?= $is_basic ? '' : 'collapsed' ?>">
                        <i class="fas fa-database fa-fw me-2"></i> ข้อมูลพื้นฐาน
                    </a>
                    <ul class="collapse list-unstyled <?= $is_basic ? 'show' : '' ?>" id="basicDataSubmenu">
                        <li class="<?= ($current_page == 'teachers.php') ? 'active' : '' ?>"><a href="teachers.php"><i class="fas fa-chalkboard-teacher fa-fw me-2"></i> ข้อมูลครู</a></li>
                        <li class="<?= ($current_page == 'students.php') ? 'active' : '' ?>"><a href="students.php"><i class="fas fa-user-graduate fa-fw me-2"></i> ข้อมูลนักเรียน</a></li>
                        <li class="<?= ($current_page == 'subjects.php') ? 'active' : '' ?>"><a href="subjects.php"><i class="fas fa-book fa-fw me-2"></i> ข้อมูลวิชา</a></li>
                        <li class="<?= ($current_page == 'classrooms.php') ? 'active' : '' ?>"><a href="classrooms.php"><i class="fas fa-door-open fa-fw me-2"></i> ข้อมูลชั้นและห้อง</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($is_admin || $is_teacher): ?>
                <!-- เมนูสำหรับ ครู และ Admin -->
                <?php if ($is_admin): ?>
                <li class="<?= ($current_page == 'schedules.php') ? 'active' : '' ?>">
                    <a href="schedules.php"><i class="fas fa-calendar-alt fa-fw me-2"></i> จัดการตารางเรียน</a>
                </li>
                <?php endif; ?>
                
                <?php if ($is_teacher): ?>
                <li class="<?= ($current_page == 'my_teaching_schedule.php') ? 'active' : '' ?>">
                    <a href="my_teaching_schedule.php"><i class="fas fa-calendar-check fa-fw me-2"></i> ตารางสอนของฉัน</a>
                </li>
                <?php endif; ?>
                
                <li class="<?= ($current_page == 'grading.php') ? 'active' : '' ?>">
                    <a href="grading.php"><i class="fas fa-award fa-fw me-2"></i> บันทึกคะแนน</a>
                </li>
                <li class="<?= ($current_page == 'attendance.php') ? 'active' : '' ?>">
                    <a href="attendance.php"><i class="fas fa-user-check fa-fw me-2"></i> บันทึกเวลาเรียน</a>
                </li>
                <?php endif; ?>

                <?php if ($is_student): ?>
                <!-- เมนูสำหรับ นักเรียน -->
                <li class="<?= ($current_page == 'my_schedule.php') ? 'active' : '' ?>">
                    <a href="my_schedule.php"><i class="fas fa-calendar-day fa-fw me-2"></i> ดูตารางเรียน</a>
                </li>
                <li class="<?= ($current_page == 'my_attendance.php') ? 'active' : '' ?>">
                    <a href="my_attendance.php"><i class="fas fa-user-clock fa-fw me-2"></i> ประวัติการเข้าเรียน</a>
                </li>
                <li class="<?= ($current_page == 'my_grades.php') ? 'active' : '' ?>">
                    <a href="my_grades.php"><i class="fas fa-poll-h fa-fw me-2"></i> ผลการเรียนของฉัน</a>
                </li>
                <?php endif; ?>

                <?php if ($is_admin): ?>
                <li>
                    <a href="#settingsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle collapsed">
                        <i class="fas fa-cogs fa-fw me-2"></i> ตั้งค่าระบบ
                    </a>
                    <ul class="collapse list-unstyled" id="settingsSubmenu">
                        <li><a href="rbac.php" class="<?= ($current_page == 'rbac.php') ? 'active' : '' ?>"><i class="fas fa-users-cog fa-fw me-2"></i> จัดการสิทธิ์ (RBAC)</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- โครงสร้าง Content ขวามือ (เริ่มต้น) -->
        <div id="content">
            <!-- Navbar ด้านบน -->
            <nav class="navbar navbar-expand-lg navbar-custom">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex ms-auto align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle d-flex align-items-center bg-transparent border-0 shadow-none text-dark" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($_SESSION['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" class="rounded-circle me-2 border border-secondary" width="35" height="35" style="object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x me-2 text-primary"></i>
                                <?php endif; ?>
                                <span class="fw-bold" style="color: var(--primary-color);">
                                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> (<?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? '')); ?>)
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#userProfileModal"><i class="fas fa-user-cog me-2 text-primary"></i> บัญชีและการตั้งค่า</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="main-content">
