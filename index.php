<?php
$page_title = "Home";
require_once __DIR__ . '/includes/header.php';

// Fetch categories from DB
$categories = [];
try {
    $stmt = $conn->query("SELECT * FROM categories LIMIT 6");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently or fallback
}

// Fetch IPC sections from DB
$ipc_sections = [];
try {
    $stmt = $conn->query("SELECT * FROM ipc_sections LIMIT 5");
    $ipc_sections = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}

// Fetch featured lawyers from DB
$lawyers = [];
try {
    $stmt = $conn->query("SELECT * FROM lawyers WHERE status = 'Approved' LIMIT 3");
    $lawyers = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fail silently
}
?>

<style>
/* Scoped styles for the standard professional landing page */
.hero-wrapper {
    background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 65%, #1e2238 100%);
    color: #ffffff;
    padding: 70px 0;
    position: relative;
    border-bottom: 1px solid rgba(245, 158, 11, 0.2);
}
.hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 4px;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.25);
    color: #f59e0b;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 16px;
}
.hero-main-title {
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1.25;
    color: #ffffff;
    margin-bottom: 14px;
}
.hero-main-title span {
    color: #f59e0b;
}
.hero-desc {
    font-size: 1.05rem;
    color: #cbd5e1;
    line-height: 1.6;
    max-width: 580px;
    margin-bottom: 24px;
}
.hero-img-box {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(245, 158, 11, 0.25);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    background: #111422;
    max-width: 440px;
    margin: 0 auto;
}
.hero-img-box img {
    width: 100%;
    height: 360px;
    object-fit: cover;
    object-position: center top;
    display: block;
}

/* Service & Feature Cards */
.lc-service-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    height: 100%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}
.lc-service-card:hover {
    transform: translateY(-3px);
    border-color: #f59e0b;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}
.lc-service-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: #fef3c7;
    color: #d97706;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 16px;
}

/* How It Works Steps */
.step-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 28px 24px;
    text-align: center;
    height: 100%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}
.step-badge {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #1a1a2e;
    color: #f59e0b;
    font-weight: 700;
    font-size: 1.15rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border: 2px solid #f59e0b;
}

@media (max-width: 991.98px) {
    .hero-main-title {
        font-size: 2rem;
    }
    .hero-img-box img {
        height: 280px;
    }
}
</style>

<!-- 1. HERO SECTION -->
<section class="hero-wrapper">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start">
                <div class="hero-tag">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Legal Connect Platform</span>
                </div>
                <h1 class="hero-main-title">
                    Connect with the Right Lawyer.<br>
                    <span>Get the Right Legal Support.</span>
                </h1>
                <p class="hero-desc">
                    Legal Connect bridges the gap between clients and verified legal practitioners. Search qualified advocates, schedule consultations, ask legal questions, and access Indian Penal Code information with confidence.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                        <a href="/legalconnect/user/lawyers.php" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-2">
                            <i class="fa-solid fa-user-tie me-2"></i>Find a Lawyer
                        </a>
                        <a href="/legalconnect/user/dashboard.php" class="btn btn-outline-light btn-lg fw-semibold px-4 py-2">
                            <i class="fa-solid fa-gauge me-2"></i>My Dashboard
                        </a>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'lawyer'): ?>
                        <a href="/legalconnect/lawyer/dashboard.php" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-2">
                            <i class="fa-solid fa-gauge me-2"></i>Lawyer Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/legalconnect/login.php" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-2">
                            <i class="fa-solid fa-user-tie me-2"></i>Find a Lawyer
                        </a>
                        <a href="/legalconnect/register.php" class="btn btn-outline-light btn-lg fw-semibold px-4 py-2">
                            <i class="fa-solid fa-user-plus me-2"></i>Get Started / Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <div class="hero-img-box">
                    <img src="/legalconnect/assets/images/lady_justice.jpg" alt="Lady Justice Statue - Legal Connect">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. INTRODUCTION / ABOUT SECTION ("Why Legal Connect?") -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-2">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="pe-lg-3">
                    <span class="text-warning fw-bold text-uppercase small tracking-wider">About Our System</span>
                    <h2 class="fw-bold text-dark mt-1 mb-3">Why Legal Connect?</h2>
                    <p class="text-muted mb-3" style="line-height: 1.65;">
                        Legal Connect is a comprehensive online legal support and consultation management system. We connect clients with verified legal advocates across multiple practice specializations, providing transparent, accessible, and structured legal assistance.
                    </p>
                    <p class="text-muted mb-0" style="line-height: 1.65;">
                        From booking appointments and real-time messaging to asking direct legal questions and exploring IPC sections, our platform makes legal consultation straightforward and trustworthy.
                    </p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-shield-halved text-warning fs-5"></i>
                                <h6 class="fw-bold text-dark mb-0">Verified Advocates</h6>
                            </div>
                            <p class="text-muted small mb-0">Every registered lawyer is vetted with valid qualifications and BAR certificates.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-calendar-check text-warning fs-5"></i>
                                <h6 class="fw-bold text-dark mb-0">Easy Scheduling</h6>
                            </div>
                            <p class="text-muted small mb-0">Book consultation slots that suit your calendar with real-time status tracking.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-circle-question text-warning fs-5"></i>
                                <h6 class="fw-bold text-dark mb-0">Legal Q&amp;A Inquiries</h6>
                            </div>
                            <p class="text-muted small mb-0">Submit specific case queries directly to advocates and review answers.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 border rounded-3 bg-light h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-lock text-warning fs-5"></i>
                                <h6 class="fw-bold text-dark mb-0">Secure Consultation</h6>
                            </div>
                            <p class="text-muted small mb-0">Transparent billing, secure user accounts, and dedicated chat support.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. KEY SERVICES SECTION -->
