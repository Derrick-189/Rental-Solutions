<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required form data
    if (!isset($_POST['hostel_id']) || !isset($_POST['start_date']) || !isset($_POST['duration'])) {
        header("Location: search.php?error=missing_data");
        exit();
    }
    
    // Retrieve form data
    $hostel_id = (int)$_POST['hostel_id'];
    $start_date = $_POST['start_date'];
    $duration = (int)$_POST['duration'];

    // Validate start_date is not empty
    if (empty($start_date)) {
        header("Location: booking.php?id=$hostel_id&error=invalid_date");
        exit();
    }

    // Calculate the end date
    $end_date = date('Y-m-d', strtotime("+$duration months", strtotime($start_date)));

    // Calculate total amount (you need a function for this based on hostel price)
    $hostel = get_hostel($hostel_id);
    if (!$hostel) {
        header("Location: search.php?error=hostel_not_found");
        exit();
    }
    
    $total_amount = $hostel['price_per_month'] * $duration;
    $platform_fee = $total_amount * 0.05; // Example 5% fee
    $grand_total = $total_amount + $platform_fee;

    $user_id = $_SESSION['user_id'];
    $landlord_id = $hostel['landlord_id'];

    // Insert a new booking record into the database
    $query = "INSERT INTO bookings (student_id, hostel_id, landlord_id, start_date, end_date, total_amount, platform_fee, payment_method, status, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $payment_method = $_POST['payment_method'] ?? 'mobile_money';
    $status = 'pending';
    $payment_status = 'pending';
    $stmt->bind_param("iiisddssss", $user_id, $hostel_id, $landlord_id, $start_date, $end_date, $grand_total, $platform_fee, $payment_method, $status, $payment_status);

    if ($stmt->execute()) {
        // Get the ID of the new booking
        $booking_id = $conn->insert_id;

        // Redirect the user to the payment page with the booking ID
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    } else {
        // Handle error, e.g., redirect with an error message
        header("Location: booking.php?id=$hostel_id&error=booking_failed");
        exit();
    }
}


if (!isset($_GET['id'])) {
    header("Location: search.php");
    exit();
}

$hostel_id = (int)$_GET['id'];
$hostel = get_hostel($hostel_id);

if (!$hostel) {
    header("Location: search.php");
    exit();
}

$images = get_hostel_images($hostel_id);
$amenities = json_decode($hostel['amenities'], true) ?? [];

$page_title = $hostel['name'];
require_once __DIR__ . '/header.php';

