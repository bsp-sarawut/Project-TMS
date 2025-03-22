<?php
    // session_start(); // เริ่มต้นเซสชัน
    require_once 'condb.php';
    include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" >
    <title>Transport Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

            .form-control:read-only {
                background-color: rgba(143, 138, 138, 0.74);
                color: black;
            }
            .receipt-section {
                display: none;
                margin-top: 20px;
                padding: 20px;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            .card {
                border-radius: 15px;
                overflow: hidden;
            }
            .card-header {
                background-color: #007bff;
                color: white;
            }
            .btn-primary {
                background-color: #007bff;
                border: none;
            }
            .btn-primary:hover {
                background-color: #0056b3;
            }
            .btn-success {
                background-color: #28a745;
                border: none;
            }
            .btn-success:hover {
                background-color: #218838;
            }
            .modal-content {
                border-radius: 15px;
            }
            .modal-header {
                background-color: #007bff;
                color: white;
            }
            .modal-body img {
                border-radius: 10px;
            }
            .container {
            padding-top: 70px; /* ระยะห่างจาก navbar */
            }
            #close-btn {
                position: absolute;
                top: 10px;
                right: 10px;
                font-size: 2rem;
                color: #f8f9fa; /* ไอคอนสีแดง */
                cursor: pointer;
            }


        </style>
</head>
<body>

<div class="container">
    <div class="card shadow-lg ">
        <div class="card-header">
            <h3 class="text-center">Transport Registration</h3>
        </div>
        <div class="card-body">
            <form id="registration-form" action="enrollment_db.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Username</label>
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
                    <label class="form-label">Route Image</label><br>
                    <img id="route-image" src="" alt="Route Image" style="max-width: 50%; cursor: pointer; display: none;">
                </div>
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" style="display: none;" inert>
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel">View Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <img id="modal-image" src="" class="img-fluid" alt="Route Image">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Transport Schedule</label>
                    <select name="schedule" class="form-select" id="schedule" required>
                        <?php while ($schedule = $result_schedule->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $schedule['id']; ?>" data-num-of-days="<?php echo $schedule['num_of_days']; ?>">
                                <?php echo 'ปี : ' . $schedule['year'] . ' / เดือน : ' . $schedule['month'] . ' / วันที่ ' . $schedule['available_dates']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Number of Days</label>
                    <input type="number" class="form-control" id="num_of_days" name="num_of_days" min="1" required readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="text" class="form-control" id="price" name="price" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Price</label>
                    <input type="text" class="form-control" id="total_price" name="total_price" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Receipt Image</label>
                    <input type="file" class="form-control" name="payment_receipt" required>
                </div>
                <button type="button" class="btn btn-primary w-100" id="preview-btn">Preview Information</button>
                <button type="submit" class="btn btn-success w-100 mt-3" id="submit-btn" style="display:none;">Register</button>
            </form>
            <div id="receipt-section" class="receipt-section">
    <i id="close-btn" class="fas fa-times-circle"></i> <!-- ใช้ไอคอนแทนปุ่มปิด -->
    <h4>Your Transport Registration Details</h4>
    <p><strong>Username:</strong> <span id="receipt-username"></span></p>
    <p><strong>Province:</strong> <span id="receipt-province"></span></p>
    <p><strong>Amphur:</strong> <span id="receipt-amphur"></span></p>
    <p><strong>Location:</strong> <span id="receipt-location"></span></p>
    <p><strong>Transport Schedule:</strong> <span id="receipt-schedule"></span></p>
    <p><strong>Number of Days:</strong> <span id="receipt-num-of-days"></span></p>
    <p><strong>Price:</strong> <span id="receipt-price"></span></p>
    <p><strong>Total Price:</strong> <span id="receipt-total-price"></span></p>
    <p><strong>Payment Receipt:</strong> <span id="receipt-receipt"></span></p>
    <button type="button" class="btn btn-success w-100" id="confirm-btn">Confirm and Register</button>
</div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- โหลด SweetAlert2 -->
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