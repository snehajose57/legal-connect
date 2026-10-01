<!-- Footer -->
<footer class="pt-5 pb-3 mt-auto">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="mb-3"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>LEGAL CONNECT</h5>
                <p>A secure digital ecosystem bridging clients and verified legal practitioners. We prioritize accessibility, data integrity, and modern justice services.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="fs-5 text-warning"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="fs-5 text-warning"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="fs-5 text-warning"><i class="fa-brands fa-linkedin"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/legalconnect/index.php">Home</a></li>
                    <li class="mb-2"><a href="/legalconnect/about.php">About Us</a></li>
                    <li class="mb-2"><a href="/legalconnect/contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="mb-3">Legal Portal</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/legalconnect/login.php">Login / Access Portal</a></li>
                    <li class="mb-2"><a href="/legalconnect/register.php">Register Account</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="mb-3">Contact Support</h5>
                <p class="mb-2"><i class="fa-solid fa-envelope text-warning me-2"></i> support@legalconnect.com</p>
                <p class="mb-2"><i class="fa-solid fa-phone text-warning me-2"></i> +1 (555) 019-2834</p>
                <p class="mb-2"><i class="fa-solid fa-location-dot text-warning me-2"></i> 100 Legal Plaza, Suite 400</p>
            </div>
        </div>
        <div class="footer-bottom mt-4 text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Legal Connect. All Rights Reserved. Designed for Professional Legal Practice.</p>
        </div>
    </div>
</footer>

<!-- Toast/Notification System -->
<?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="liveToast" class="toast align-items-center text-white <?php echo isset($_SESSION['success']) ? 'bg-success' : 'bg-danger'; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa-solid <?php echo isset($_SESSION['success']) ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?> me-2"></i>
                <?php 
                if (isset($_SESSION['success'])) {
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                } else {
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                }
                ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toastEl = document.getElementById('liveToast');
        if (toastEl) {
            setTimeout(function() {
                const toast = new bootstrap.Toast(toastEl);
                toast.hide();
            }, 5000);
        }
    });
</script>
<?php endif; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
