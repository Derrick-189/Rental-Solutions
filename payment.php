<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only students can make payments
check_user_role(['student']);

if (!isset($_GET['booking_id'])) {
    header("Location: search.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// Verify booking belongs to this student and get booking details
$query = "SELECT b.*, h.name AS hostel_name, h.address AS hostel_address, 
                 u.name AS university_name, u.logo AS university_logo
          FROM bookings b
          JOIN hostels h ON b.hostel_id = h.hostel_id
          JOIN universities u ON h.university_id = u.university_id
          WHERE b.booking_id = ? AND b.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

// Check if booking is already paid
if ($booking['payment_status'] === 'paid') {
    header("Location: booking-confirmation.php?id=" . $booking_id);
    exit();
}

$page_title = "Complete Payment";
require_once __DIR__ . '/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Complete Your Payment</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Booking Summary</h5>
                            <div class="border rounded p-3 mb-4">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Booking ID:</th>
                                        <td>#<?= $booking_id ?></td>
                                    </tr>
                                    <tr>
                                        <th>Hostel:</th>
                                        <td><?= htmlspecialchars($booking['hostel_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>University:</th>
                                        <td><?= htmlspecialchars($booking['university_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Dates:</th>
                                        <td>
                                            <?= date('M j, Y', strtotime($booking['start_date'])) ?> -<br>
                                            <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Payment Details</h5>
                            <div class="border rounded p-3 mb-4">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Hostel Rent:</th>
                                        <td class="text-end">UGX <?= number_format($booking['total_amount'] - $booking['platform_fee']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Platform Fee:</th>
                                        <td class="text-end">UGX <?= number_format($booking['platform_fee']) ?></td>
                                    </tr>
                                    <tr class="table-active">
                                        <th><strong>Total Amount:</strong></th>
                                        <td class="text-end"><strong>UGX <?= number_format($booking['total_amount']) ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="process-payment.php">
                        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                        
                        <div class="mb-4">
                            <h5>Select Payment Method</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check card p-3">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="mobile_money" value="mobile_money" checked>
                                        <label class="form-check-label" for="mobile_money">
                                            <i class="bi bi-phone fs-4 text-primary"></i><br>
                                            <strong>Mobile Money</strong><br>
                                            <small class="text-muted">MTN, Airtel, Africell</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check card p-3">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="credit_card" value="credit_card">
                                        <label class="form-check-label" for="credit_card">
                                            <i class="bi bi-credit-card fs-4 text-primary"></i><br>
                                            <strong>Credit Card</strong><br>
                                            <small class="text-muted">Visa, MasterCard</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check card p-3">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="bank_transfer" value="bank_transfer">
                                        <label class="form-check-label" for="bank_transfer">
                                            <i class="bi bi-bank fs-4 text-primary"></i><br>
                                            <strong>Bank Transfer</strong><br>
                                            <small class="text-muted">Direct bank transfer</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Money Details -->
                        <div id="mobile_money_details" class="payment-details">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Mobile Money Instructions</h6>
                                <p class="mb-1">1. Dial *165# on your phone</p>
                                <p class="mb-1">2. Select "Send Money"</p>
                                <p class="mb-1">3. Enter merchant number: <strong>0761891599</strong></p>
                                <p class="mb-1">4. Enter amount: <strong>UGX <?= number_format($booking['total_amount']) ?></strong></p>
                                <p class="mb-0">5. Enter reference: <strong>BOOK<?= $booking_id ?></strong></p>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mobile_number" class="form-label">Your Mobile Number</label>
                                <input type="tel" class="form-control" id="mobile_number" name="mobile_number" 
                                       placeholder="07XXXXXXXX" required>
                            </div>
                            <div class="mb-3">
                                <label for="transaction_code" class="form-label">Transaction Code</label>
                                <input type="text" class="form-control" id="transaction_code" name="transaction_code" 
                                       placeholder="Enter transaction code from SMS" required>
                            </div>
                        </div>

                        <!-- Credit Card Details -->
                        <div id="credit_card_details" class="payment-details" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" 
                                           placeholder="1234 5678 9012 3456" disabled>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                           placeholder="MM/YY" disabled>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" 
                                           placeholder="123" disabled>
                                </div>
                            </div>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Credit card payments are currently unavailable. Please use Mobile Money.
                            </div>
                        </div>

                        <!-- Bank Transfer Details -->
                        <div id="bank_transfer_details" class="payment-details" style="display: none;">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Bank Transfer Instructions</h6>
                                <p class="mb-1"><strong>Bank:</strong> Centenary Bank</p>
                                <p class="mb-1"><strong>Account Name:</strong> Rental Solutions</p>
                                <p class="mb-1"><strong>Account Number:</strong> 31013456789</p>
                                <p class="mb-1"><strong>Amount:</strong> UGX <?= number_format($booking['total_amount']) ?></p>
                                <p class="mb-0"><strong>Reference:</strong> BOOK<?= $booking_id ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bank_name" class="form-label">Your Bank Name</label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="transfer_date" class="form-label">Transfer Date</label>
                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" disabled>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms_agreement" required>
                            <label class="form-check-label" for="terms_agreement">
                                I agree to the <a href="#" target="_blank">Terms and Conditions</a> and 
                                <a href="#" target="_blank">Cancellation Policy</a>
                            </label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="s_bookings.php" class="btn btn-outline-secondary me-md-2">
                                <i class="bi bi-arrow-left"></i> Back to Bookings
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-lock-fill"></i> Pay UGX <?= number_format($booking['total_amount']) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide payment method details
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Hide all payment details
        document.querySelectorAll('.payment-details').forEach(detail => {
            detail.style.display = 'none';
        });
        
        // Disable all inputs in hidden sections
        document.querySelectorAll('.payment-details input').forEach(input => {
            input.disabled = true;
            input.required = false;
        });
        
        // Show selected payment details
        const selectedDetails = document.getElementById(this.value + '_details');
        if (selectedDetails) {
            selectedDetails.style.display = 'block';
            
            // Enable inputs in visible section
            selectedDetails.querySelectorAll('input').forEach(input => {
                input.disabled = false;
                if (input.id !== 'card_number' && input.id !== 'expiry_date' && input.id !== 'cvv' && 
                    input.id !== 'bank_name' && input.id !== 'transfer_date') {
                    input.required = true;
                }
            });
        }
    });
});

// Initialize with mobile money selected
document.getElementById('mobile_money').dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/footer.php'; ?>