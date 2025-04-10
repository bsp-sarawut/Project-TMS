<?php
    // session_start(); // เริ่มต้นเซสชัน
    require_once 'condb.php';
    include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนขึ้นรถรับส่ง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            font-family: 'Chakra Petch', sans-serif;
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px; /* ป้องกันเนื้อหาทับกับ Navbar */
            color: #eceff1;
        }
        .container {
            padding-top: 20px;
            padding-bottom: 50px;
            max-width: 800px;
        }
        .card {
            border: none;
            border-radius: 15px;
            background: #263238;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3), 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            color: #1c2526;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: none;
            padding: 20px;
            position: relative;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            z-index: 0;
        }
        .card-header h3 {
            margin: 0;
            position: relative;
            z-index: 1;
        }
        .card-body {
            padding: 30px;
        }
        .form-label {
            color: #eceff1;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .form-control, .form-select {
            background: #37474f;
            border: 1px solid #ffca28;
            color: #eceff1;
            border-radius: 8px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ff8f00;
            box-shadow: 0 0 8px rgba(255, 202, 40, 0.3);
            background: #455a64;
            color: #eceff1;
        }
        .form-control:read-only {
            background: #455a64;
            color: #b0bec5;
            border: 1px solid #ffca28;
        }
        .form-control::placeholder, .form-select::placeholder {
            color: #b0bec5;
        }
        .btn-primary {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            border: none;
            border-radius: 25px;
            padding: 12px 0;
            font-weight: 600;
            color: #1c2526;
            transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8f00, #ffca28);
            transform: scale(1.03);
            box-shadow: 0 4px 15px rgba(255, 202, 40, 0.4);
        }
        .btn-success {
            background: linear-gradient(45deg, #4caf50, #66bb6a);
            border: none;
            border-radius: 25px;
            padding: 12px 0;
            font-weight: 600;
            color: #1c2526;
            transition: background 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-success:hover {
            background: linear-gradient(45deg, #66bb6a, #4caf50);
            transform: scale(1.03);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }
        .receipt-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #2c3e50;
            border: 1px solid #ffca28;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            color: #eceff1;
        }
        #close-btn {
            position: absolute;
            top: -5px;
            right: 10px;
            font-size: 1.5rem;
            color: #eceff1;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        #close-btn:hover {
            color:rgb(253, 47, 47);
        }
        .receipt-section h4 {
            color: #ffca28;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .receipt-section p {
            margin-bottom: 10px;
            font-size: 1rem;
        }
        .receipt-section strong {
            color: #ffca28;
        }
        .modal-content {
            border-radius: 15px;
            background: #263238;
            color: #eceff1;
        }
        .modal-header {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            color: #1c2526;
            border-bottom: none;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-body img {
            border-radius: 10px;
            border: 2px solid #ffca28;
        }
        @media (max-width: 768px) {
            .container {
                padding-top: 10px;
                padding-bottom: 40px;
            }
            .card-body {
                padding: 20px;
            }
            .form-label {
                font-size: 0.85rem;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
            }
            .btn-primary, .btn-success {
                padding: 10px 0;
                font-size: 0.9rem;
            }
            .receipt-section {
                padding: 15px;
            }
            .receipt-section h4 {
                font-size: 1.2rem;
            }
            .receipt-section p {
                font-size: 0.9rem;
            }
        }
        @media (max-width: 576px) {
            .card-header h3 {
                font-size: 1.3rem;
            }
            .form-label {
                font-size: 0.8rem;
            }
            .form-control, .form-select {
                font-size: 0.85rem;
            }
            .btn-primary, .btn-success {
                padding: 8px 0;
                font-size: 0.85rem;
            }
            .receipt-section h4 {
                font-size: 1.1rem;
            }
            .receipt-section p {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow-lg">
        <div class="card-header">
            <h3 class="text-center">ลงทะเบียนขึ้นรถรับส่ง</h3>
        </div>
        <div class="card-body">
            <form id="registration-form" action="enrollment_db.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" class="form-control" name="stu_username" value="<?php echo $_SESSION['user_name']; ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">เลือกจังหวัด</label>
                    <select class="form-select" id="province" name="province" required>
                        <option value="" disabled selected>เลือกจังหวัด</option>
                        <?php while ($row = $result_province->fetch(PDO::FETCH_ASSOC)) {?>
                            <option value="<?php echo $row['PROVINCE_ID']; ?>"><?php echo $row['PROVINCE_NAME']; ?></option>
                        <?php }?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">เลือกอำเภอ</label>
                    <select class="form-select" id="amphur" name="amphur" required>
                        <option value="" disabled selected>เลือกอำเภอ</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">เลือกสถานที่ขึ้นรถ</label>
                    <select class="form-select" id="location" name="location" required>
                        <option value="" disabled selected>เลือกสถานที่ขึ้นรถ</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">ภาพเส้นทาง</label><br>
                    <img id="route-image" src="" alt="ภาพเส้นทาง" style="max-width: 50%; cursor: pointer; display: none;">
                </div>
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" style="display: none;" inert>
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel">ดูภาพเส้นทาง</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <img id="modal-image" src="" class="img-fluid" alt="ภาพเส้นทาง">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">เลือกตารางรถ</label>
                    <select name="schedule" class="form-select" id="schedule" required>
                        <?php while ($schedule = $result_schedule->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $schedule['id']; ?>" data-num-of-days="<?php echo $schedule['num_of_days']; ?>">
                                <?php echo 'ปี : ' . $schedule['year'] . ' / เดือน : ' . $schedule['month'] . ' / วันที่ ' . $schedule['available_dates']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">จำนวนวัน</label>
                    <input type="number" class="form-control" id="num_of_days" name="num_of_days" min="1" required readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">ราคา</label>
                    <input type="text" class="form-control" id="price" name="price" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">ราคารวม</label>
                    <input type="text" class="form-control" id="total_price" name="total_price" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">ภาพใบเสร็จการชำระเงิน</label>
                    <input type="file" class="form-control" name="payment_receipt" required>
                </div>
                <button type="button" class="btn btn-primary w-100" id="preview-btn">ดูตัวอย่างข้อมูล</button>
                <button type="submit" class="btn btn-success w-100 mt-3" id="submit-btn" style="display:none;">ลงทะเบียน</button>
            </form>
            <div id="receipt-section" class="receipt-section">
                <i id="close-btn">x</i>
                <h4>รายละเอียดการลงทะเบียนรถรับส่ง</h4>
                <p><strong>ชื่อผู้ใช้:</strong> <span id="receipt-username"></span></p>
                <p><strong>จังหวัด:</strong> <span id="receipt-province"></span></p>
                <p><strong>อำเภอ:</strong> <span id="receipt-amphur"></span></p>
                <p><strong>สถานที่ขึ้นรถ:</strong> <span id="receipt-location"></span></p>
                <p><strong>ตารางรถ:</strong> <span id="receipt-schedule"></span></p>
                <p><strong>จำนวนวัน:</strong> <span id="receipt-num-of-days"></span></p>
                <p><strong>ราคา:</strong> <span id="receipt-price"></span></p>
                <p><strong>ราคารวม:</strong> <span id="receipt-total-price"></span></p>
                <p><strong>ใบเสร็จการชำระเงิน:</strong> <span id="receipt-receipt"></span></p>
                <button type="button" class="btn btn-success w-100" id="confirm-btn">ยืนยันและลงทะเบียน</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#province').change(function() {
        var provinceId = $(this).val();
        $.post('get_amphur.php', { province_id: provinceId }, function(response) {
            $('#amphur').html(response).trigger('change');
        });
    });

    $('#amphur').change(function() {
        var amphurId = $(this).val();
        var provinceId = $('#province').val();
        $.post('get_location.php', { province_id: provinceId, amphur_id: amphurId }, function(response) {
            var locations = response.split('|');
            var locationOptions = locations[0];
            var routeImage = locations[1];

            $('#location').html(locationOptions).trigger('change');

            if (routeImage) {
                $('#route-image').attr('src', routeImage).show();
            } else {
                $('#route-image').hide();
            }
        });
    });

    $('#location').change(function() {
        var locationId = $(this).val();
        var selectedOption = $(this).find('option:selected');
        var routeImage = selectedOption.data('image');

        if (routeImage) {
            $('#route-image').attr('src', routeImage).show();
        } else {
            $('#route-image').hide();
        }

        $.post('get_price.php', { location_id: locationId }, function(response) {
            $('#price').val(response);
            calculateTotalPrice();
        });
    });

    $('#schedule').change(function() {
        var numOfDays = $(this).find('option:selected').data('num-of-days') || 0;
        $('#num_of_days').val(numOfDays);
        calculateTotalPrice();
    });

    function calculateTotalPrice() {
        var price = parseFloat($('#price').val()) || 0;
        var numOfDays = parseInt($('#num_of_days').val()) || 0;
        $('#total_price').val((price * numOfDays).toFixed(2));
    }

    $('#preview-btn').click(function() {
        $('#receipt-username').text($('input[name="stu_username"]').val());
        $('#receipt-province').text($('#province option:selected').text());
        $('#receipt-amphur').text($('#amphur option:selected').text());
        $('#receipt-location').text($('#location option:selected').text());
        $('#receipt-schedule').text($('#schedule option:selected').text());
        $('#receipt-num-of-days').text($('#num_of_days').val());
        $('#receipt-price').text($('#price').val());
        $('#receipt-total-price').text($('#total_price').val());
        $('#receipt-receipt').text($('input[name="payment_receipt"]').val().split('\\').pop());

        $('#registration-form').fadeOut(500, function() {
            $('#receipt-section').fadeIn(500);
        });

        $('#submit-btn').fadeIn(500);
    });

    $('#confirm-btn').click(function() {
        $('#registration-form').submit();
    });

    $('#route-image').click(function() {
        var imageSrc = $(this).attr('src');
        $('#modal-image').attr('src', imageSrc);
        $('#imageModal').modal('show');
    });

    $('#imageModal').on('show.bs.modal', function() {
        $(this).removeAttr('inert');
    });

    $('#imageModal').on('hide.bs.modal', function() {
        $(this).attr('inert', '');
    });
});

$(document).on('click', '#close-btn', function() {
    $('#receipt-section').fadeOut(500);
    $('#registration-form').fadeIn(500);
    $('#submit-btn').fadeOut(500);
});
</script>
</body>
</html>