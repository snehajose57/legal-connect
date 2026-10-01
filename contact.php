<?php
$page_title = "Contact Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-dark py-5 text-light text-center" style="background: linear-gradient(135deg, #0a192f 0%, #172a45 100%);">
    <div class="container py-3">
        <h1 class="fw-bold"><i class="fa-solid fa-envelope-open-text text-warning me-2"></i>Contact Our Support Team</h1>
        <p class="lead text-white-50">Got questions about registration, billing, or technical queries? We are here to help.</p>
    </div>
</div>

<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-5">
            <!-- Left Info column -->
            <div class="col-lg-4">
                <div class="p-4 rounded-4 bg-light shadow-sm h-100">
                    <h4 class="fw-bold text-dark mb-4">Contact Information</h4>
                    
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="p-3 bg-white rounded-circle shadow-sm text-warning"><i class="fa-solid fa-phone fs-5"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Call Us</h6>
                            <p class="text-muted mb-0">+1 (555) 019-2834</p>
                            <p class="text-muted small">Mon - Fri, 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="p-3 bg-white rounded-circle shadow-sm text-warning"><i class="fa-solid fa-envelope fs-5"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Email Us</h6>
                            <p class="text-muted mb-0">support@legalconnect.com</p>
                            <p class="text-muted small">Response within 24 business hours</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start gap-3">
                        <div class="p-3 bg-white rounded-circle shadow-sm text-warning"><i class="fa-solid fa-location-dot fs-5"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Visit Office</h6>
                            <p class="text-muted mb-0">100 Legal Plaza, Suite 400</p>
                            <p class="text-muted small">New York, NY 10001</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Form column -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                    <h4 class="fw-bold mb-3">Send a Message</h4>
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contact_name" class="form-label fw-bold">Full Name</label>
                                <input type="text" class="form-control" id="contact_name" name="name" required placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_email" class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control" id="contact_email" name="email" required placeholder="john@example.com">
                            </div>
                            <div class="col-12">
                                <label for="contact_subject" class="form-label fw-bold">Subject</label>
                                <input type="text" class="form-control" id="contact_subject" name="subject" required placeholder="Technical issues with registration">
                            </div>
                            <div class="col-12">
                                <label for="contact_message" class="form-label fw-bold">Message</label>
                                <textarea class="form-control" id="contact_message" name="message" rows="6" required placeholder="Type details here..."></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-gold px-4" name="send_message">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php
                    if (isset($_POST['send_message'])) {
                        echo '<div class="alert alert-success mt-4 mb-0 alert-dismissible fade show" role="alert">
                                <strong>Message Sent!</strong> Thank you for contacting us. We will reply to your registered email soon.
                                <button type="button" class="btn-close" data-bs-dismiss="close" aria-label="Close"></button>
                              </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
