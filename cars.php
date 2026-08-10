<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$brand = $_GET['brand'] ?? '';
$fuel = $_GET['fuel'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM cars WHERE 1=1";
$params = [];

if (!empty($brand)) {
    $sql .= " AND brand = ?";
    $params[] = $brand;
}
if (!empty($fuel)) {
    $sql .= " AND fuel_type = ?";
    $params[] = $fuel;
}
if (!empty($max_price)) {
    $sql .= " AND price_per_day <= ?";
    $params[] = $max_price;
}
if (!empty($search)) {
    $sql .= " AND (car_name LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY car_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

$brands = $pdo->query("SELECT DISTINCT brand FROM cars ORDER BY brand")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="listing-header">
    <h1>Our Fleet</h1>
    <p>Choose from our wide range of vehicles</p>
</section>

<section class="filter-bar">
    <form method="GET" action="" class="filter-container">
        <input type="text" name="search" placeholder="Search by name, brand..." value="<?= htmlspecialchars($search) ?>">
        <select name="brand">
            <option value="">All Brands</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $brand === $b['brand'] ? 'selected' : '' ?>><?= htmlspecialchars($b['brand']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="fuel">
            <option value="">Fuel Type</option>
            <option value="Petrol" <?= $fuel === 'Petrol' ? 'selected' : '' ?>>Petrol</option>
            <option value="Diesel" <?= $fuel === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
            <option value="Electric" <?= $fuel === 'Electric' ? 'selected' : '' ?>>Electric</option>
            <option value="Hybrid" <?= $fuel === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
        </select>
        <input type="number" name="max_price" placeholder="Max $/day" value="<?= htmlspecialchars($max_price) ?>" min="0">
        <button type="submit" class="search-btn"><i class="fas fa-filter"></i> Filter</button>
        <a href="cars.php" class="btn-secondary" style="padding: 10px 20px; font-size: 0.9rem;">Clear</a>
    </form>
</section>

<section class="section">
    <div class="featured-grid">
        <?php if (empty($cars)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-car"></i>
                <h3>No Cars Found</h3>
                <p>Try adjusting your search or filter criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cars as $car): ?>
                <div class="car-card">
                    <div class="car-card-img">
                        <?php if ($car['image'] && file_exists("uploads/cars/" . $car['image'])): ?>
                            <img src="uploads/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['car_name']) ?>">
                        <?php else: ?>
                            <i class="fas fa-car-side placeholder-icon"></i>
                        <?php endif; ?>
                    </div>
                    <div class="car-card-body">
                        <span class="badge <?= $car['availability'] ? 'badge-available' : 'badge-unavailable' ?>">
                            <?= $car['availability'] ? 'Available' : 'Rented' ?>
                        </span>
                        <h3><?= htmlspecialchars($car['car_name']) ?></h3>
                        <p class="car-brand"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?> (<?= $car['year'] ?>)</p>
                        <div class="car-features">
                            <span><i class="fas fa-gas-pump"></i> <?= $car['fuel_type'] ?></span>
                            <span><i class="fas fa-users"></i> <?= $car['seats'] ?> Seats</span>
                            <span><i class="fas fa-cog"></i> <?= $car['transmission'] ?></span>
                        </div>
                        <div class="car-price">$<?= number_format($car['price_per_day'], 2) ?> <small>/ day</small></div>
                        <a href="car-details.php?id=<?= $car['car_id'] ?>" class="btn-rent">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
