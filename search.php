<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/functions.php';

$universities = get_universities();
$amenities_list = ['WiFi', 'Water', 'Electricity', 'Security', 'Laundry', 'Cafeteria', 'Parking'];

// Get search parameters
$university_id = $_GET['university_id'] ?? null;
$min_price = $_GET['min_price'] ?? null;
$max_price = $_GET['max_price'] ?? null;
$selected_amenities = $_GET['amenities'] ?? [];

// Search hostels if filters are applied
$hostels = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (!empty($university_id) || !empty($min_price) || !empty($max_price) || !empty($selected_amenities))) {
    $hostels = search_hostels($university_id, $min_price, $max_price, $selected_amenities);
}
?>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>Filter Hostels</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="search.php">
                    <div class="mb-3">
                        <label for="university_id" class="form-label">University:</label>
                        <select class="form-select" id="university_id" name="university_id">
                            <option value="">All Universities</option>
                            <?php foreach ($universities as $university): ?>
                                <option value="<?php echo $university['university_id']; ?>" 
                                    <?php echo ($university_id == $university['university_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($university['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price Range (UGX/month):</label>
                        <div class="row">
                            <div class="col">
                                <input type="number" class="form-control" placeholder="Min" name="min_price" 
                                       value="<?php echo htmlspecialchars($min_price); ?>">
                            </div>
                            <div class="col">
                                <input type="number" class="form-control" placeholder="Max" name="max_price" 
                                       value="<?php echo htmlspecialchars($max_price); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amenities:</label>
                        <?php foreach ($amenities_list as $amenity): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" 
                                       id="amenity_<?php echo strtolower($amenity); ?>" 
                                       value="<?php echo $amenity; ?>"
                                       <?php echo in_array($amenity, $selected_amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="amenity_<?php echo strtolower($amenity); ?>">
                                    <?php echo $amenity; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    <?php if (!empty($university_id) || !empty($min_price) || !empty($max_price) || !empty($selected_amenities)): ?>
                        <a href="search.php" class="btn btn-outline-secondary w-100 mt-2">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Available Hostels</h2>
            <?php if (is_logged_in() && $_SESSION['user_type'] === 'landlord'): ?>
                <a href="listings.php?action=create" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Add New Hostel
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($hostels) && ($_SERVER['REQUEST_METHOD'] === 'GET') && (!empty($university_id) || !empty($min_price) || !empty($max_price) || !empty($selected_amenities))): ?>
            <div class="alert alert-info">
                No hostels found matching your criteria. Try adjusting your filters.
            </div>
        <?php elseif (empty($hostels)): ?>
            <div class="alert alert-info">
                Use the filters to find hostels near your university.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($hostels as $hostel): 
                    $amenities = json_decode($hostel['amenities'], true) ?? [];
                    $primary_image = get_hostel_images($hostel['hostel_id']);
                    $primary_image_path = !empty($primary_image) ? 'uploads/' . $primary_image[0]['image_path'] : '.hostel_images/hostel-placeholder.jpg';
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo $primary_image_path; ?>" class="card-img-top hostel-image" alt="<?php echo htmlspecialchars($hostel['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($hostel['name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($hostel['university_name']); ?></h6>
                                <p class="card-text text-success fw-bold">UGX <?php echo number_format($hostel['price_per_month']); ?>/month</p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <?php echo round($hostel['distance_to_university'], 1); ?> km from campus
                                    </small>
                                </p>
                                <div class="amenities mb-2">
                                    <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                                        <span class="badge bg-primary me-1"><?php echo htmlspecialchars($amenity); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($amenities) > 3): ?>
                                        <span class="badge bg-secondary">+<?php echo count($amenities) - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="hostel-details.php?id=<?php echo $hostel['hostel_id']; ?>" class="btn btn-outline-primary w-100">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$include_maps = true;
require_once __DIR__ . '/footer.php'; 
?>