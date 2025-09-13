<?php
require_once __DIR__ . '/auth.php';

// Only landlords can access this page
check_user_role(['landlord']);
$user = get_current_user();

$page_title = "Manage Hostel Listings";
require_once __DIR__ . '/header.php';

$action = $_GET['action'] ?? 'list';
$hostel_id = $_GET['id'] ?? null;

// Get landlord's hostels
global $conn;
$query = "SELECT h.*, u.name AS university_name 
        FROM hostels h
        JOIN universities u ON h.university_id = u.university_id
        WHERE h.landlord_id = ?
        ORDER BY h.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$hostels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/functions.php';
    
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize_input($conn, $_POST['name']);
        $description = sanitize_input($conn, $_POST['description']);
        $address = sanitize_input($conn, $_POST['address']);
        $latitude = (float)$_POST['latitude'];
        $longitude = (float)$_POST['longitude'];
        $price = (float)$_POST['price'];
        $rooms = (int)$_POST['rooms'];
        $university_id = (int)$_POST['university_id'];
        $amenities = json_encode($_POST['amenities'] ?? []);
        $rules = sanitize_input($conn, $_POST['rules']);
        
        if ($action === 'create') {
            // Create new hostel
            $query = "INSERT INTO hostels (landlord_id, name, description, address, latitude, longitude, 
                                        price_per_month, rooms_available, university_id, amenities, rules)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssdddiiss", $_SESSION['user_id'], $name, $description, $address, 
                            $latitude, $longitude, $price, $rooms, $university_id, $amenities, $rules);
            
            if ($stmt->execute()) {
                $hostel_id = $conn->insert_id;
                
                // Calculate distance to university
                $university_query = "SELECT latitude, longitude FROM universities WHERE university_id = ?";
                $university_stmt = $conn->prepare($university_query);
                $university_stmt->bind_param("i", $university_id);
                $university_stmt->execute();
                $university = $university_stmt->get_result()->fetch_assoc();
                
                $distance = calculate_distance(
                    $latitude, $longitude,
                    $university['latitude'], $university['longitude']
                );
                
                // Update distance
                $update_query = "UPDATE hostels SET distance_to_university = ? WHERE hostel_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("di", $distance, $hostel_id);
                $update_stmt->execute();
                
                // Handle new image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_errors = [];
                    $uploaded_files = 0;
                    
                    foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                        if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                            $upload_errors[] = "Error uploading file: " . $_FILES['images']['name'][$index];
                            continue;
                        }
                        
                        $file = [
                            'name' => $_FILES['images']['name'][$index],
                            'type' => $_FILES['images']['type'][$index],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['images']['error'][$index],
                            'size' => $_FILES['images']['size'][$index]
                        ];
                        
                        $result = upload_hostel_image($file, $hostel_id, $index === 0);
                        
                        if (!$result['success']) {
                            $upload_errors[] = $result['message'] . " (" . $file['name'] . ")";
                        } else {
                            $uploaded_files++;
                        }
                    }
                    
                    if ($uploaded_files > 0) {
                        $_SESSION['success'] = "Hostel created with $uploaded_files images" . 
                                            (count($upload_errors) > 0 ? " (some uploads failed)" : "");
                    }
                }
                
                $_SESSION['success'] = "Hostel created successfully!" . (isset($_SESSION['success']) ? " " . $_SESSION['success'] : "");
                header("Location: listings.php");
                exit();
            } else {
                $error = "Error creating hostel: " . $conn->error;
            }
        } elseif ($action === 'edit' && $hostel_id) {
            // Update existing hostel
            $query = "UPDATE hostels 
                SET name = ?, description = ?, address = ?, latitude = ?, longitude = ?, 
                    price_per_month = ?, rooms_available = ?, university_id = ?, amenities = ?, rules = ?
                WHERE hostel_id = ? AND landlord_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssdddiissii", $name, $description, $address, $latitude, $longitude, 
                        $price, $rooms, $university_id, $amenities, $rules, $hostel_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Recalculate distance if university changed
                if ($_POST['original_university_id'] != $university_id) {
                    $university_query = "SELECT latitude, longitude FROM universities WHERE university_id = ?";
                    $university_stmt = $conn->prepare($university_query);
                    $university_stmt->bind_param("i", $university_id);
                    $university_stmt->execute();
                    $university = $university_stmt->get_result()->fetch_assoc();
                    
                    $distance = calculate_distance(
                        $latitude, $longitude,
                        $university['latitude'], $university['longitude']
                    );
                    
                    $update_query = "UPDATE hostels SET distance_to_university = ? WHERE hostel_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("di", $distance, $hostel_id);
                    $update_stmt->execute();
                }
                
                // Handle new image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_errors = [];
                    $uploaded_files = 0;
                    
                    foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
                        if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                            $upload_errors[] = "Error uploading file: " . $_FILES['images']['name'][$index];
                            continue;
                        }
                        
                        $file = [
                            'name' => $_FILES['images']['name'][$index],
                            'type' => $_FILES['images']['type'][$index],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['images']['error'][$index],
                            'size' => $_FILES['images']['size'][$index]
                        ];
                        
                        $result = upload_hostel_image($file, $hostel_id, false);
                        
                        if (!$result['success']) {
                            $upload_errors[] = $result['message'] . " (" . $file['name'] . ")";
                        } else {
                            $uploaded_files++;
                        }
                    }
                    
                    if ($uploaded_files > 0) {
                        $_SESSION['success'] = "Hostel updated with $uploaded_files new images" . 
                                            (count($upload_errors) > 0 ? " (some uploads failed)" : "");
                    }
                }
                
                $_SESSION['success'] = "Hostel updated successfully!" . (isset($_SESSION['success']) ? " " . $_SESSION['success'] : "");
                header("Location: listings.php");
                exit();
            } else {
                $error = "Error updating hostel: " . $conn->error;
            }
        }
    } elseif ($action === 'delete_image' && $hostel_id) {
        $image_id = (int)$_POST['image_id'];
        
        // Verify the image belongs to the landlord's hostel
        $verify_query = "SELECT hi.image_id 
                    FROM hostel_images hi
                JOIN hostels h ON hi.hostel_id = h.hostel_id
                WHERE hi.image_id = ? AND h.landlord_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $image_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Get image path to delete file
            $image_query = "SELECT image_path FROM hostel_images WHERE image_id = ?";
            $image_stmt = $conn->prepare($image_query);
            $image_stmt->bind_param("i", $image_id);
            $image_stmt->execute();
            $image = $image_stmt->get_result()->fetch_assoc();
            
            // Delete from database
            $delete_query = "DELETE FROM hostel_images WHERE image_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $image_id);
            
            if ($delete_stmt->execute()) {
                // Delete file
                $file_path = __DIR__ . "/uploads/hostel_images/" . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $_SESSION['success'] = "Image deleted successfully!";
                header("Location: listings.php?action=edit&id=$hostel_id");
                exit();
            } else {
                $error = "Error deleting image: " . $conn->error;
            }
        } else {
            $error = "Image not found or you don't have permission to delete it.";
        }
    } elseif ($action === 'set_primary_image' && $hostel_id) {
        $image_id = (int)$_POST['image_id'];
        
        // Verify the image belongs to the landlord's hostel
        $verify_query = "SELECT hi.image_id 
            FROM hostel_images hi
                        JOIN hostels h ON hi.hostel_id = h.hostel_id
                    WHERE hi.image_id = ? AND h.landlord_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $image_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Reset all primary images for this hostel
            $reset_query = "UPDATE hostel_images SET is_primary = FALSE WHERE hostel_id = ?";
            $reset_stmt = $conn->prepare($reset_query);
            $reset_stmt->bind_param("i", $hostel_id);
            $reset_stmt->execute();
            
            // Set new primary image
            $set_query = "UPDATE hostel_images SET is_primary = TRUE WHERE image_id = ?";
            $set_stmt = $conn->prepare($set_query);
            $set_stmt->bind_param("i", $image_id);
            
            if ($set_stmt->execute()) {
                $_SESSION['success'] = "Primary image set successfully!";
                header("Location: listings.php?action=edit&id=$hostel_id");
                exit();
            } else {
                $error = "Error setting primary image: " . $conn->error;
            }
        } else {
            $error = "Image not found or you don't have permission to modify it.";
        }
    }
}

