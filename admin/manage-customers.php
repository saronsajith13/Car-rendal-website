<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-users"></i> Manage Customers</h2>
        <span style="color: var(--text-light);">Total: <?= count($users) ?> users</span>
    </div>
    <div class="admin-card-body">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No Users Registered</h3>
                <p>Users will appear here once they register.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Registered</th>
                            <th>Bookings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
                            $stmt->execute([$user['user_id']]);
                            $bookingCount = $stmt->fetchColumn();
                        ?>
                            <tr>
                                <td><?= $user['user_id'] ?></td>
                                <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td><?= htmlspecialchars($user['address'] ?? '-') ?></td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td><span class="badge badge-available"><?= $bookingCount ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
