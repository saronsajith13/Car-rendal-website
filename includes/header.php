<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveEasy - Car Rental Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-car"></i> DriveEasy
                </a>
                <div class="nav-toggle" id="navToggle">
                    <i class="fas fa-bars"></i>
                </div>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="cars.php" class="nav-link">Cars</a></li>
                    <li><a href="contact.php" class="nav-link">Contact</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php" class="nav-link"><i class="fas fa-user"></i> Dashboard</a></li>
                        <li><a href="logout.php" class="nav-link btn-logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="nav-link">Login</a></li>
                        <li><a href="register.php" class="nav-link btn-register">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>
