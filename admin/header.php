<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DriveEasy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <nav class="admin-nav">
            <a href="index.php" class="admin-logo">
                <i class="fas fa-car"></i> DriveEasy <span style="font-size: 0.8rem; color: var(--secondary); font-weight: 400;">Admin</span>
            </a>
            <ul class="admin-nav-links">
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage-cars.php" class="<?= strpos($_SERVER['PHP_SELF'], 'manage-cars') !== false || strpos($_SERVER['PHP_SELF'], 'add-car') !== false || strpos($_SERVER['PHP_SELF'], 'edit-car') !== false ? 'active' : '' ?>"><i class="fas fa-car"></i> Cars</a></li>
                <li><a href="manage-customers.php" class="<?= strpos($_SERVER['PHP_SELF'], 'customers') !== false ? 'active' : '' ?>"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="manage-bookings.php" class="<?= strpos($_SERVER['PHP_SELF'], 'bookings') !== false ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="payments.php" class="<?= strpos($_SERVER['PHP_SELF'], 'payments') !== false ? 'active' : '' ?>"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>
    <main class="admin-container">
