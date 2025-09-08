<?php
require_once __DIR__ . '/db.php';

// Calculate distance between two coordinates (in km)
function calculate_distance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return round($miles * 1.609344, 2); // Convert to km
}

// Get all universities
function get_universities() {
    global $conn;
    $query = "SELECT * FROM universities ORDER BY name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get hostel by ID
function get_hostel($hostel_id) {
    global $conn;
    $query = "SELECT h.*, u.name AS university_name, u.latitude AS uni_lat, u.longitude AS uni_lon 
              FROM hostels h 
              JOIN universities u ON h.university_id = u.university_id 
              WHERE h.hostel_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get hostel images
function get_hostel_images($hostel_id) {
    global $conn;
    $query = "SELECT * FROM hostel_images WHERE hostel_id = ? ORDER BY is_primary DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Search hostels with filters
function search_hostels($university_id = null, $min_price = null, $max_price = null, $amenities = []) {
    global $conn;
    
    $query = "SELECT h.*, u.name AS university_name 
              FROM hostels h 
              JOIN universities u ON h.university_id = u.university_id 
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($university_id) {
        $query .= " AND h.university_id = ?";
        $params[] = $university_id;
        $types .= "i";
    }
    
    if ($min_price) {
        $query .= " AND h.price_per_month >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price) {
        $query .= " AND h.price_per_month <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    // Basic amenities filter (simplified)
    if (!empty($amenities)) {
        foreach ($amenities as $amenity) {
            $query .= " AND h.amenities LIKE ?";
            $params[] = "%$amenity%";
            $types .= "s";
        }
    }
    
    $query .= " ORDER BY h.distance_to_university ASC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle file upload
function upload_hostel_image($file, $hostel_id, $is_primary = false) {
    global $conn;
    
    $target_dir = __DIR__ . "/../uploads/hostel_images/";
    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = "hostel_" . $hostel_id . "_" . uniqid() . "." . $file_ext;
    $target_file = $target_dir . $filename;
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }
    
    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large."];
    }
    
    // Allow certain file formats
    $allowed_ext = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($file_ext, $allowed_ext)) {
        return ["success" => false, "message" => "Only JPG, JPEG, PNG & GIF files are allowed."];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Save to database
        $query = "INSERT INTO hostel_images (hostel_id, image_path, is_primary) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isi", $hostel_id, $filename, $is_primary);
        $stmt->execute();
        
        return ["success" => true, "filename" => $filename];
    } else {
        return ["success" => false, "message" => "Error uploading file."];
    }
}

// Sanitize user input
function sanitize_input($conn, $data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Check user role and redirect if not authorized
function check_user_role_access($allowed_roles) {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
        header("Location: /login.php");
        exit();
    }
}

// Delete hostel and its associated images
function delete_hostel($hostel_id) {
    global $conn;
    
    // First delete all images associated with the hostel
    $images = get_hostel_images($hostel_id);
    foreach ($images as $image) {
        $file_path = __DIR__ . "/../uploads/hostel_images/" . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete from database
    $query = "DELETE FROM hostels WHERE hostel_id = ? AND landlord_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $hostel_id, $_SESSION['user_id']);
    return $stmt->execute();
}

/**
 * Converts a timestamp to a "time ago" format (e.g., "2 hours ago")
 * @param string $timestamp The timestamp to convert
 * @return string Formatted time difference
 */
function time_ago($timestamp) {
    $time_diff = time() - strtotime($timestamp);
    
    if ($time_diff < 60) {
        return "just now";
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return "$minutes minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return "$hours hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 2592000) {
        $days = floor($time_diff / 86400);
        return "$days day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", strtotime($timestamp));
    }
}
?>