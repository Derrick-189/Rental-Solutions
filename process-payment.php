<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only students can make payments
check_user_role(['student']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    header("Location: dashboard.php");
    exit();
}

$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['user_id'];

// Verify booking belongs to this student
$query = "SELECT * FROM bookings WHERE booking_id = ? AND student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

// Generate a random transaction ID (in a real app, this would come from payment gateway)
$transaction_id = 'TXN' . time() . rand(1000, 9999);

// Update booking status
$update_query = "UPDATE bookings SET 
                 payment_status = 'paid',
                 status = 'confirmed',
                 transaction_id = ?
                 WHERE booking_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $transaction_id, $booking_id);
$stmt->execute();

// Record payment
$payment_query = "INSERT INTO payments (booking_id, amount, platform_fee, payment_method, transaction_id, status)
                  VALUES (?, ?, ?, ?, ?, 'completed')";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("iddss", $booking_id, $booking['total_amount'], $booking['platform_fee'], 
                  $_POST['payment_method'], $transaction_id);
$stmt->execute();

// Redirect to booking confirmation
header("Location: booking-confirmation.php?id=" . $booking_id);
exit();
?>