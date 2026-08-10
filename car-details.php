<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$car_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM cars WHERE car_id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    redirect('cars.php');
}

// Fetch reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.name FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.car_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$car_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE car_id = ?");
$stmt->execute([$car_id]);
$ratingData = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isLoggedIn()) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, car_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $car_id, $rating, $comment]);
        redirect("car-details.php?id=$car_id");
    }
}
?>

<section class="car-details-section">
    <div class="car-details-grid">
        <div class="car-details-image">
            <?php if ($car['image'] && file_exists("uploads/cars/" . $car['image'])): ?>
                <img src="uploads/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['car_name']) ?>">
            <?php else: ?>
                <i class="fas fa-car-side placeholder-icon"></i>
            <?php endif; ?>
        </div>

        <div class="car-details-info">
            <h1><?= htmlspecialchars($car['car_name']) ?></h1>
            <p class="car-brand-model"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?> (<?= $car['year'] ?>)</p>

            <?php if ($ratingData['total_reviews'] > 0): ?>
                <p style="margin-bottom: 15px; color: var(--gold);">
                    <?php
                    $avg = round($ratingData['avg_rating']);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $avg ? '★' : '☆';
                    }
                    ?>
                    <span style="color: var(--text-light); font-size: 0.9rem;"> (<?= $ratingData['total_reviews'] ?> reviews)</span>
                </p>
            <?php endif; ?>

            <div class="car-specs">
                <div class="spec-item">
                    <span>Fuel Type</span>
                    <strong><?= $car['fuel_type'] ?></strong>
                </div>
                <div class="spec-item">
                    <span>Seats</span>
                    <strong><?= $car['seats'] ?> Persons</strong>
                </div>
                <div class="spec-item">
                    <span>Transmission</span>
                    <strong><?= $car['transmission'] ?></strong>
                </div>
                <div class="spec-item">
                    <span>Year</span>
                    <strong><?= $car['year'] ?></strong>
                </div>
            </div>

            <div class="car-price-big">
                $<?= number_format($car['price_per_day'], 2) ?> <small>per day</small>
            </div>

            <p class="description"><?= nl2br(htmlspecialchars($car['description'])) ?></p>

            <?php if ($car['availability']): ?>
                <?php if (isLoggedIn()): ?>
                    <a href="booking.php?car_id=<?= $car['car_id'] ?>" class="btn-rent-now"><i class="fas fa-calendar-check"></i> Rent This Car</a>
                <?php else: ?>
                    <a href="login.php" class="btn-rent-now"><i class="fas fa-sign-in-alt"></i> Login to Rent</a>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn-rent-now" disabled><i class="fas fa-times-circle"></i> Currently Unavailable</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 0; max-width: 1200px; margin: 0 auto;">
    <h2 class="section-title" style="text-align: left; font-size: 1.5rem;">Customer Reviews</h2>

    <?php if (isLoggedIn()): ?>
        <div style="background: var(--white); padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 30px;">
            <h3 style="margin-bottom: 15px; color: var(--primary);">Write a Review</h3>
            <form method="POST" action="">
                <div class="rating-input">
                    <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                    <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                    <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                    <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                    <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                </div>
                <div class="form-group">
                    <textarea name="comment" placeholder="Share your experience..." required></textarea>
                </div>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit" name="submit_review" class="btn-primary" style="padding: 10px 25px;">Submit Review</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="reviews-section">
        <?php if (empty($reviews)): ?>
            <p style="color: var(--text-light);">No reviews yet. Be the first to review!</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="review-user"><?= htmlspecialchars($review['name']) ?></span>
                        <span class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $review['rating'] ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </span>
                    </div>
                    <p class="review-comment"><?= htmlspecialchars($review['comment']) ?></p>
                    <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 8px;"><?= date('F j, Y', strtotime($review['created_at'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
