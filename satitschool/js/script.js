$(document).ready(function () {
    // ฟังก์ชันเปิด/ปิด Sidebar Menu
    $('#sidebarCollapse').on('click', function () {
        $('.sidebar').toggleClass('active');
    });

    // ให้ Sidebar ปิดอัตโนมัติเมื่อคลิกพื้นที่อื่น (เฉพาะในหน้าจอ Mobile)
    $(document).on('click', function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('.sidebar, #sidebarCollapse').length) {
                $('.sidebar').removeClass('active');
            }
        }
    });
});
