<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$car_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM cars WHERE car_id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    redirect('manage-cars.php');
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
        $image = $car['image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            if (in_array($ext, $allowed) && in_array($mime, $allowed_mime)) {
                if ($image && file_exists("../uploads/cars/$image")) {
                    unlink("../uploads/cars/$image");
                }
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/cars/$image");
            } else {
                $error = 'Invalid image format. Allowed: jpg, jpeg, png, webp';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                UPDATE cars SET car_name=?, brand=?, model=?, year=?, price_per_day=?, fuel_type=?, seats=?, transmission=?, image=?, availability=?, description=?
                WHERE car_id=?
            ");
            $stmt->execute([$car_name, $brand, $model, $year, $price_per_day, $fuel_type, $seats, $transmission, $image, $availability, $description, $car_id]);
            $success = 'Car updated successfully!';
            $car = array_merge($car, $_POST);
            $car['image'] = $image;
            $car['availability'] = $availability;
        }
    }
}

require_once 'header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2><i class="fas fa-edit"></i> Edit Car</h2>
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
                    <input type="text" name="car_name" value="<?= htmlspecialchars($car['car_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Brand *</label>
                    <input type="text" name="brand" value="<?= htmlspecialchars($car['brand']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" value="<?= htmlspecialchars($car['model']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" value="<?= $car['year'] ?>" min="2000" max="<?= date('Y') + 1 ?>">
                </div>
                <div class="form-group">
                    <label>Price Per Day ($) *</label>
                    <input type="number" name="price_per_day" step="0.01" min="0" value="<?= $car['price_per_day'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type">
                        <option value="Petrol" <?= $car['fuel_type'] === 'Petrol' ? 'selected' : '' ?>>Petrol</option>
                        <option value="Diesel" <?= $car['fuel_type'] === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                        <option value="Electric" <?= $car['fuel_type'] === 'Electric' ? 'selected' : '' ?>>Electric</option>
                        <option value="Hybrid" <?= $car['fuel_type'] === 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Seats</label>
                    <select name="seats">
                        <?php foreach ([2, 4, 5, 7, 8] as $s): ?>
                            <option value="<?= $s ?>" <?= $car['seats'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transmission</label>
                    <select name="transmission">
                        <option value="Automatic" <?= $car['transmission'] === 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                        <option value="Manual" <?= $car['transmission'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                        <option value="CVT" <?= $car['transmission'] === 'CVT' ? 'selected' : '' ?>>CVT</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Car Image</label>
                <input type="file" name="image" accept="image/*" id="car_image">
                <div id="image_preview" style="margin-top: 10px; width: 150px; height: 100px; background: var(--light); border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <?php if ($car['image'] && file_exists("../uploads/cars/" . $car['image'])): ?>
                        <img src="../uploads/cars/<?= htmlspecialchars($car['image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="color: var(--text-light); font-size: 0.8rem;">No Image</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($car['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="availability" <?= $car['availability'] ? 'checked' : '' ?>> Available for rent
                </label>
            </div>
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <button type="submit" class="btn-submit" style="max-width: 200px;"><i class="fas fa-save"></i> Update Car</button>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
