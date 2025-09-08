// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Enable Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Enable Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Mobile money payment method toggle
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const isMobileMoney = this.value.includes('mobile_money');
            document.getElementById('mobile-money-fields').style.display = isMobileMoney ? 'block' : 'none';
            document.getElementById('card-fields').style.display = this.value === 'credit_card' ? 'block' : 'none';
        });
    });
    
    // Set minimum end date based on start date
    const startDateInput = document.getElementById('start_date');
    if (startDateInput) {
        startDateInput.addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            endDate.min = this.value;
            
            // Set default end date to 1 month from start date
            const startDate = new Date(this.value);
            startDate.setMonth(startDate.getMonth() + 1);
            const endDateStr = startDate.toISOString().split('T')[0];
            endDate.value = endDateStr;
        });
    }
    
    // User type toggle for registration form
    const userTypeSelect = document.getElementById('user_type');
    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function() {
            const universityField = document.getElementById('university-field');
            if (this.value === 'student') {
                universityField.style.display = 'block';
                document.getElementById('university').required = true;
            } else {
                universityField.style.display = 'none';
                document.getElementById('university').required = false;
            }
        });
    }

    // Initialize profile picture upload if on profile page
    if (document.getElementById('profile_pic')) {
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Client-side validation
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Only JPG, PNG, or GIF images are allowed');
                this.value = '';
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB');
                this.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePicPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
            
            // Upload via AJAX
            const formData = new FormData(document.getElementById('profilePicForm'));
            formData.append('ajax_request', true);
            
            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update image with cache-busting timestamp
                    const preview = document.getElementById('profilePicPreview');
                    preview.src = data.newSrc;
                    
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        Profile picture updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.card-body').prepend(alertDiv);
                    
                    // Auto-dismiss after 3 seconds
                    setTimeout(() => {
                        alertDiv.classList.remove('show');
                        setTimeout(() => alertDiv.remove(), 150);
                    }, 3000);
                } else {
                    alert(data.message || 'Failed to update profile picture');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while uploading');
            });
        });
    }
});

// Time ago function for messages
function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return interval + ' ' + unit + (interval === 1 ? '' : 's') + ' ago';
        }
    }
    
    return 'Just now';
}

// Apply time ago to all elements with data-time attribute
document.querySelectorAll('[data-time]').forEach(element => {
    const timestamp = element.getAttribute('data-time');
    element.textContent = timeAgo(timestamp);
});

// Google Maps integration
function initMap() {
    // This function would be implemented in google-maps.js
    console.log('Google Maps initialized');
}