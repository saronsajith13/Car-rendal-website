<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Handle toggle availability (must be before header to avoid headers already sent)
if (isset($_GET['toggle'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $car_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE cars SET availability = NOT availability WHERE car_id = ?");
    $stmt->execute([$car_id]);
    redirect('manage-cars.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $car_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image FROM cars WHERE car_id = ?");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    if ($car && $car['image'] && file_exists("../uploads/cars/" . $car['image'])) {
        unlink("../uploads/cars/" . $car['image']);
    }
    $stmt = $pdo->prepare("DELETE FROM cars WHERE car_id = ?");
    $stmt->execute([$car_id]);
    redirect('manage-cars.php');
}

$stmt = $pdo->query("SELECT * FROM cars ORDER BY car_id DESC");
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-car"></i> Manage Cars</h2>
        <a href="add-car.php" class="btn-add"><i class="fas fa-plus"></i> Add New Car</a>
    </div>
    <div class="admin-card-body">
        <?php if (empty($cars)): ?>
            <div class="empty-state">
                <i class="fas fa-car"></i>
                <h3>No Cars Added</h3>
                <p>Start by adding your first car to the fleet.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Year</th>
                            <th>Price/Day</th>
                            <th>Fuel</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td><?= $car['car_id'] ?></td>
                                <td>
                                    <div style="width: 60px; height: 40px; background: var(--primary); border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        <?php if ($car['image'] && file_exists("../uploads/cars/" . $car['image'])): ?>
                                            <img src="../uploads/cars/<?= htmlspecialchars($car['image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-car" style="color: var(--secondary); font-size: 1rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><strong><?= htmlspecialchars($car['car_name']) ?></strong></td>
                                <td><?= htmlspecialchars($car['brand']) ?></td>
                                <td><?= htmlspecialchars($car['model']) ?></td>
                                <td><?= $car['year'] ?></td>
                                <td>$<?= number_format($car['price_per_day'], 2) ?></td>
                                <td><?= $car['fuel_type'] ?></td>
                                <td>
                                    <span class="badge <?= $car['availability'] ? 'badge-available' : 'badge-unavailable' ?>">
                                        <?= $car['availability'] ? 'Available' : 'Unavailable' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit-car.php?id=<?= $car['car_id'] ?>" class="action-btn action-btn-edit"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?= $car['car_id'] ?>&csrf_token=<?= generateCSRFToken() ?>" class="action-btn action-btn-delete confirm-delete"><i class="fas fa-trash"></i></a>
                                    <a href="manage-cars.php?id=<?= $car['car_id'] ?>&toggle=1&csrf_token=<?= generateCSRFToken() ?>" class="btn-add" style="padding: 6px 10px; font-size: 0.75rem;">
                                        <?= $car['availability'] ? 'Deactivate' : 'Activate' ?>
                                    </a>
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
