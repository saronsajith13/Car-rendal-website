// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            const icon = navToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
    }

    // Close menu on link click (mobile)
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                const icon = navToggle?.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            }
        });
    });

    // Auto-calculate booking total
    const pickupInput = document.getElementById('pickup_date');
    const returnInput = document.getElementById('return_date');
    const priceDisplay = document.getElementById('price_per_day');
    const totalDisplay = document.getElementById('total_amount');
    const totalInput = document.getElementById('total_price');

    function calculateTotal() {
        if (pickupInput && returnInput && pickupInput.value && returnInput.value) {
            const pickup = new Date(pickupInput.value);
            const ret = new Date(returnInput.value);

            if (ret > pickup) {
                const diffTime = Math.abs(ret - pickup);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const pricePerDay = parseFloat(priceDisplay?.dataset?.price || 0);

                if (pricePerDay > 0 && diffDays > 0) {
                    const total = pricePerDay * diffDays;
                    if (totalDisplay) totalDisplay.textContent = '$' + total.toFixed(2);
                    if (totalInput) totalInput.value = total.toFixed(2);
                }
            }
        }
    }

    if (pickupInput) pickupInput.addEventListener('change', calculateTotal);
    if (returnInput) returnInput.addEventListener('change', calculateTotal);

    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            const methodInput = document.getElementById('payment_method');
            if (methodInput) {
                methodInput.value = this.dataset.method;
            }
        });
    });

    // Set min date for pickup to today
    const today = new Date().toISOString().split('T')[0];
    if (pickupInput) pickupInput.setAttribute('min', today);
    if (returnInput) {
        returnInput.setAttribute('min', today);
        pickupInput?.addEventListener('change', function() {
            returnInput.setAttribute('min', this.value);
        });
    }

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirmation for delete actions
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // Image upload preview
    const imageInput = document.getElementById('car_image');
    const imagePreview = document.getElementById('image_preview');
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
