<?php
session_start();

// ล้าง session ทั้งหมด
session_unset();
session_destroy();

// แสดง SweetAlert2 และ redirect
echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>ออกจากระบบ</title>
    <!-- SweetAlert2 -->
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'ออกจากระบบสำเร็จ',
                text: 'คุณได้ออกจากระบบเรียบร้อยแล้ว',
                confirmButtonText: 'ตกลง',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                window.location.href = 'driver_signin.php';
            });
        });
    </script>
</body>
</html>";
exit;
?>