// Display error messages if any
if (isset($_GET['error'])) {
    $error_messages = [
        'booking_failed' => 'Sorry, there was an error processing your booking. Please try again.',
        'invalid_dates' => 'Please select valid dates for your booking.',
        'no_rooms' => 'Sorry, this hostel is fully booked. No rooms available.',
        'missing_data' => 'Please fill in all required fields.',
        'invalid_date' => 'Please select a valid start date.',
        'hostel_not_found' => 'The selected hostel was not found.'
    ];
    
    if (isset($error_messages[$_GET['error']])) {
        echo '<div class="alert alert-danger">' . $error_messages[$_GET['error']] . '</div>';
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!empty($images)): ?>
                        <div id="hostelCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="/uploads/hostel_images/<?= $image['image_path'] ?>" 
                                             class="d-block w-100" style="height: 400px; object-fit: cover;" 
                                             alt="<?= htmlspecialchars($hostel['name']) ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#hostelCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#hostelCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>
                    <?php else: ?>
                        <img src="/assets/images/hostel-placeholder.jpg" class="img-fluid w-100" 
                             style="height: 400px; object-fit: cover;" alt="Hostel Image">
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Hostel Details</h5>
                </div>
                <div class="card-body">
                    <h4><?= htmlspecialchars($hostel['name']) ?></h4>
                    <p class="text-muted">
                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($hostel['address']) ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="text-success mb-0">UGX <?= number_format($hostel['price_per_month']) ?> 
                            <small class="text-muted">/month</small>
                        </h4>
                        <span class="badge bg-primary">
                            <?= round($hostel['distance_to_university'], 1) ?> km from <?= htmlspecialchars($hostel['university_name']) ?>
                        </span>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($hostel['description'])) ?></p>
                    </div>
                    
                    <?php if (!empty($amenities)): ?>
                    <div class="mb-4">
                        <h5>Amenities</h5>
                        <div class="amenities-container">
                            <?php foreach ($amenities as $amenity): ?>
                                <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($amenity) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($hostel['rules'])): ?>
                    <div class="mb-4">
                        <h5>Rules & Regulations</h5>
                        <p><?= nl2br(htmlspecialchars($hostel['rules'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h5>Availability</h5>
                        <p class="<?= $hostel['rooms_available'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <i class="bi bi-<?= $hostel['rooms_available'] > 0 ? 'check-circle' : 'x-circle' ?>"></i>
                            <?= $hostel['rooms_available'] > 0 ? 
                                $hostel['rooms_available'] . ' room(s) available' : 
                                'Fully booked' ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Location</h5>
                </div>
                <div class="card-body p-0">
                    <div id="hostelMap" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        Distance to <?= htmlspecialchars($hostel['university_name']) ?>: 
                        <?= round($hostel['distance_to_university'], 1) ?> km
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Book This Hostel</h5>
                </div>
                <div class="card-body">
                    <?php if ($hostel['rooms_available'] > 0): ?>
                        <div class="text-center mb-3">
                            <h3 class="text-success">UGX <?= number_format($hostel['price_per_month']) ?></h3>
                            <small class="text-muted">per month</small>
                        </div>

                        <form method="POST" action="booking.php">
                            <input type="hidden" name="hostel_id" value="<?= $hostel_id ?>">
                            
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Move-in Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration (months)</label>
                                <select class="form-select" id="duration" name="duration" required>
                                    <option value="1">1 Month</option>
                                    <option value="3">3 Months</option>
                                    <option value="6">6 Months</option>
                                    <option value="12">12 Months</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Move-out Date</label>
                                <input type="text" class="form-control" id="end_date" readonly>
                                <small class="text-muted">This will be calculated automatically based on your duration selection.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="mobile_money" value="mobile_money" checked required>
                                    <label class="form-check-label" for="mobile_money">
                                        <i class="bi bi-phone"></i> Mobile Money
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="credit_card" value="credit_card" required>
                                    <label class="form-check-label" for="credit_card">
                                        <i class="bi bi-credit-card"></i> Credit Card
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="bank_transfer" value="bank_transfer" required>
                                    <label class="form-check-label" for="bank_transfer">
                                        <i class="bi bi-bank"></i> Bank Transfer
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <h6>Price Breakdown</h6>
                                <div class="d-flex justify-content-between">
                                    <span>Rent (<span id="durationDisplay">1</span> month):</span>
                                    <span>UGX <span id="rentAmount"><?= number_format($hostel['price_per_month']) ?></span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Platform Fee:</span>
                                    <span>UGX <span id="platformFee">10,000</span></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total Amount:</span>
                                    <span>UGX <span id="totalAmount"><?= number_format($hostel['price_per_month'] + 10000) ?></span></span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 btn-lg" id="bookButton">
                                <i class="bi bi-calendar-check"></i> Book Now
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                            <h5>Fully Booked</h5>
                            <p class="mb-0">This hostel is currently fully booked. Please check back later or explore other hostels.</p>
                        </div>
                        <a href="search.php" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Find Other Hostels
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($amenities)): ?>
                    <div class="mt-4">
                        <h6>Amenities Included</h6>
                        <div class="row">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="col-6 mb-2">
                                    <i class="bi bi-check-circle text-success"></i> 
                                    <small><?= htmlspecialchars($amenity) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Contact Landlord</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="send-message.php">
                        <input type="hidden" name="hostel_id" value="<?= $hostel_id ?>">
                        <input type="hidden" name="receiver_id" value="<?= $hostel['landlord_id'] ?>">
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="Inquiry about <?= htmlspecialchars($hostel['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                      placeholder="I'm interested in booking your hostel. Could you please provide more information?" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate end date and update prices based on duration
function updateBookingDetails() {
    const startDate = document.getElementById('start_date').value;
    const duration = parseInt(document.getElementById('duration').value);
    const pricePerMonth = <?= $hostel['price_per_month'] ?>;
    const platformFee = 10000;
    
    if (startDate) {
        // Calculate end date
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + duration);
        document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
        
        // Calculate prices
        const totalRent = pricePerMonth * duration;
        const totalAmount = totalRent + platformFee;
        
        // Update display
        document.getElementById('durationDisplay').textContent = duration;
        document.getElementById('rentAmount').textContent = totalRent.toLocaleString();
        document.getElementById('totalAmount').textContent = totalAmount.toLocaleString();
    }
}

// Event listeners
document.getElementById('start_date').addEventListener('change', updateBookingDetails);
document.getElementById('duration').addEventListener('change', updateBookingDetails);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').min = today;
    
    // Initialize booking details
    updateBookingDetails();
});

// Initialize hostel map
function initMap() {
    const hostelLocation = { 
        lat: <?= $hostel['latitude'] ?>, 
        lng: <?= $hostel['longitude'] ?> 
    };
    const universityLocation = { 
        lat: <?= $hostel['uni_lat'] ?>, 
        lng: <?= $hostel['uni_lon'] ?> 
    };
    
    const map = new google.maps.Map(document.getElementById("hostelMap"), {
        zoom: 14,
        center: hostelLocation,
    });
    
    // Hostel marker
    new google.maps.Marker({
        position: hostelLocation,
        map: map,
        title: "<?= htmlspecialchars($hostel['name']) ?>",
        icon: {
            url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        }
    });
    
    // University marker
    new google.maps.Marker({
        position: universityLocation,
        map: map,
        title: "<?= htmlspecialchars($hostel['university_name']) ?>",
        icon: {
            url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
        }
    });
    
    // Draw a line between hostel and university
    new google.maps.Polyline({
        path: [hostelLocation, universityLocation],
        geodesic: true,
        strokeColor: "#FF0000",
        strokeOpacity: 1.0,
        strokeWeight: 2,
        map: map
    });
}

window.initMap = initMap;
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>

<?php require_once __DIR__ . '/footer.php'; ?>