<section class="py-5" style="background-color: #f8f7f4;">
    <div class="container py-2">
        <div class="text-center mb-5">
            <span class="text-warning fw-bold text-uppercase small tracking-wider">Platform Capabilities</span>
            <h2 class="fw-bold text-dark mt-1">Our Key Services</h2>
            <div class="mx-auto bg-warning mt-2" style="height: 3px; width: 50px;"></div>
            <p class="text-muted mt-2 small">Everything you need to find advocates and receive reliable legal support</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="lc-service-card">
                    <div class="lc-service-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Find Lawyers</h5>
                    <p class="text-muted small mb-0">Search and filter certified advocates by legal specialization, qualification, and years of experience.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lc-service-card">
                    <div class="lc-service-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Book Appointments</h5>
                    <p class="text-muted small mb-0">Schedule appointments with preferred time slots and track approval status directly in your portal.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lc-service-card">
                    <div class="lc-service-icon">
                        <i class="fa-solid fa-book-bookmark"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Legal Information</h5>
                    <p class="text-muted small mb-0">Browse key Indian Penal Code (IPC) sections and essential legal reference materials for public awareness.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lc-service-card">
                    <div class="lc-service-icon">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Ask Legal Questions</h5>
                    <p class="text-muted small mb-0">Submit case-specific questions to advocates and receive structured legal responses in your dashboard.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lc-service-card">
                    <div class="lc-service-icon">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Chat with Lawyers</h5>
                    <p class="text-muted small mb-0">Engage in direct consultation chat with your scheduled advocate for follow-ups and discussions.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="lc-service-card">
                    <div class="lc-service-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Secure Payments</h5>
                    <p class="text-muted small mb-0">Transparent consultation fees with integrated payment processing and clear refund policies.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. HOW IT WORKS (3 Simple Steps) -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-2">
        <div class="text-center mb-5">
            <span class="text-warning fw-bold text-uppercase small tracking-wider">Step-By-Step Guide</span>
            <h2 class="fw-bold text-dark mt-1">How It Works</h2>
            <div class="mx-auto bg-warning mt-2" style="height: 3px; width: 50px;"></div>
            <p class="text-muted mt-2 small">Get connected with an advocate in 3 easy steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">1</div>
                    <h5 class="fw-bold text-dark mb-2">Find a Lawyer</h5>
                    <p class="text-muted small mb-0">Browse our directory of verified advocates based on specialization, qualification, and client ratings.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge" style="background:#f59e0b; color:#1a1a2e; border-color:#d97706;">2</div>
                    <h5 class="fw-bold text-dark mb-2">Book a Consultation</h5>
                    <p class="text-muted small mb-0">Select an available appointment date and time, provide brief case details, and complete the booking.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">3</div>
                    <h5 class="fw-bold text-dark mb-2">Get Legal Support</h5>
                    <p class="text-muted small mb-0">Communicate with your lawyer, ask legal questions, and receive professional case assistance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. PRACTICE SPECIALIZATIONS -->
