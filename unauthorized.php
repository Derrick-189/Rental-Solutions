<?php
require_once __DIR__ . '/header.php';
$page_title = "Unauthorized Access";
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow text-center">
            <div class="card-header bg-danger text-white">
                <h3 class="mb-0">Unauthorized Access</h3>
            </div>
            <div class="card-body">
                <i class="bi bi-shield-lock fs-1 text-danger mb-3"></i>
                <h4>You don't have permission to access this page</h4>
                <p class="text-muted">
                    Please contact the administrator if you believe this is an error.
                </p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-house-door"></i> Return to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>