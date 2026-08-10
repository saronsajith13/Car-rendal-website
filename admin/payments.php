<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$stmt = $pdo->query("
    SELECT p.*, b.pickup_date, b.return_date, b.total_price as booking_total, 
           u.name as user_name, c.car_name, b.status as booking_status
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    JOIN cars c ON b.car_id = c.car_id
    ORDER BY p.payment_date DESC
");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed'")->fetchColumn();
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn();

require_once 'header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
    <div style="background: var(--white); padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow);">
        <p style="color: var(--text-light); font-size: 0.9rem;">Total Revenue</p>
        <h2 style="color: var(--primary); font-size: 2rem;">$<?= number_format($totalRevenue, 2) ?></h2>
    </div>
    <div style="background: var(--white); padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow);">
        <p style="color: var(--text-light); font-size: 0.9rem;">Pending Payments</p>
        <h2 style="color: #856404; font-size: 2rem;"><?= $pendingPayments ?></h2>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-credit-card"></i> Payment Records</h2>
        <span style="color: var(--text-light);"><?= count($payments) ?> transactions</span>
    </div>
    <div class="admin-card-body">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="fas fa-credit-card"></i>
                <h3>No Payments Yet</h3>
                <p>Payments will appear here once customers complete bookings.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking #</th>
                            <th>Customer</th>
                            <th>Car</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td>#<?= $p['payment_id'] ?></td>
                                <td>#<?= $p['booking_id'] ?></td>
                                <td><?= htmlspecialchars($p['user_name']) ?></td>
                                <td><?= htmlspecialchars($p['car_name']) ?></td>
                                <td><strong>$<?= number_format($p['amount'], 2) ?></strong></td>
                                <td style="text-transform: capitalize;">
                                    <?= str_replace('_', ' ', htmlspecialchars($p['payment_method'])) ?>
                                </td>
                                <td>
                                    <span class="status status-<?= $p['payment_status'] === 'completed' ? 'approved' : ($p['payment_status'] === 'pending' ? 'pending' : 'rejected') ?>">
                                        <?= ucfirst($p['payment_status']) ?>
                                    </span>
                                </td>
                                <td><small><?= htmlspecialchars($p['transaction_id'] ?? '-') ?></small></td>
                                <td><?= date('M j, Y', strtotime($p['payment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