<section class="py-5" style="background-color: #f8f7f4;">
    <div class="container py-2">
        <div class="text-center mb-5">
            <span class="text-warning fw-bold text-uppercase small tracking-wider">Practice Areas</span>
            <h2 class="fw-bold text-dark mt-1">Legal Categories We Support</h2>
            <div class="mx-auto bg-warning mt-2" style="height: 3px; width: 50px;"></div>
        </div>
        <div class="row g-4">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card feature-card p-4 border bg-white rounded-3">
                            <div class="card-icon">
                                <?php 
                                $icon = 'fa-scale-balanced';
                                if (stripos($cat['category_name'], 'criminal') !== false) $icon = 'fa-building-shield';
                                elseif (stripos($cat['category_name'], 'civil') !== false) $icon = 'fa-people-arrows';
                                elseif (stripos($cat['category_name'], 'cyber') !== false) $icon = 'fa-laptop-code';
                                elseif (stripos($cat['category_name'], 'family') !== false) $icon = 'fa-children';
                                elseif (stripos($cat['category_name'], 'corporate') !== false) $icon = 'fa-briefcase';
                                elseif (stripos($cat['category_name'], 'property') !== false) $icon = 'fa-house-chimney-crack';
                                ?>
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($cat['category_name']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($cat['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted">No legal categories available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 6. FEATURED ATTORNEYS -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-2">
        <div class="text-center mb-5">
            <span class="text-warning fw-bold text-uppercase small tracking-wider">Our Advocates</span>
            <h2 class="fw-bold text-dark mt-1">Featured Legal Practitioners</h2>
            <div class="mx-auto bg-warning mt-2" style="height: 3px; width: 50px;"></div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <?php if (!empty($lawyers)): ?>
                <?php foreach ($lawyers as $lawyer): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card feature-card p-0 text-center overflow-hidden border rounded-3">
                            <div class="bg-dark p-4 position-relative">
                                <?php if ($lawyer['profile_image'] && $lawyer['profile_image'] !== 'default_lawyer.png'): ?>
                                    <img src="/legalconnect/uploads/profiles/<?php echo htmlspecialchars($lawyer['profile_image']); ?>" alt="Profile" class="rounded-circle border border-white border-3 shadow" style="width: 90px; height: 90px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle mx-auto text-dark bg-warning" style="width: 80px; height: 80px; font-size: 1.8rem;"><?php echo substr($lawyer['name'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($lawyer['name']); ?></h5>
                                <span class="badge bg-warning text-dark mb-3"><?php echo htmlspecialchars($lawyer['specialization']); ?></span>
                                <p class="text-muted small mb-1"><i class="fa-solid fa-graduation-cap me-2 text-warning"></i><?php echo htmlspecialchars($lawyer['qualification']); ?></p>
                                <p class="text-muted small mb-3"><i class="fa-solid fa-briefcase me-2 text-warning"></i><?php echo htmlspecialchars($lawyer['experience']); ?>+ Years Experience</p>
                                <a href="/legalconnect/login.php" class="btn btn-dark w-100 rounded-pill btn-sm">Consult Advocate</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-lg-6 text-center">
                    <div class="p-4 rounded-3 bg-light border">
                        <i class="fa-solid fa-user-tie fs-2 text-muted mb-2"></i>
                        <h6 class="fw-bold">Advocates Onboarding</h6>
                        <p class="text-muted small mb-3">Our verified advocate network is growing. Register as a lawyer to join or as a client to get notified.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="/legalconnect/register.php" class="btn btn-sm btn-warning fw-bold text-dark">Join as Advocate</a>
                            <a href="/legalconnect/register.php" class="btn btn-sm btn-outline-dark">Join as Client</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 7. IPC INFORMATION HIGHLIGHTS -->
<section class="py-5" style="background-color: #f8f7f4;">
    <div class="container py-2">
        <div class="text-center mb-5">
            <span class="text-warning fw-bold text-uppercase small tracking-wider">Legal Awareness</span>
            <h2 class="fw-bold text-dark mt-1">Indian Penal Code (IPC) Highlights</h2>
            <div class="mx-auto bg-warning mt-2" style="height: 3px; width: 50px;"></div>
            <p class="text-muted mt-2 small">Essential legal sections and civic rights for citizens</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-8 mx-auto">
                <div class="accordion shadow-sm" id="ipcAccordion">
                    <?php if (!empty($ipc_sections)): ?>
                        <?php foreach ($ipc_sections as $index => $ipc): ?>
                            <div class="accordion-item border mb-2 rounded overflow-hidden">
                                <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                    <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?> fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                        <span class="badge bg-dark text-warning me-3"><?php echo htmlspecialchars($ipc['section_number']); ?></span> 
                                        <?php echo htmlspecialchars($ipc['title']); ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#ipcAccordion">
                                    <div class="accordion-body bg-white border-top text-muted small">
                                        <?php echo nl2br(htmlspecialchars($ipc['description'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">No IPC information available at the moment.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. FINAL CALL-TO-ACTION SECTION -->
<section class="py-5" style="background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%); color: #ffffff; border-top: 1px solid rgba(245, 158, 11, 0.2);">
    <div class="container text-center py-3">
        <h2 class="fw-bold text-white mb-2">Need Legal Support?</h2>
        <p class="text-light opacity-90 mb-4" style="max-width: 580px; margin: 0 auto; font-size: 1.05rem;">
            Find the right legal professional through Legal Connect. Register as a client to book consultations or join our verified advocate network.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="/legalconnect/register.php" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-2">
                <i class="fa-solid fa-user-plus me-2"></i>Create Free Account
            </a>
            <a href="/legalconnect/login.php" class="btn btn-outline-light btn-lg fw-semibold px-4 py-2">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Access Portal
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
