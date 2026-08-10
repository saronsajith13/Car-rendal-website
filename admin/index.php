<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

require_once 'header.php';

// Dashboard stats
$totalCars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalIncome = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'completed'")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$availableCars = $pdo->query("SELECT COUNT(*) FROM cars WHERE availability = 1")->fetchColumn();

// Recent bookings
$stmt = $pdo->query("
    SELECT b.*, u.name as user_name, c.car_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN cars c ON b.car_id = c.car_id
    ORDER BY b.created_at DESC LIMIT 5
");
$recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 style="color: var(--primary); margin-bottom: 25px;"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-icon blue"><i class="fas fa-car"></i></div>
        <div>
            <h3><?= $totalCars ?></h3>
            <p>Total Cars <span style="color: var(--secondary);">(<?= $availableCars ?> available)</span></p>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon green"><i class="fas fa-calendar-check"></i></div>
        <div>
            <h3><?= $totalBookings ?></h3>
            <p>Total Bookings <span style="color: #856404;">(<?= $pendingBookings ?> pending)</span></p>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon gold"><i class="fas fa-dollar-sign"></i></div>
        <div>
            <h3>$<?= number_format($totalIncome, 2) ?></h3>
            <p>Total Income</p>
        </div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-icon red"><i class="fas fa-users"></i></div>
        <div>
            <h3><?= $totalUsers ?></h3>
            <p>Registered Users</p>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-clock"></i> Recent Bookings</h2>
        <a href="manage-bookings.php" class="btn-add">View All</a>
    </div>
    <div class="admin-card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Car</th>
                        <th>Dates</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b): ?>
                        <tr>
                            <td>#<?= $b['booking_id'] ?></td>
                            <td><?= htmlspecialchars($b['user_name']) ?></td>
                            <td><?= htmlspecialchars($b['car_name']) ?></td>
                            <td><?= date('M j', strtotime($b['pickup_date'])) ?> - <?= date('M j, Y', strtotime($b['return_date'])) ?></td>
                            <td>$<?= number_format($b['total_price'], 2) ?></td>
                            <td><span class="status status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentBookings)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-light);">No bookings yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
