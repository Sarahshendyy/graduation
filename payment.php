<?php
include "mail.php";

$error = "";

// Check if booking_id is provided in the URL
if (!isset($_GET['booking_id'])) {
    die("Booking ID is missing.");
}

$bookingId = mysqli_real_escape_string($connect, $_GET['booking_id']);

// Fetch booking details
$query = "SELECT b.*, r.room_name, r.`p/hr`, r.workspace_id, w.name AS workspace_name, u.email AS user_email 
          FROM bookings b
          JOIN rooms r ON b.room_id = r.room_id
          JOIN workspaces w ON r.workspace_id = w.workspace_id
          JOIN users u ON b.user_id = u.user_id
          WHERE b.booking_id = ?";
$stmt = $connect->prepare($query);
if (!$stmt) {
    die("Database error: " . $connect->error);
}

$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}

$bookingData = $result->fetch_assoc();

// Extract booking details
$workspaceName = $bookingData['workspace_name'];
$roomName = $bookingData['room_name'];
$date = $bookingData['date'];
$startTime = $bookingData['start_time'];
$endTime = $bookingData['end_time'];
$pricePerHour = $bookingData['p/hr'];
$userEmail = $bookingData['user_email'];

// Calculate the number of hours booked
$startDateTime = new DateTime($startTime);
$endDateTime = new DateTime($endTime);
$interval = $startDateTime->diff($endDateTime);
$hoursBooked = $interval->h + ($interval->i / 60); // Convert minutes to hours

// Calculate the total amount
$totalAmount = $pricePerHour * $hoursBooked;

