<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Fetch featured cars (available)
$stmt = $pdo->query("SELECT * FROM cars WHERE availability = 1 ORDER BY car_id DESC LIMIT 6");
$featuredCars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch brands for search
$brands = $pdo->query("SELECT DISTINCT brand FROM cars ORDER BY brand")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="hero">
    <h1>Drive Your <span>Dream</span> Car Today</h1>
    <p>Premium car rental service with the best prices. Choose from our wide range of vehicles for any occasion.</p>
    <div class="hero-buttons">
        <a href="cars.php" class="btn-primary"><i class="fas fa-search"></i> Browse Cars</a>
        <a href="register.php" class="btn-secondary"><i class="fas fa-user-plus"></i> Get Started</a>
    </div>
</section>

<section class="search-section">
    <form action="cars.php" method="GET" class="search-container">
        <select name="brand">
            <option value="">All Brands</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= htmlspecialchars($b['brand']) ?>"><?= htmlspecialchars($b['brand']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="fuel">
            <option value="">Fuel Type</option>
            <option value="Petrol">Petrol</option>
            <option value="Diesel">Diesel</option>
            <option value="Electric">Electric</option>
            <option value="Hybrid">Hybrid</option>
        </select>
        <input type="number" name="max_price" placeholder="Max Price Per Day ($)" min="0">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
    </form>
</section>

<section class="section">
    <h2 class="section-title">Featured Vehicles</h2>
    <p class="section-subtitle">Choose from our精选 fleet of premium vehicles</p>
    <div class="featured-grid">
        <?php if (empty($featuredCars)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-car"></i>
                <h3>No Cars Available</h3>
                <p>Check back later for new arrivals.</p>
            </div>
        <?php else: ?>
            <?php foreach ($featuredCars as $car): ?>
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

<section class="section" style="background: var(--white);">
    <h2 class="section-title">Why Choose DriveEasy?</h2>
    <p class="section-subtitle">We provide the best car rental experience</p>
    <div class="features-grid">
        <div class="feature-box">
            <i class="fas fa-car"></i>
            <h3>Wide Selection</h3>
            <p>Choose from a diverse fleet of well-maintained vehicles for every need.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-dollar-sign"></i>
            <h3>Best Prices</h3>
            <p>Competitive rates with no hidden charges. Best value for your money.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-headset"></i>
            <h3>24/7 Support</h3>
            <p>Round-the-clock customer service to assist you anytime, anywhere.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-shield-alt"></i>
            <h3>Fully Insured</h3>
            <p>All our rentals come with comprehensive insurance for your peace of mind.</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
