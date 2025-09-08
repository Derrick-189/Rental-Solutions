<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only students can book hostels
check_user_role(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['hostel_id'])) {
    header("Location: search.php");
    exit();
}

$hostel_id = intval($_POST['hostel_id']);
$hostel = get_hostel($hostel_id);

if (!$hostel) {
    header("Location: search.php");
    exit();
}

// Calculate total amount (1 month rent + platform fee)
$rent = $hostel['price_per_month'];
$platform_fee = 10000; // UGX
$total_amount = $rent + $platform_fee;

// Create booking
$query = "INSERT INTO bookings (student_id, hostel_id, landlord_id, start_date, end_date, 
                               total_amount, platform_fee, status, payment_method, payment_status)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending')";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiissdds", $_SESSION['user_id'], $hostel_id, $hostel['landlord_id'], 
                 $_POST['start_date'], $_POST['end_date'], $total_amount, $platform_fee, 
                 $_POST['payment_method']);
$stmt->execute();

$booking_id = $conn->insert_id;

// Redirect to payment page
header("Location: payment.php?booking_id=" . $booking_id);
exit();
?>