// Process payment
if (isset($_POST['pay'])) {
    $transactionId = rand(10000, 99999);
    $amount = $totalAmount; // Use the calculated total amount
    $workspaceId = $bookingData['workspace_id'];

    // Start transaction
    mysqli_begin_transaction($connect);
    
    try {
        // Update the bookings table with payment info
        $updateBooking = "UPDATE bookings 
                         SET total_price = '$totalAmount', 
                             pay_method = 'Online',
                             status = 'upcoming'
                         WHERE booking_id = '$bookingId'";
        $runUpdate = mysqli_query($connect, $updateBooking);
        
        if (!$runUpdate) {
            throw new Exception("Failed to update booking record.");
        }
        
        // Insert into payments table
        $insert = "INSERT INTO payments (booking_id, workspace_id, amount, payment_method, transaction_id, created_at) 
                   VALUES ('$bookingId', '$workspaceId', '$totalAmount', 'Online', '$transactionId', NOW())";
        $run_insert = mysqli_query($connect, $insert);
        
        if (!$run_insert) {
            throw new Exception("Failed to insert payment record.");
        }
        
        // Commit transaction
        mysqli_commit($connect);
        
        // Send email confirmation
        $subject = "Your Payment Confirmation";
        $message = "
        <body style='font-family: DM Sans, Arial, sans-serif; margin: 0; padding: 0; background-color: #fff; color: #071739;'>
            <div style='background-color: #071739; padding: 28px 0 18px 0; text-align: left; color: #E3C39D;'>
                <h1 style='margin: 0 0 0 40px; font-size: 2.2rem; font-weight: bold; letter-spacing: 1px;'>Payment Confirmation</h1>
            </div>
            <div style='padding: 32px 40px 24px 40px; background-color: #fff; color: #071739; text-align: left;'>
                <p>Dear <span style='color: #A68868;'>Customer</span>,</p>
                <p>Thank you for your payment with <b>WorkSphere</b>! Your booking has been confirmed. Here are your details:</p>
                <ul style='list-style: none; padding-left: 0; margin-bottom: 18px;'>
                    <li style='margin-bottom: 8px;'><strong>Workspace:</strong> <span style='color: #A68868;'>$workspaceName</span></li>
                    <li style='margin-bottom: 8px;'><strong>Room:</strong> <span style='color: #A68868;'>$roomName</span></li>
                    <li style='margin-bottom: 8px;'><strong>Date:</strong> <span style='color: #A68868;'>$date</span></li>
                    <li style='margin-bottom: 8px;'><strong>Time:</strong> <span style='color: #A68868;'>$startTime to $endTime</span></li>
                    <li style='margin-bottom: 8px;'><strong>Total Amount Paid:</strong> <span style='color: #A68868;'>$" . number_format($totalAmount, 2) . "</span></li>
                    <li style='margin-bottom: 8px;'><strong>Transaction ID:</strong> <span style='color: #A68868;'>$transactionId</span></li>
                    <li style='margin-bottom: 8px;'><strong>Payment Method:</strong> <span style='color: #A68868;'>Online</span></li>
                </ul>
                <p style='color: #A68868; margin-bottom: 18px;'>Your booking is now confirmed. Please present this confirmation at the reception.</p>
                <p>If you have any questions, feel free to contact us.</p>
                <p style='margin-top: 32px;'>Best regards,<br>The WorkSphere Team</p>
            </div>
            <div style='background-color: #4B6382; padding: 18px 40px; text-align: left; color: #E3C39D;'>
                <p style='margin: 0 0 6px 0;'>For any questions, please contact:</p>
                <p style='margin: 0;'>Email: <a href='mailto:worksphere50@gmail.com' style='color: #A68868; text-decoration: underline;'>worksphere50@gmail.com</a></p>
            </div>
        </body>";

        // Send the email
        $mail->setFrom('worksphere50@gmail.com', 'WorkSphere');
        $mail->addAddress($userEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();

        // Redirect to success page
        header("Location: my_bookings.php");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($connect);
        $error = "Payment failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link rel="stylesheet" href="css/payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="js/payment.js"></script>

</head>

<body>
    <div class="container">
        <div class="card-container">
            <div class="front">
                <div class="image">
                    <img src="img/chip-card2 (1).png" alt="">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="visa-icon">
                </div>
                <div class="card-number-box">################</div>
                <div class="flexbox">
                    <div class="box" style="margin-right: 32px;">
                        <span>Card Holder</span>
                        <div class="card-holder-name">Full Name</div>
                    </div>
                    <div class="box">
                        <span>expires</span>
                        <div class="expiration">
                            <span class="exp-month">mm</span>
                            <span class="exp-year">yy</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="back">
                <div class="stripe"></div>
                <div class="box">
                    <span>cvv</span>
                    <div class="cvv-box"></div>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="visa-icon">
                </div>
            </div>
        </div>
        <!-- Payment Form -->
        <form method="POST" onsubmit="return validateForm()" style="position:relative;">
            <a href="javascript:history.back()" class="close-btn" title="Go back">
                <i class="fas fa-times"></i>
            </a>
            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
            <div class="inputBox">
                <span class="span">Card Number</span>
                <input type="number" maxlength="16" name="card_number" id="card-number-input" class="card-number-input" oninput="validateNum()" required>
                <span name="numError" id="numError" class="error" style="display:none;"></span>
            </div>
            <div class="inputBox">
                <span class="span">Card Holder</span>
                <input type="text" class="card-holder-input" name="C-HOLDER" id="card-holder-input" oninput="validateName()" required>
                <span name="nameError" id="nameError" class="error" style="display:none;"></span>
            </div>
            <div class="flexbox">
                <div class="inputBox">
                    <span class="span">Expiration MM</span>
                    <select name="MM" id="month-input" oninput="validateMonth()" class="month-input" required>
                        <option value="month" id="MONTH">Month</option>
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            echo "<option value='$i'>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
                        }
                        ?>
                    </select>
                    <span name="monthError" id="monthError" class="error" style="display:none;"></span>
                </div>
                <div class="inputBox">
                    <span class="span">Expiration YY</span>
                    <select name="YY" id="year-input" oninput="validateYear()" class="year-input" required>
                        <option value="year" id="YEAR">Year</option>
                        <?php
                        for ($i = date('Y'); $i <= date('Y') + 10; $i++) {
                            echo "<option value='$i'>$i</option>";
                        }
                        ?>
                    </select>
                    <span name="yearError" id="yearError" class="error" style="display:none;"></span>
                </div>
                <div class="inputBox">
                    <span>CVV</span>
                    <input type="text" maxlength="3" class="cvv-input" name="cvv" id="cvv-input" oninput="validateCvv()" required>
                    <span name="cvvError" id="cvvError" class="error" style="display:none;"></span>
                </div>
            </div>
            <div class="btns">
                <div class="buttons">
                    <a href="#">
                    <button class="cssbuttons-io-button addto" type="submit" name="pay">
                        Pay
                        <div class="icon">
                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M16.172 11l-5.364-5.364 1.414-1.414L20 12l-7.778 7.778-1.414-1.414L16.172 13H4v-2z" fill="currentColor"></path>
                            </svg>
                        </div>
                    </button>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
    // Card Number Display and Validation
    document.getElementById('card-number-input').oninput = () => {
        document.querySelector('.card-number-box').innerText = document.getElementById('card-number-input').value;
        validateNum();
    };

    // Card Holder Name Display and Validation
    document.getElementById('card-holder-input').oninput = () => {
        document.querySelector('.card-holder-name').innerText = document.getElementById('card-holder-input').value;
        validateName();
    };

    // Expiration Month Display and Validation
    document.getElementById('month-input').oninput = () => {
        document.querySelector('.exp-month').innerText = document.getElementById('month-input').value;
        validateMonth();
    };

    // Expiration Year Display and Validation
    document.getElementById('year-input').oninput = () => {
        document.querySelector('.exp-year').innerText = document.getElementById('year-input').value;
        validateYear();
    };

    // CVV Display and Validation
    document.getElementById('cvv-input').oninput = () => {
        document.querySelector('.cvv-box').innerText = document.getElementById('cvv-input').value;
        validateCvv();
    };

    // Rotate card on CVV focus
    document.getElementById('cvv-input').onmouseenter = () => {
        document.querySelector('.front').style.transform = 'perspective(1000px) rotateY(-180deg)';
        document.querySelector('.back').style.transform = 'perspective(1000px) rotateY(0deg)';
    };

    // Rotate card back on CVV blur
    document.getElementById('cvv-input').onmouseleave = () => {
        document.querySelector('.front').style.transform = 'perspective(1000px) rotateY(0deg)';
        document.querySelector('.back').style.transform = 'perspective(1000px) rotateY(180deg)';
    };

    // Prevent form submission if validation fails
    document.querySelector('form').onsubmit = function (e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    };

    // Validation Functions
    function validateNum() {
        const cardNumber = document.getElementById('card-number-input').value;
        const numError = document.getElementById('numError');
        if (cardNumber.length !== 16) {
            numError.innerText = 'Card number must be 16 digits.';
            numError.style.display = 'block';
            return false;
        } else {
            numError.style.display = 'none';
            return true;
        }
    }

    function validateName() {
        const cardHolder = document.getElementById('card-holder-input').value;
        const nameError = document.getElementById('nameError');
        if (!cardHolder.match(/^[A-Za-z ]+$/)) {
            nameError.innerText = 'Invalid cardholder name.';
            nameError.style.display = 'block';
            return false;
        } else {
            nameError.style.display = 'none';
            return true;
        }
    }

    function validateMonth() {
        const month = document.getElementById('month-input').value;
        const monthError = document.getElementById('monthError');
        if (month === 'month') {
            monthError.innerText = 'Please select a valid month.';
            monthError.style.display = 'block';
            return false;
        } else {
            monthError.style.display = 'none';
            return true;
        }
    }

    function validateYear() {
        const year = document.getElementById('year-input').value;
        const yearError = document.getElementById('yearError');
        if (year === 'year') {
            yearError.innerText = 'Please select a valid year.';
            yearError.style.display = 'block';
            return false;
        } else {
            yearError.style.display = 'none';
            return true;
        }
    }

    function validateCvv() {
        const cvv = document.getElementById('cvv-input').value;
        const cvvError = document.getElementById('cvvError');
        if (cvv.length !== 3 || !cvv.match(/^\d{3}$/)) {
            cvvError.innerText = 'CVV must be 3 digits.';
            cvvError.style.display = 'block';
            return false;
        } else {
            cvvError.style.display = 'none';
            return true;
        }
    }

    function validateForm() {
        return (
            validateNum() &&
            validateName() &&
            validateMonth() &&
            validateYear() &&
            validateCvv()
        );
    }
});
    </script>
</body>
</html>
