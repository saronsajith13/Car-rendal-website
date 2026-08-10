<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Handle approve/reject
if (isset($_GET['approve'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $bid = (int)$_GET['approve'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE booking_id = ?");
    $stmt->execute([$bid]);
    // Mark car as unavailable when booking is approved
    $stmt2 = $pdo->prepare("UPDATE cars c JOIN bookings b ON c.car_id = b.car_id SET c.availability = 0 WHERE b.booking_id = ?");
    $stmt2->execute([$bid]);
    redirect('manage-bookings.php');
}
if (isset($_GET['reject'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $bid = (int)$_GET['reject'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE booking_id = ?");
    $stmt->execute([$bid]);
    redirect('manage-bookings.php');
}
if (isset($_GET['complete'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $bid = (int)$_GET['complete'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE booking_id = ?");
    $stmt->execute([$bid]);

    $stmt2 = $pdo->prepare("UPDATE cars c JOIN bookings b ON c.car_id = b.car_id SET c.availability = 1 WHERE b.booking_id = ?");
    $stmt2->execute([$bid]);

    redirect('manage-bookings.php');
}

$status_filter = $_GET['status'] ?? '';

$sql = "
    SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone, c.car_name, c.brand, c.model
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN cars c ON b.car_id = c.car_id
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " WHERE b.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-calendar-check"></i> Manage Bookings</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <select id="statusFilter" onchange="window.location.href='?status='+this.value" style="padding: 8px 15px; border: 2px solid #e0e0e0; border-radius: 6px;">
                <option value="">All Status</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <span style="color: var(--text-light);"><?= count($bookings) ?> bookings</span>
        </div>
    </div>
    <div class="admin-card-body">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>No bookings match the current filter.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Car</th>
                            <th>Pickup</th>
                            <th>Return</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>#<?= $b['booking_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($b['user_name']) ?></strong><br>
                                    <small style="color: var(--text-light);"><?= htmlspecialchars($b['user_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($b['car_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($b['pickup_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($b['return_date'])) ?></td>
                                <td><strong>$<?= number_format($b['total_price'], 2) ?></strong></td>
                                <td><span class="status status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                                <td>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <a href="?approve=<?= $b['booking_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="action-btn btn-approve" style="padding: 6px 12px;"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?reject=<?= $b['booking_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="action-btn btn-reject" style="padding: 6px 12px;"><i class="fas fa-times"></i> Reject</a>
                                    <?php elseif ($b['status'] === 'approved'): ?>
                                        <a href="?complete=<?= $b['booking_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="action-btn action-btn-view" style="padding: 6px 12px;"><i class="fas fa-check-double"></i> Complete</a>
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

<?php require_once 'footer.php'; ?>
