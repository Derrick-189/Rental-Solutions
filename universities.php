<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only admins can access this page
check_user_role(['admin']);
$user = get_logged_in_user();

$page_title = "Manage Universities";
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;
$action = $_GET['action'] ?? 'list';
$university_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($conn, $_POST['name'] ?? '');
    $location = sanitize_input($conn, $_POST['location'] ?? '');
    $latitude = (float)($_POST['latitude'] ?? 0);
    $longitude = (float)($_POST['longitude'] ?? 0);
    $description = sanitize_input($conn, $_POST['description'] ?? '');
    
    // Validate inputs
    if (empty($name)) $errors[] = "University name is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($latitude) || empty($longitude)) $errors[] = "Valid coordinates are required";
    
    if ($action === 'create') {
        if (empty($errors)) {
            $query = "INSERT INTO universities (name, location, latitude, longitude, description) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdds", $name, $location, $latitude, $longitude, $description);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "University added successfully!";
                header("Location: universities.php");
                exit();
            } else {
                $errors[] = "Error adding university: " . $conn->error;
            }
        }
    } elseif ($action === 'edit' && $university_id) {
        if (empty($errors)) {
            $query = "UPDATE universities 
                      SET name = ?, location = ?, latitude = ?, longitude = ?, description = ?
                      WHERE university_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssddsi", $name, $location, $latitude, $longitude, $description, $university_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "University updated successfully!";
                header("Location: universities.php");
                exit();
            } else {
                $errors[] = "Error updating university: " . $conn->error;
            }
        }
    }
} elseif ($action === 'delete' && $university_id) {
    // Check if any hostels are associated with this university
    $check_query = "SELECT COUNT(*) AS hostel_count FROM hostels WHERE university_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $university_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['hostel_count'] > 0) {
        $errors[] = "Cannot delete university - there are hostels associated with it.";
    } else {
        $delete_query = "DELETE FROM universities WHERE university_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $university_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "University deleted successfully!";
            header("Location: universities.php");
            exit();
        } else {
            $errors[] = "Error deleting university: " . $conn->error;
        }
    }
}

// Get university for editing
$university = null;
if ($action === 'edit' && $university_id) {
    $query = "SELECT * FROM universities WHERE university_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $university_id);
    $stmt->execute();
    $university = $stmt->get_result()->fetch_assoc();
    
    if (!$university) {
        $_SESSION['error'] = "University not found";
        header("Location: universities.php");
        exit();
    }
}

// Display messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

if (!empty($errors)) {
    echo '<div class="alert alert-danger"><ul class="mb-0">';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul></div>';
}
?>

<?php if ($action === 'list'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Universities</h2>
        <a href="universities.php?action=create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add University
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>University Name</th>
                            <th>Location</th>
                            <th>Coordinates</th>
                            <th>Hostels</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT u.*, COUNT(h.hostel_id) AS hostel_count 
                                  FROM universities u
                                  LEFT JOIN hostels h ON u.university_id = h.university_id
                                  GROUP BY u.university_id
                                  ORDER BY u.name";
                        $universities = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
                        
                        foreach ($universities as $uni): ?>
                            <tr>
                                <td><?php echo $uni['university_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($uni['name']); ?></strong>
                                    <?php if (!empty($uni['description'])): ?>
                                        <p class="text-muted mb-0 small"><?php echo htmlspecialchars($uni['description']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($uni['location']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo round($uni['latitude'], 4); ?>, <?php echo round($uni['longitude'], 4); ?>
                                    </small>
                                </td>
                                <td><?php echo $uni['hostel_count']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="universities.php?action=edit&id=<?php echo $uni['university_id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="universities.php?action=delete&id=<?php echo $uni['university_id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this university?');"
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?php echo $action === 'create' ? 'Add New University' : 'Edit University'; ?></h2>
        <a href="universities.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">University Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">University Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($university['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location (Address)</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($university['location'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                echo htmlspecialchars($university['description'] ?? ''); 
                            ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" step="0.000001" class="form-control" id="latitude" name="latitude" 
                                       value="<?php echo htmlspecialchars($university['latitude'] ?? '0.3136'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" step="0.000001" class="form-control" id="longitude" name="longitude" 
                                       value="<?php echo htmlspecialchars($university['longitude'] ?? '32.5811'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <p class="mb-0">Use <a href="https://www.google.com/maps" target="_blank">Google Maps</a> to find the exact coordinates of the university.</p>
                        </div>
                        
                        <div id="universityMap" style="height: 300px; width: 100%;"></div>
                        
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <?php echo $action === 'create' ? 'Add University' : 'Update University'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Initialize map for university location
    function initMap() {
        const defaultLocation = { 
            lat: <?php echo isset($university['latitude']) ? $university['latitude'] : '0.3136'; ?>, 
            lng: <?php echo isset($university['longitude']) ? $university['longitude'] : '32.5811'; ?> 
        };
        
        const map = new google.maps.Map(document.getElementById("universityMap"), {
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
                    document.getElementById('location').value = results[0].formatted_address;
                }
            });
        });
        
        // Search box for locations
        const input = document.createElement("input");
        input.placeholder = "Search for university location";
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
                document.getElementById('location').value = place.formatted_address;
                
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