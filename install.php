<?php
/**
 * DriveEasy - Web Installer
 * Run this script once from browser to set up the database.
 * Delete or secure this file after installation.
 */

// Detect if site is already installed
$config_installed = false;
if (file_exists(__DIR__ . '/includes/config.php')) {
    try {
        require_once __DIR__ . '/includes/config.php';
        $pdo->query("SELECT 1 FROM admins LIMIT 1");
        $config_installed = true;
    } catch (Exception $e) {
        // config exists but DB not set up yet
    }
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? 'driveeasy');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';

    // Test connection (without database first)
    try {
        // Connect without DB to create it
        $temp_pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $temp_pdo->exec("USE `$db_name`");

        // Drop existing tables to allow re-install
        $temp_pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $tables = ['reviews', 'contacts', 'payments', 'bookings', 'cars', 'users', 'admins'];
        foreach ($tables as $t) {
            $temp_pdo->exec("DROP TABLE IF EXISTS `$t`");
        }
        $temp_pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Create tables
        $temp_pdo->exec("
            CREATE TABLE users (
                user_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $temp_pdo->exec("
            CREATE TABLE cars (
                car_id INT AUTO_INCREMENT PRIMARY KEY,
                car_name VARCHAR(100) NOT NULL,
                brand VARCHAR(50) NOT NULL,
                model VARCHAR(50) NOT NULL,
                year INT NOT NULL,
                price_per_day DECIMAL(10,2) NOT NULL,
                fuel_type VARCHAR(20) NOT NULL,
                seats INT NOT NULL,
                transmission VARCHAR(20) NOT NULL,
                image VARCHAR(255) DEFAULT 'default-car.jpg',
                availability TINYINT(1) DEFAULT 1,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $temp_pdo->exec("
            CREATE TABLE bookings (
                booking_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                car_id INT NOT NULL,
                pickup_date DATE NOT NULL,
                return_date DATE NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
                pickup_location VARCHAR(255) DEFAULT 'Main Office',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE
            )
        ");

        $temp_pdo->exec("
            CREATE TABLE payments (
                payment_id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                transaction_id VARCHAR(100),
                payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
            )
        ");

        $temp_pdo->exec("
            CREATE TABLE admins (
                admin_id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $temp_pdo->exec("
            CREATE TABLE reviews (
                review_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                car_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE
            )
        ");

        $temp_pdo->exec("
            CREATE TABLE contacts (
                contact_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(200),
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Insert admin
        $admin_pass = password_hash('password', PASSWORD_DEFAULT);
        $admin_email = trim($_POST['admin_email'] ?? 'admin@driveeasy.com');
        $temp_pdo->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)")
            ->execute(['admin', $admin_pass, $admin_email]);

        // Insert sample cars
        $sample_cars = [
            ['Toyota Camry', 'Toyota', 'Camry', 2024, 50.00, 'Petrol', 5, 'Automatic', 'camry.jpg', 1, 'A comfortable and reliable sedan perfect for business trips and family outings.'],
            ['Honda Civic', 'Honda', 'Civic', 2024, 45.00, 'Petrol', 5, 'Automatic', 'civic.jpg', 1, 'Sporty and fuel-efficient compact car with modern features.'],
            ['BMW X5', 'BMW', 'X5', 2024, 120.00, 'Diesel', 5, 'Automatic', 'bmwx5.jpg', 1, 'Luxury SUV with premium comfort and powerful performance.'],
            ['Mercedes C-Class', 'Mercedes', 'C-Class', 2023, 100.00, 'Petrol', 5, 'Automatic', 'mercedes.jpg', 1, 'Elegant luxury sedan with cutting-edge technology.'],
            ['Ford Mustang', 'Ford', 'Mustang', 2024, 150.00, 'Petrol', 4, 'Manual', 'mustang.jpg', 1, 'Iconic American muscle car with thrilling performance.'],
            ['Toyota RAV4', 'Toyota', 'RAV4', 2024, 65.00, 'Hybrid', 5, 'Automatic', 'rav4.jpg', 1, 'Popular compact SUV with excellent fuel economy.'],
            ['Audi A4', 'Audi', 'A4', 2024, 90.00, 'Petrol', 5, 'Automatic', 'audia4.jpg', 1, 'German engineering at its finest - luxury and performance combined.'],
            ['Nissan Altima', 'Nissan', 'Altima', 2023, 40.00, 'Petrol', 5, 'CVT', 'altima.jpg', 1, 'Affordable and reliable mid-size sedan for daily commutes.'],
            ['Range Rover Sport', 'Land Rover', 'Range Rover Sport', 2024, 200.00, 'Diesel', 5, 'Automatic', 'rangerover.jpg', 1, 'Ultimate luxury SUV with off-road capability and prestige.'],
            ['Hyundai Tucson', 'Hyundai', 'Tucson', 2024, 55.00, 'Diesel', 5, 'Automatic', 'tucson.jpg', 1, 'Stylish and feature-packed compact SUV.'],
            ['Tesla Model 3', 'Tesla', 'Model 3', 2024, 130.00, 'Electric', 5, 'Automatic', 'tesla3.jpg', 1, 'Premium electric sedan with autopilot and zero emissions.'],
            ['Chevrolet Suburban', 'Chevrolet', 'Suburban', 2023, 180.00, 'Petrol', 8, 'Automatic', 'suburban.jpg', 1, 'Full-size SUV perfect for large groups and road trips.']
        ];

        $stmt = $temp_pdo->prepare("INSERT INTO cars (car_name, brand, model, year, price_per_day, fuel_type, seats, transmission, image, availability, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sample_cars as $car) {
            $stmt->execute($car);
        }

        // Write config file
        $config_content = "<?php\n";
        $config_content .= "session_start();\n\n";
        $config_content .= "\$host = '" . addslashes($db_host) . "';\n";
        $config_content .= "\$dbname = '" . addslashes($db_name) . "';\n";
        $config_content .= "\$username = '" . addslashes($db_user) . "';\n";
        $config_content .= "\$password = '" . addslashes($db_pass) . "';\n\n";
        $config_content .= "try {\n";
        $config_content .= "    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname\", \$username, \$password);\n";
        $config_content .= "    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
        $config_content .= "} catch(PDOException \$e) {\n";
        $config_content .= "    die(\"Connection failed: \" . \$e->getMessage());\n";
        $config_content .= "}\n\n";
        $config_content .= "function isLoggedIn() {\n";
        $config_content .= "    return isset(\$_SESSION['user_id']);\n";
        $config_content .= "}\n\n";
        $config_content .= "function isAdmin() {\n";
        $config_content .= "    return isset(\$_SESSION['admin_id']);\n";
        $config_content .= "}\n\n";
        $config_content .= "function redirect(\$url) {\n";
        $config_content .= "    header(\"Location: \$url\");\n";
        $config_content .= "    exit();\n";
        $config_content .= "}\n\n";
        $config_content .= "function getTotalDays(\$start, \$end) {\n";
        $config_content .= "    \$start = new DateTime(\$start);\n";
        $config_content .= "    \$end = new DateTime(\$end);\n";
        $config_content .= "    \$interval = \$start->diff(\$end);\n";
        $config_content .= "    return max(1, \$interval->days);\n";
        $config_content .= "}\n";

        $written = file_put_contents(__DIR__ . '/includes/config.php', $config_content);

        if ($written === false) {
            $error = 'Could not write config file. Check directory permissions.';
        } else {
            $success = 'Installation complete!';
            $step = 3;
        }

    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - DriveEasy Car Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-container { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); width: 100%; max-width: 560px; overflow: hidden; }
        .install-header { background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 35px 30px; text-align: center; color: #fff; }
        .install-header i { font-size: 3rem; color: #e94560; margin-bottom: 10px; }
        .install-header h1 { font-size: 1.8rem; }
        .install-header p { opacity: 0.7; font-size: 0.95rem; margin-top: 5px; }
        .install-body { padding: 30px; }
        .step-indicator { display: flex; gap: 8px; margin-bottom: 30px; justify-content: center; }
        .step-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; background: #e0e0e0; color: #888; }
        .step-dot.active { background: #e94560; color: #fff; }
        .step-dot.done { background: #28a745; color: #fff; }
        .step-line { width: 40px; height: 2px; background: #e0e0e0; align-self: center; }
        .step-line.done { background: #28a745; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; color: #333; }
        .form-group input { width: 100%; padding: 11px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; transition: 0.3s; }
        .form-group input:focus { border-color: #e94560; outline: none; }
        .form-group .hint { font-size: 0.8rem; color: #888; margin-top: 3px; }
        .btn-install { width: 100%; padding: 14px; background: #e94560; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 5px; }
        .btn-install:hover { background: #d63851; transform: translateY(-1px); box-shadow: 0 5px 20px rgba(233,69,96,0.3); }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.9rem; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .success-box { text-align: center; padding: 20px 0; }
        .success-box i { font-size: 4rem; color: #28a745; margin-bottom: 15px; }
        .success-box h2 { color: #1a1a2e; margin-bottom: 8px; }
        .success-box p { color: #666; margin-bottom: 20px; }
        .btn-group { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn-group a { padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-group .btn-primary { background: #e94560; color: #fff; }
        .btn-group .btn-primary:hover { background: #d63851; }
        .btn-group .btn-secondary { background: #1a1a2e; color: #fff; }
        .btn-group .btn-secondary:hover { background: #16213e; }
        .btn-group .btn-outline { background: transparent; border: 2px solid #1a1a2e; color: #1a1a2e; }
        .creds { background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: left; margin: 15px 0; font-size: 0.9rem; }
        .creds strong { color: #1a1a2e; }
        .creds code { background: #e9ecef; padding: 2px 8px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="install-container">
    <div class="install-header">
        <i class="fas fa-car"></i>
        <h1>DriveEasy Installation</h1>
        <p>Set up your car rental management system</p>
    </div>

    <div class="install-body">
        <?php if ($config_installed && empty($success)): ?>
            <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border-color: #bee5eb;">
                <i class="fas fa-info-circle"></i> System appears to be already installed.
            </div>
            <div class="btn-group">
                <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> Go to Homepage</a>
                <a href="admin/login.php" class="btn btn-secondary"><i class="fas fa-lock"></i> Admin Panel</a>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="?reset=1" style="color: #e94560; font-size: 0.85rem;" onclick="return confirm('Re-install will erase all data. Continue?')">Re-install</a>
            </p>
        <?php elseif ($step == 3 || !empty($success)): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <h2>Installation Complete!</h2>
                <p>DriveEasy is ready to use.</p>

                <div class="creds">
                    <p><i class="fas fa-user-shield"></i> <strong>Admin Login</strong></p>
                    <p>URL: <code><a href="admin/login.php" style="color: #e94560;">/driveeasy/admin/login.php</a></code></p>
                    <p>Username: <code>admin</code></p>
                    <p>Password: <code>password</code></p>
                    <hr style="margin: 10px 0; border: none; border-top: 1px solid #e0e0e0;">
                    <p><i class="fas fa-exclamation-triangle" style="color: #856404;"></i> <strong>Security:</strong> Delete <code>install.php</code> after setup!</p>
                </div>

                <div class="btn-group">
                    <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> Visit Website</a>
                    <a href="admin/login.php" class="btn btn-secondary"><i class="fas fa-tachometer-alt"></i> Admin Panel</a>
                </div>
            </div>
        <?php else: ?>
            <div class="step-indicator">
                <div class="step-dot active">1</div>
                <div class="step-line"></div>
                <div class="step-dot">2</div>
                <div class="step-line"></div>
                <div class="step-dot">3</div>
            </div>

            <p style="text-align: center; color: #666; margin-bottom: 22px; font-size: 0.95rem;">
                Enter your MySQL database credentials to begin.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                    <div class="hint">Usually <strong>localhost</strong> for XAMPP/WAMP</div>
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="driveeasy" required>
                    <div class="hint">Will be created if it doesn't exist</div>
                </div>
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_user" value="root" required>
                    <div class="hint">Default is <strong>root</strong> for XAMPP</div>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" placeholder="(leave blank if none)">
                    <div class="hint">Default is empty for XAMPP</div>
                </div>
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;">
                <div class="form-group">
                    <label>Admin Email (optional)</label>
                    <input type="email" name="admin_email" value="admin@driveeasy.com">
                </div>
                <button type="submit" class="btn-install">
                    <i class="fas fa-database"></i> Install Now
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
