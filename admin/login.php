<?php
require_once '../includes/config.php';

if (isAdmin()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            redirect('index.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - DriveEasy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, var(--dark), var(--primary)); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: var(--white); padding: 40px; border-radius: var(--radius); box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 400px; margin: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <i class="fas fa-car" style="font-size: 3rem; color: var(--secondary);"></i>
            <h2 style="color: var(--primary); margin-top: 10px;">Admin Login</h2>
            <p style="color: var(--text-light);">DriveEasy Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter admin username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <button type="submit" class="btn-submit"><i class="fas fa-lock"></i> Sign In</button>
        </form>
        <p style="text-align: center; margin-top: 20px;">
            <a href="../index.php" style="color: var(--secondary);"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </p>
    </div>
</body>
</html>
