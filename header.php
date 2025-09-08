<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crib Hunt - <?php echo $page_title ?? 'home'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .hero-section {
            background-image: url('/assets/images/hostel-bg.png');
            background-size: cover;
            background-position: center;
            height: 500px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .restricted-link {
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
               <img src="./assets/images/logo.png" alt="Crib Hunt" height="60">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">Find Hostels</a>
                    </li>
                    <li class="nav-item">
    <?php if (isset($_SESSION['user_type'])) : ?>
        <?php if ($_SESSION['user_type'] === 'student') : ?>
            <a class="nav-link" href="search.php">Universities</a>
        <?php elseif ($_SESSION['user_type'] === 'admin') : ?>
            <a class="nav-link" href="universities.php">Universities</a>
        <?php else : ?>
            <a class="nav-link restricted-link" 
               data-bs-toggle="tooltip" 
               data-bs-placement="bottom" 
               title="Universities management is only available to administrators">
                Universities
            </a>
        <?php endif; ?>
    <?php else : ?>
        <a class="nav-link" href="universities.php">Universities</a>
    <?php endif; ?>
</li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                            <li class="nav-item"><a class="nav-link" href="dashboard.php">Student Dashboard</a></li>
                        <?php elseif ($_SESSION['user_type'] === 'landlord'): ?>
                            <li class="nav-item"><a class="nav-link" href="l_dashboard.php">Landlord Dashboard</a></li>
                        <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4">

    <!-- Bootstrap tooltip initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>