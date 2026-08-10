<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$booking_id = $_GET['booking_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT b.*, c.car_name, c.brand, c.model, c.image
    FROM bookings b
    JOIN cars c ON b.car_id = c.car_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    redirect('dashboard.php');
}

// Check if already paid
$stmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $payment_method = $_POST['payment_method'] ?? 'credit_card';

    $stmt = $pdo->prepare("UPDATE payments SET payment_method = ?, payment_status = 'completed', transaction_id = ? WHERE booking_id = ?");
    $txn_id = 'TXN' . strtoupper(uniqid());
    $stmt->execute([$payment_method, $txn_id, $booking_id]);

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'pending' WHERE booking_id = ?");
    $stmt->execute([$booking_id]);

    $success = 'Payment successful! Your booking is pending admin approval.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - DriveEasy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<?php if ($success): ?>
    <section class="success-section">
        <div class="success-card">
            <i class="fas fa-check-circle"></i>
            <h2>Payment Successful!</h2>
            <p>Your booking for <?= htmlspecialchars($booking['car_name']) ?> has been confirmed. You will receive a confirmation once admin approves.</p>
            <div style="text-align: left; background: var(--light); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <p><strong>Booking ID:</strong> #<?= $booking['booking_id'] ?></p>
                <p><strong>Car:</strong> <?= htmlspecialchars($booking['car_name']) ?></p>
                <p><strong>Pickup:</strong> <?= date('M j, Y', strtotime($booking['pickup_date'])) ?></p>
                <p><strong>Return:</strong> <?= date('M j, Y', strtotime($booking['return_date'])) ?></p>
                <p><strong>Total Paid:</strong> $<?= number_format($booking['total_price'], 2) ?></p>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <a href="dashboard.php" class="btn-primary">View My Bookings</a>
                <a href="index.php" class="btn-secondary" style="border-color: var(--primary); color: var(--primary);">Back to Home</a>
            </div>
        </div>
    </section>
<?php else: ?>
    <section class="payment-section">
        <div class="payment-card">
            <h2><i class="fas fa-credit-card"></i> Complete Payment</h2>
            <p style="text-align: center; color: var(--text-light); margin-bottom: 25px;">Secure payment for your rental</p>

            <div style="background: var(--light); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: var(--text-light);">Booking #</span>
                    <span style="font-weight: 600;"><?= $booking['booking_id'] ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: var(--text-light);">Car</span>
                    <span style="font-weight: 600;"><?= htmlspecialchars($booking['car_name']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="color: var(--text-light);">Duration</span>
                    <span style="font-weight: 600;"><?= date('M j', strtotime($booking['pickup_date'])) ?> - <?= date('M j, Y', strtotime($booking['return_date'])) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 2px solid #ddd;">
                    <span style="font-size: 1.2rem; font-weight: 700;">Total Amount</span>
                    <span style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">$<?= number_format($booking['total_price'], 2) ?></span>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Select Payment Method</label>
                    <div class="payment-methods">
                        <div class="payment-method selected" data-method="credit_card">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit Card</span>
                        </div>
                        <div class="payment-method" data-method="paypal">
                            <i class="fab fa-paypal"></i>
                            <span>PayPal</span>
                        </div>
                        <div class="payment-method" data-method="bank_transfer">
                            <i class="fas fa-university"></i>
                            <span>Bank Transfer</span>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="payment_method" value="credit_card">
                </div>

                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-lock"></i> Pay $<?= number_format($booking['total_price'], 2) ?>
                </button>
                <p style="text-align: center; margin-top: 15px; font-size: 0.85rem; color: var(--text-light);">
                    <i class="fas fa-shield-alt"></i> Your payment is secure and encrypted
                </p>
            </form>
        </div>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
</body>
</html>
