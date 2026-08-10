<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get bookings
$stmt = $pdo->prepare("
    SELECT b.*, c.car_name, c.brand, c.model, c.image, c.price_per_day
    FROM bookings b
    JOIN cars c ON b.car_id = c.car_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$totalBookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completedBookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$totalSpent = $stmt->fetchColumn();

// Handle profile update
$profileMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (!empty($name) && !empty($phone)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->execute([$name, $phone, $address, $user_id]);
        $_SESSION['user_name'] = $name;
        $profileMessage = 'Profile updated successfully!';
    }
}

// Handle password change
$passwordMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $passwordMessage = 'Please fill in all password fields.';
    } elseif ($new !== $confirm) {
        $passwordMessage = 'New passwords do not match.';
    } elseif (strlen($new) < 6) {
        $passwordMessage = 'New password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if (password_verify($current, $row['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed, $user_id]);
            $passwordMessage = 'Password changed successfully!';
        } else {
            $passwordMessage = 'Current password is incorrect.';
        }
    }
}

// Handle cancel booking
if (isset($_GET['cancel_id'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $cancel_id = (int)$_GET['cancel_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$cancel_id, $user_id]);
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - DriveEasy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="dashboard-section">
    <div class="dashboard-header">
        <div>
            <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
            <p style="color: var(--text-light);">Manage your rentals and profile</p>
        </div>
        <a href="cars.php" class="btn-primary"><i class="fas fa-plus"></i> Rent a Car</a>
    </div>

    <?php if ($profileMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($profileMessage) ?></div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <div class="stat-card">
            <i class="fas fa-calendar-check"></i>
            <h3><?= $totalBookings ?></h3>
            <p>Total Bookings</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <h3><?= $completedBookings ?></h3>
            <p>Completed Trips</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-dollar-sign"></i>
            <h3>$<?= number_format($totalSpent, 2) ?></h3>
            <p>Total Spent</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <h3><?= $totalBookings - $completedBookings ?></h3>
            <p>Active / Pending</p>
        </div>
    </div>

    <div class="admin-card" style="margin-bottom: 30px;">
        <div class="admin-card-header">
            <h2><i class="fas fa-history"></i> My Bookings</h2>
            <div>
                <button class="btn-add" onclick="document.getElementById('profileSection').scrollIntoView({behavior: 'smooth'})">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </button>
            </div>
        </div>
        <div class="admin-card-body">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-car"></i>
                    <h3>No Bookings Yet</h3>
                    <p>Browse our fleet and rent your first car!</p>
                    <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">Browse Cars</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Car</th>
                                <th>Pickup</th>
                                <th>Return</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>#<?= $b['booking_id'] ?></td>
                                    <td><?= htmlspecialchars($b['car_name']) ?></td>
                                    <td><?= date('M j, Y', strtotime($b['pickup_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($b['return_date'])) ?></td>
                                    <td>$<?= number_format($b['total_price'], 2) ?></td>
                                    <td><span class="status status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                                    <td>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="?cancel_id=<?= $b['booking_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="action-btn action-btn-delete confirm-delete">Cancel</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-light); font-size: 0.85rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card" id="profileSection">
        <div class="admin-card-header">
            <h2><i class="fas fa-user-cog"></i> Profile Settings</h2>
        </div>
        <div class="admin-card-body">
            <form method="POST" action="" style="max-width: 500px;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: #f0f0f0;">
                    <small style="color: var(--text-light);">Email cannot be changed</small>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
            </form>
        </div>
    </div>

    <div class="admin-card" style="margin-top: 30px;">
        <div class="admin-card-header">
            <h2><i class="fas fa-key"></i> Change Password</h2>
        </div>
        <div class="admin-card-body">
            <?php if ($passwordMessage): ?>
                <div class="alert <?= strpos($passwordMessage, 'successfully') !== false ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($passwordMessage) ?></div>
            <?php endif; ?>
            <form method="POST" action="" style="max-width: 500px;">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit" name="change_password" class="btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>
