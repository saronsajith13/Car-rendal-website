<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        $success = 'Thank you for your message! We will get back to you soon.';
    }
}
?>

<section class="listing-header">
    <h1>Contact Us</h1>
    <p>We'd love to hear from you</p>
</section>

<section class="contact-section">
    <?php if ($success): ?>
        <div class="alert alert-success" style="max-width: 800px; margin: 0 auto 30px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="contact-grid">
        <div class="contact-info">
            <h2>Get In Touch</h2>
            <p style="color: var(--text-light); margin-bottom: 30px;">Have a question, feedback, or need assistance? We're here to help!</p>

            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Visit Us</h4>
                    <p>123 Main Street, Downtown<br>City, State 12345</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <h4>Call Us</h4>
                    <p>+1 (234) 567-890<br>+1 (987) 654-321</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Email Us</h4>
                    <p>info@driveeasy.com<br>support@driveeasy.com</p>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h4>Working Hours</h4>
                    <p>Mon - Sat: 8:00 AM - 8:00 PM<br>Sunday: 10:00 AM - 6:00 PM</p>
                </div>
            </div>

            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.966309591943!2d-73.9878526845937!3d40.74876897932865!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b3117469%3A0xd134e199a405a163!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1644262070688!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>

        <div>
            <div style="background: var(--white); padding: 35px; border-radius: var(--radius); box-shadow: var(--shadow);">
                <h3 style="color: var(--primary); margin-bottom: 25px;">Send a Message</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="name" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label>Your Email *</label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="How can we help?">
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" placeholder="Write your message here..." required></textarea>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