// Get hostel data for editing
$hostel = null;
$images = [];
$amenities_list = ['WiFi', 'Water', 'Electricity', 'Security', 'Laundry', 'Cafeteria', 'Parking'];

if ($action === 'edit' && $hostel_id) {
    $query = "SELECT * FROM hostels WHERE hostel_id = ? AND landlord_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $hostel_id, $_SESSION['user_id']);
    $stmt->execute();
    $hostel = $stmt->get_result()->fetch_assoc();
    
    if (!$hostel) {
        $_SESSION['error'] = "Hostel not found or you don't have permission to edit it.";
        header("Location: listings.php");
        exit();
    }
    
    $images = get_hostel_images($hostel_id);
}

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<?php if ($action === 'list'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Hostel Listings</h2>
        <a href="listings.php?action=create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Hostel
        </a>
    </div>
    
    <?php if (empty($hostels)): ?>
        <div class="alert alert-info">
            You haven't listed any hostels yet. <a href="listings.php?action=create">Add your first hostel</a> to get started.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Hostel Name</th>
                        <th>University</th>
                        <th>Price</th>
                        <th>Rooms</th>
                        <th>Distance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hostels as $h): 
                        $amenities = json_decode($h['amenities'], true) ?? [];
                        $primary_image = get_hostel_images($h['hostel_id']);
                        $primary_image_path = !empty($primary_image) ? '/uploads/hostel_images/' . $primary_image[0]['image_path'] : 'assets/images/hostel-placeholder.jpg';
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $primary_image_path; ?>" class="rounded me-3" width="60" height="60" alt="<?php echo htmlspecialchars($h['name']); ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($h['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($h['created_at'])); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($h['university_name']); ?></td>
                            <td class="text-success">UGX <?php echo number_format($h['price_per_month']); ?></td>
                            <td><?php echo $h['rooms_available']; ?></td>
                            <td><?php echo round($h['distance_to_university'], 1); ?> km</td>
                            <td>
                                <span class="badge bg-success">Active</span>
                            </td>
                            <td>
                                <a href="hostel-details.php?id=<?php echo $h['hostel_id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="listings.php?action=edit&id=<?php echo $h['hostel_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="listings.php?action=delete&id=<?php echo $h['hostel_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this hostel?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $action === 'create' ? 'Add New Hostel' : 'Edit Hostel'; ?></h2>
        <a href="listings.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Listings
        </a>
    </div>
    
    <form method="POST" action="listings.php?action=<?php echo $action; ?><?php echo $hostel_id ? '&id='.$hostel_id : ''; ?>" enctype="multipart/form-data">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="original_university_id" value="<?php echo $hostel['university_id']; ?>">
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Hostel Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                            value="<?php echo htmlspecialchars($hostel['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                                echo htmlspecialchars($hostel['description'] ?? ''); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="university_id" class="form-label">Nearby University</label>
                            <select class="form-select" id="university_id" name="university_id" required>
                                <option value="">Select University</option>
                                <?php foreach (get_universities() as $university): ?>
                                    <option value="<?php echo $university['university_id']; ?>" 
                                        <?php echo (isset($hostel['university_id']) && $hostel['university_id'] == $university['university_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($university['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price per Month (UGX)</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="1000" 
                            value="<?php echo htmlspecialchars($hostel['price_per_month'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="rooms" class="form-label">Rooms Available</label>
                            <input type="number" class="form-control" id="rooms" name="rooms" min="1" 
                                value="<?php echo htmlspecialchars($hostel['rooms_available'] ?? '1'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Location Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="address" class="form-label">Full Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?php 
                                echo htmlspecialchars($hostel['address'] ?? ''); 
                            ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude" 
                            value="<?php echo htmlspecialchars($hostel['latitude'] ?? '0.3136'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude" 
                                    value="<?php echo htmlspecialchars($hostel['longitude'] ?? '32.5811'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <p class="mb-0">Use <a href="https://www.google.com/maps" target="_blank">Google Maps</a> to find the exact coordinates of your hostel location.</p>
                        </div>
                        
                        <div id="locationMap" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Rules & Regulations</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="rules" class="form-label">Hostel Rules</label>
                            <textarea class="form-control" id="rules" name="rules" rows="4"><?php 
                                echo htmlspecialchars($hostel['rules'] ?? ''); 
                            ?></textarea>
                            <small class="text-muted">List any rules or regulations for students staying at your hostel.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Amenities</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $current_amenities = isset($hostel['amenities']) ? json_decode($hostel['amenities'], true) : [];
                        ?>
                        <?php foreach ($amenities_list as $amenity): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="amenities[]" 
                                    id="amenity_<?php echo strtolower($amenity); ?>" value="<?php echo $amenity; ?>"
                                    <?php echo in_array($amenity, $current_amenities) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="amenity_<?php echo strtolower($amenity); ?>">
                                    <?php echo $amenity; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Hostel Images</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($action === 'edit' && !empty($images)): ?>
                            <div class="mb-3">
                                <h6>Current Images</h6>
                                <div class="row g-2">
                                    <?php foreach ($images as $image): ?>
                                        <div class="col-6">
                                            <div class="position-relative">
                                                <img src="/uploads/hostel_images/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                    class="img-thumbnail w-100 mb-2" style="height: 100px; object-fit: cover;">
                                                <div class="d-flex justify-content-between">
                                                    <?php if ($image['is_primary']): ?>
                                                        <span class="badge bg-success">Primary</span>
                                                    <?php else: ?>
                                                        <form method="POST" action="listings.php?action=set_primary_image&id=<?php echo $hostel_id; ?>" class="d-inline">
                                                            <input type="hidden" name="image_id" value="<?php echo $image['image_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">Set Primary</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" action="listings.php?action=delete_image&id=<?php echo $hostel_id; ?>" class="d-inline">
                                                        <input type="hidden" name="image_id" value="<?php echo $image['image_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this image?');">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="images" class="form-label">Upload New Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                            <small class="text-muted">Upload multiple images (max 5MB each). First image will be set as primary.</small>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?php echo $action === 'create' ? 'Create Hostel' : 'Update Hostel'; ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    <script>
    // Initialize map for location selection
    function initMap() {
        const defaultLocation = { 
            lat: <?php echo isset($hostel['latitude']) ? $hostel['latitude'] : '0.3136'; ?>, 
            lng: <?php echo isset($hostel['longitude']) ? $hostel['longitude'] : '32.5811'; ?> 
        };
        
        const map = new google.maps.Map(document.getElementById("locationMap"), {
            zoom: 15,
            center: defaultLocation,
        });
        
        const marker = new google.maps.Marker({
            position: defaultLocation,
            map: map,
            draggable: true,
        });
        
        // Update form fields when marker is dragged
        google.maps.event.addListener(marker, 'dragend', function() {
            document.getElementById('latitude').value = marker.getPosition().lat();
            document.getElementById('longitude').value = marker.getPosition().lng();
            
            // Reverse geocode to get address
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: marker.getPosition() }, (results, status) => {
                if (status === "OK" && results[0]) {
                    document.getElementById('address').value = results[0].formatted_address;
                }
            });
        });
        
        // Search box for locations
        const input = document.createElement("input");
        input.placeholder = "Search for location";
        input.classList.add("form-control", "mb-2");
        
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
        
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });
        
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();
            
            if (places.length === 0) {
                return;
            }
            
            // Clear existing markers
            marker.setMap(null);
            
            // For each place, get the location and create a marker
            const bounds = new google.maps.LatLngBounds();
            
            places.forEach((place) => {
                if (!place.geometry) {
                    return;
                }
                
                marker.setPosition(place.geometry.location);
                marker.setMap(map);
                
                // Update form fields
                document.getElementById('latitude').value = place.geometry.location.lat();
                document.getElementById('longitude').value = place.geometry.location.lng();
                document.getElementById('address').value = place.formatted_address;
                
                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });
            
            map.fitBounds(bounds);
        });
    }
    
    window.initMap = initMap;
    </script>
    
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places&callback=initMap" async defer></script>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>