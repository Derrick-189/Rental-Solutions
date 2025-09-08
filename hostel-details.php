<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/functions.php';

if (!isset($_GET['id'])) {
    header("Location: search.php");
    exit();
}

$hostel_id = (int)$_GET['id'];
$hostel = get_hostel($hostel_id);
$images = get_hostel_images($hostel_id);

if (!$hostel) {
    header("Location: search.php");
    exit();
}

// Get amenities as array
$amenities = json_decode($hostel['amenities'], true) ?? [];

// Calculate distance to university
$distance = calculate_distance(
    $hostel['latitude'], $hostel['longitude'],
    $hostel['uni_lat'], $hostel['uni_lon']
);
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <!-- Hostel Image Gallery -->
            <div id="hostelCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php if (empty($images)): ?>
                        <div class="carousel-item active">
                            <img src="/assets/images/hostel-placeholder.jpg" class="d-block w-100" alt="Hostel Image">
                        </div>
                    <?php else: ?>
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="/uploads/hostel_images/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     class="d-block w-100 hostel-detail-image" alt="Hostel Image">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#hostelCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#hostelCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
            
            <div class="card-body">
                <h2 class="card-title"><?php echo htmlspecialchars($hostel['name']); ?></h2>
                <p class="text-muted">
                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($hostel['address']); ?>
                </p>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-success mb-0">UGX <?php echo number_format($hostel['price_per_month']); ?> <small class="text-muted">/month</small></h4>
                    <span class="badge bg-primary">
                        <?php echo round($distance, 1); ?> km from <?php echo htmlspecialchars($hostel['university_name']); ?>
                    </span>
                </div>
                
                <div class="mb-4">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($hostel['description'])); ?></p>
                </div>
                
                <div class="mb-4">
                    <h5>Amenities</h5>
                    <div class="amenities-container">
                        <?php foreach ($amenities as $amenity): ?>
                            <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($amenity); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5>Rules</h5>
                    <p><?php echo nl2br(htmlspecialchars($hostel['rules'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Map Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Location</h5>
            </div>
            <div class="card-body p-0">
                <div id="hostelMap" style="height: 400px; width: 100%;"></div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    Distance to <?php echo htmlspecialchars($hostel['university_name']); ?>: <?php echo round($distance, 1); ?> km
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Book This Hostel</h5>
            </div>
            <div class="card-body">
                <?php if (is_logged_in() && $_SESSION['user_type'] === 'student'): ?>
                    <form id="bookingForm" method="POST" action="booking.php">
                        <input type="hidden" name="hostel_id" value="<?php echo $hostel_id; ?>">
                        
                        <div class="mb-3">
                            <label for="check_in" class="form-label">Check-in Date</label>
                            <input type="date" class="form-control" id="check_in" name="check_in" required>
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
                            <label class="form-label">Payment Method</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="mobile_money" value="mobile_money" checked>
                                <label class="form-check-label" for="mobile_money">
                                    Mobile Money
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card">
                                <label class="form-check-label" for="credit_card">
                                    Credit Card
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <p class="mb-1">Total Amount: <span id="totalAmount">UGX <?php echo number_format($hostel['price_per_month']); ?></span></p>
                            <p class="mb-0">Platform Fee: <span id="platformFee">UGX <?php echo number_format($hostel['price_per_month'] * 0.05); ?></span></p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Book Now</button>
                    </form>
                <?php elseif (is_logged_in() && $_SESSION['user_type'] === 'landlord'): ?>
                    <div class="alert alert-info">
                        You cannot book hostels as a landlord.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        You need to <a href="login.php">login</a> as a student to book this hostel.
                    </div>
                    <a href="login.php" class="btn btn-primary w-100">Login</a>
                    <div class="text-center mt-2">
                        Don't have an account? <a href="register.php">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Contact Landlord</h5>
            </div>
            <div class="card-body">
                <?php if (is_logged_in()): ?>
                    <form id="contactForm" method="POST" action="send-message.php">
                        <input type="hidden" name="hostel_id" value="<?php echo $hostel_id; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $hostel['landlord_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary w-100">Send Message</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        You need to <a href="login.php">login</a> to contact the landlord.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate total amount based on duration
document.getElementById('duration').addEventListener('change', function() {
    const pricePerMonth = <?php echo $hostel['price_per_month']; ?>;
    const duration = this.value;
    const totalAmount = pricePerMonth * duration;
    const platformFee = totalAmount * 0.05; // 5% platform fee
    
    document.getElementById('totalAmount').textContent = 'UGX ' + totalAmount.toLocaleString();
    document.getElementById('platformFee').textContent = 'UGX ' + platformFee.toLocaleString();
});

// Initialize hostel map
function initMap() {
    const hostelLocation = { lat: <?php echo $hostel['latitude']; ?>, lng: <?php echo $hostel['longitude']; ?> };
    const universityLocation = { lat: <?php echo $hostel['uni_lat']; ?>, lng: <?php echo $hostel['uni_lon']; ?> };
    
    const map = new google.maps.Map(document.getElementById("hostelMap"), {
        zoom: 14,
        center: hostelLocation,
    });
    
    // Hostel marker
    new google.maps.Marker({
        position: hostelLocation,
        map: map,
        title: "<?php echo addslashes($hostel['name']); ?>",
        icon: {
            url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        }
    });
    
    // University marker
    new google.maps.Marker({
        position: universityLocation,
        map: map,
        title: "<?php echo addslashes($hostel['university_name']); ?>",
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

<?php 
$include_maps = true;
require_once __DIR__ . '/footer.php'; 
?>