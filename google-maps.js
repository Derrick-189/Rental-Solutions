// Initialize Google Maps for hostel locations
function initHostelMap() {
    // Check if map container exists
    const mapContainer = document.getElementById('hostel-map');
    if (!mapContainer) return;
    
    // Get coordinates from data attributes
    const hostelLat = parseFloat(mapContainer.dataset.lat);
    const hostelLng = parseFloat(mapContainer.dataset.lng);
    const uniLat = parseFloat(mapContainer.dataset.uniLat);
    const uniLng = parseFloat(mapContainer.dataset.uniLng);
    
    // Create map centered between hostel and university
    const centerLat = (hostelLat + uniLat) / 2;
    const centerLng = (hostelLng + uniLng) / 2;
    
    const map = new google.maps.Map(mapContainer, {
        zoom: 14,
        center: { lat: centerLat, lng: centerLng },
        mapTypeId: 'roadmap'
    });
    
    // Add markers
    new google.maps.Marker({
        position: { lat: hostelLat, lng: hostelLng },
        map: map,
        title: 'Hostel Location',
        icon: {
            url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
        }
    });
    
    new google.maps.Marker({
        position: { lat: uniLat, lng: uniLng },
        map: map,
        title: 'University',
        icon: {
            url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        }
    });
    
    // Add directions line
    const directionsLine = new google.maps.Polyline({
        path: [
            { lat: hostelLat, lng: hostelLng },
            { lat: uniLat, lng: uniLng }
        ],
        geodesic: true,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 2,
        map: map
    });
}

// Initialize Google Maps for search page
function initSearchMap(hostels) {
    const mapContainer = document.getElementById('search-map');
    if (!mapContainer || !hostels.length) return;
    
    // Get first hostel's university location as center
    const firstHostel = hostels[0];
    const map = new google.maps.Map(mapContainer, {
        zoom: 14,
        center: { lat: firstHostel.latitude, lng: firstHostel.longitude },
        mapTypeId: 'roadmap'
    });
    
    // Add markers for all hostels
    hostels.forEach(hostel => {
        new google.maps.Marker({
            position: { lat: hostel.latitude, lng: hostel.longitude },
            map: map,
            title: hostel.name,
            icon: {
                url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
            }
        });
    });
}

// Load Google Maps API
function loadGoogleMaps() {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap`;
    script.defer = true;
    script.async = true;
    document.head.appendChild(script);
}

// Call loadGoogleMaps when needed
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('hostel-map') || document.getElementById('search-map')) {
        loadGoogleMaps();
    }
});