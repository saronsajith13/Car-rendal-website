<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_name = trim($_POST['car_name']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year = (int)$_POST['year'];
    $price_per_day = (float)$_POST['price_per_day'];
    $fuel_type = $_POST['fuel_type'];
    $seats = (int)$_POST['seats'];
    $transmission = $_POST['transmission'];
    $description = trim($_POST['description']);
    $availability = isset($_POST['availability']) ? 1 : 0;

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } elseif (empty($car_name) || empty($brand) || empty($model) || $price_per_day <= 0) {
        $error = 'Please fill in all required fields.';
    } else {
        $image = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (in_array($ext, $allowed) && in_array($mime, $allowed_mime)) {
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/cars/$image");
            } else {
                $error = 'Invalid image format. Allowed: jpg, jpeg, png, webp';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                INSERT INTO cars (car_name, brand, model, year, price_per_day, fuel_type, seats, transmission, image, availability, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$car_name, $brand, $model, $year, $price_per_day, $fuel_type, $seats, $transmission, $image, $availability, $description]);
            $success = 'Car added successfully!';
        }
    }
}

require_once 'header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-plus-circle"></i> Add New Car</h2>
        <a href="manage-cars.php" class="btn-add" style="background: transparent; border: 2px solid var(--primary); color: var(--primary);">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div class="admin-card-body">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" style="max-width: 700px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Car Name *</label>
                    <input type="text" name="car_name" placeholder="e.g. Toyota Camry" required>
                </div>
                <div class="form-group">
                    <label>Brand *</label>
                    <input type="text" name="brand" placeholder="e.g. Toyota" required>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" placeholder="e.g. Camry" required>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" value="<?= date('Y') ?>" min="2000" max="<?= date('Y') + 1 ?>">
                </div>
                <div class="form-group">
                    <label>Price Per Day ($) *</label>
                    <input type="number" name="price_per_day" step="0.01" min="0" placeholder="50.00" required>
                </div>
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type">
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Seats</label>
                    <select name="seats">
                        <option value="2">2</option>
                        <option value="4">4</option>
                        <option value="5" selected>5</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transmission</label>
                    <select name="transmission">
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                        <option value="CVT">CVT</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Car Image</label>
                <input type="file" name="image" accept="image/*" id="car_image">
                <div id="image_preview" style="margin-top: 10px; width: 150px; height: 100px; background: var(--light); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <span style="color: var(--text-light); font-size: 0.8rem;">Preview</span>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Car description..." rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="availability" checked> Available for rent
                </label>
            </div>
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <button type="submit" class="btn-submit" style="max-width: 200px;"><i class="fas fa-save"></i> Add Car</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
