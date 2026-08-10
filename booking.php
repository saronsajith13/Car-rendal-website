<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$car_id = $_GET['car_id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM cars WHERE car_id = ? AND availability = 1");
$stmt->execute([$car_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    redirect('cars.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $pickup_date = $_POST['pickup_date'];
    $return_date = $_POST['return_date'];
    $pickup_location = trim($_POST['pickup_location']);

    if (empty($pickup_date) || empty($return_date)) {
        $error = 'Please select pickup and return dates.';
    } elseif ($return_date <= $pickup_date) {
        $error = 'Return date must be after pickup date.';
    } else {
        $days = getTotalDays($pickup_date, $return_date);
        $total_price = $car['price_per_day'] * $days; // Server-calculated, not from hidden input

        // Check for overlapping bookings
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE car_id = ? AND status IN ('pending', 'approved')
            AND pickup_date < ? AND return_date > ?
        ");
        $stmt->execute([$car_id, $return_date, $pickup_date]);

        if ($stmt->fetchColumn() > 0) {
            $error = 'This car is already booked for the selected dates.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (user_id, car_id, pickup_date, return_date, total_price, pickup_location)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $car_id,
                $pickup_date,
                $return_date,
                $total_price,
                $pickup_location
            ]);
            $booking_id = $pdo->lastInsertId();

            // Create payment record
            $stmt = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, payment_status)
                VALUES (?, ?, 'pending', 'pending')
            ");
            $stmt->execute([$booking_id, $total_price]);

            redirect("payment.php?booking_id=$booking_id");
        }
    }
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book - <?= htmlspecialchars($car['car_name']) ?> - DriveEasy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="listing-header">
    <h1>Book Your Car</h1>
    <p>Complete the details to rent <?= htmlspecialchars($car['car_name']) ?></p>
</section>

<section class="booking-section">
    <?php if ($error): ?>
        <div class="alert alert-error" style="max-width: 800px; margin: 0 auto 20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="booking-grid">
        <div class="booking-summary">
            <h3><i class="fas fa-car"></i> Rental Summary</h3>
            <div style="display: flex; gap: 20px; margin-bottom: 20px; align-items: center;">
                <div style="width: 100px; height: 70px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-car-side" style="color: var(--secondary); font-size: 2rem;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary);"><?= htmlspecialchars($car['car_name']) ?></h4>
                    <p style="color: var(--text-light); font-size: 0.9rem;"><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></p>
                </div>
            </div>
            <div class="summary-item">
                <span>Price per day</span>
                <span>$<?= number_format($car['price_per_day'], 2) ?></span>
            </div>
            <div class="summary-item" id="summary_days">
                <span>Number of days</span>
                <span id="days_count">-</span>
            </div>
            <div class="summary-item" id="summary_total">
                <span>Total Amount</span>
                <span id="total_amount">$0.00</span>
            </div>
        </div>

        <div class="booking-form">
            <h3><i class="fas fa-calendar-alt"></i> Rental Details</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Pickup Date</label>
                    <input type="date" name="pickup_date" id="pickup_date" min="<?= $today ?>" required>
                </div>
                <div class="form-group">
                    <label>Return Date</label>
                    <input type="date" name="return_date" id="return_date" min="<?= $today ?>" required>
                </div>
                <div class="form-group">
                    <label>Pickup Location</label>
                    <select name="pickup_location">
                        <option value="Main Office">Main Office</option>
                        <option value="Airport Terminal 1">Airport Terminal 1</option>
                        <option value="Airport Terminal 2">Airport Terminal 2</option>
                        <option value="City Center">City Center</option>
                        <option value="Train Station">Train Station</option>
                    </select>
                </div>
                <input type="hidden" id="price_per_day" data-price="<?= $car['price_per_day'] ?>">
                <input type="hidden" name="total_price" id="total_price">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit" class="btn-submit" style="margin-top: 10px;">
                    <i class="fas fa-arrow-right"></i> Proceed to Payment
                </button>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pickup = document.getElementById('pickup_date');
    const ret = document.getElementById('return_date');
    const pricePerDay = <?= $car['price_per_day'] ?>;
    const totalAmount = document.getElementById('total_amount');
    const daysCount = document.getElementById('days_count');

    function updateSummary() {
        if (pickup.value && ret.value) {
            const p = new Date(pickup.value);
            const r = new Date(ret.value);
            if (r > p) {
                const diff = Math.ceil((r - p) / (1000 * 60 * 60 * 24));
                daysCount.textContent = diff;
                totalAmount.textContent = '$' + (pricePerDay * diff).toFixed(2);
                document.getElementById('total_price').value = (pricePerDay * diff).toFixed(2);
            }
        }
    }

    pickup.addEventListener('change', function() {
        ret.setAttribute('min', this.value);
        updateSummary();
    });
    ret.addEventListener('change', updateSummary);
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
