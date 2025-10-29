</main>
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5>Rental Solutions</h5>
                <p>Connecting students with affordable and convenient hostel accommodations near their universities.</p>
            </div>
            <div class="col-md-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white">Home</a></li>
                    <li><a href="search.php" class="text-white">Find Hostels</a></li>
                    <li>
    <?php if (isset($_SESSION['user_type'])) : ?>
        <?php if ($_SESSION['user_type'] === 'student') : ?>
            <a href="search.php" class="text-white">Universities</a>
        <?php elseif ($_SESSION['user_type'] === 'admin') : ?>
            <a href="universities.php" class="text-white">Universities</a>
        <?php else : ?>
            <span class="text-muted" data-bs-toggle="tooltip" 
                  title="Universities management is only available to administrators">
                Universities
            </span>
        <?php endif; ?>
    <?php else : ?>
        <a href="universities.php" class="text-white">Universities</a>
    <?php endif; ?>
    </li>
                   
                    
                    <li><a href="contact.php" class="text-white">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact</h5>
                <p><i class="bi bi-envelope"></i> info@rentalsolutions.com<br>
                <i class="bi bi-telephone"></i> +256 761 891599<br>
                <i class="bi bi-whatsapp"></i> +256 706 507291</p>
        </div>
        <div class="text-center mt-3">
            <p>&copy; <?php echo date('Y'); ?> Rental Solutions. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/script.js"></script>

<?php if (isset($include_maps)) : ?>
    <script src="/assets/js/google-maps.js"></script>
<?php endif; ?>
<script>
    // Initialize tooltips for footer
    document.addEventListener('DOMContentLoaded', function() {
        var footerTooltips = [].slice.call(document.querySelectorAll('footer [data-bs-toggle="tooltip"]'));
        footerTooltips.map(function(tooltipEl) {
            return new bootstrap.Tooltip(tooltipEl);
        });
    });
</script>
</body>
</html>
<?php
// Flush any output buffering started in auth.php
if (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_end_flush();
}