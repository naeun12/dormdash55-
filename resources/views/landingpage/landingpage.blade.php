@include('landingpage.partials.navigation')
<main>

  <section id="home">
    <div class="container-fluid px-0 position-relative vh-100 overflow-hidden">
        <img src="{{ asset('images/landingpage/istockphoto-1276470198-612x612.jpg') }}" 
             alt="Modern Dormitory"
             class="w-100 h-100 position-absolute top-0 start-0 hero-bg-zoom" 
             style="object-fit: cover; filter: brightness(0.35);">

        <div class="position-absolute top-0 start-0 w-100 h-100"
             style="background: radial-gradient(circle at center, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.8) 100%); z-index: 1;">
        </div>

        <div class="position-relative z-2 h-100 d-flex align-items-center justify-content-center px-3">
            <div class="glass-card p-4 p-md-5 rounded-5 shadow-2xl text-center" style="max-width: 800px;">
                
                <span class="badge rounded-pill mb-3 px-3 py-2 text-uppercase tracking-wider" 
                      style="background: rgba(252, 125, 7, 0.15); color: #FC7D07; border: 1px solid rgba(252, 125, 7, 0.3); letter-spacing: 1px;">
                    Fast & Reliable Housing
                </span>

                <h1 class="fw-bold mb-3 text-white display-3">
                    Welcome to <span style="color: #003C87;">Dorm</span><span style="color: #FC7D07;">Dash</span>
                </h1>

                <p class="lead mb-4 text-white-50 mx-auto" style="max-width: 600px; font-size: 1.1rem;">
                    The ultimate management bridge for modern living in <strong>Surigao City</strong>
                </p>

                <div class="divider-brand mx-auto mb-4"></div>

                <p class="mb-3 small text-uppercase fw-bold text-white-50 tracking-widest">Join the Community</p>

                <div class="d-flex gap-3 flex-column flex-sm-row justify-content-center">
                    <a href="{{ route('landlord-Login') }}"
                       class="btn-modern btn-brand-blue">
                        <i class="bi bi-house-door me-2"></i> Property Owner
                    </a>
                    <a href="{{ route('login-tenant') }}"
                       class="btn-modern btn-brand-orange">
                        <i class="bi bi-person me-2"></i> Resident
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>


    <!-- Benefits Section -->
    <div class="container position-relative" style="margin-top: -100px; z-index: 5;">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-white mb-2">Why Choose <span style="color: #003C87;">Dorm</span><span style="color: #FC7D07;">Dash?</span> </h2>
        <div class="divider-brand mx-auto"></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card benefit-card shadow-lg border-0 rounded-4 h-100 text-center p-4">
                <div class="icon-box mb-3 mx-auto color-blue">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h5 class="fw-bold color-blue">Easy Search</h5>
                <p class="text-muted small">Quickly find dormitories around Surigao City with filters for price, location, and amenities.</p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card benefit-card shadow-lg border-0 rounded-4 h-100 text-center p-4">
                <div class="icon-box mb-3 mx-auto color-orange">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <h5 class="fw-bold color-orange">Affordable Options</h5>
                <p class="text-muted small">Access budget-friendly dorms suited for students and workers with transparent pricing and no hidden fees.</p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card benefit-card shadow-lg border-0 rounded-4 h-100 text-center p-4">
                <div class="icon-box mb-3 mx-auto color-blue">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="fw-bold color-blue">Safe & Verified</h5>
                <p class="text-muted small">All listings undergo a verification process to ensure secure, legitimate, and trustworthy accommodations.</p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card benefit-card shadow-lg border-0 rounded-4 h-100 text-center p-4">
                <div class="icon-box mb-3 mx-auto color-orange">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <h5 class="fw-bold color-orange">Smart Booking</h5>
                <p class="text-muted small">Easily reserve your preferred room or instantly book your stay with our streamlined, hassle-free system.</p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card benefit-card shadow-lg border-0 rounded-4 h-100 text-center p-4">
                <div class="icon-box mb-3 mx-auto color-blue">
                    <i class="bi bi-laptop"></i>
                </div>
                <h5 class="fw-bold color-blue">User-Friendly</h5>
                <p class="text-muted small">Experience a high-performance web platform designed for speed and ease of use on any device.</p>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
            <div class="card benefit-card shadow-lg border-0 rounded-4 h-100 text-center p-4">
                <div class="icon-box mb-3 mx-auto color-orange">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h5 class="fw-bold color-orange">Community Support</h5>
                <p class="text-muted small">Connect directly with property owners and co-tenants, fostering a safe and supportive living environment.</p>
            </div>
        </div>
    </div>
</div>


    <!-- Available Dorms Section -->
 <div class="container mt-5 py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-uppercase position-relative d-inline-block pb-2" style="color: #003C87; letter-spacing: 1px;">
            <i class="bi bi-building-heart me-2"></i>Available Dorms
            <span class="position-absolute bottom-0 start-0 w-100" style="height: 3px; background: linear-gradient(to right, #003C87, #FC7D07); border-radius: 2px;"></span>
        </h2>
        <p class="text-muted mt-3">Discover premium living spaces verified for your comfort and safety.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 border-0 shadow-lg rounded-4 overflow-hidden featured-dorm-card">
                <div class="position-relative h-100">
                    <img src="{{ asset('images/landingpage/dorm1.jpg') }}" class="w-100 h-100 zoom-img" alt="Sunshine Dormitory" style="object-fit: cover; min-height: 450px;">
                    
                    <div class="position-absolute top-0 start-0 m-3">
                        <span class="badge glass-badge px-3 py-2 text-white">
                            <i class="bi bi-star-fill text-warning me-1"></i> 4.8 Featured
                        </span>
                    </div>

                    <div class="card-img-overlay d-flex flex-column justify-content-end p-4 bg-gradient-brand">
                        <div class="text-white">
                            <h3 class="fw-bold mb-1">Sunshine Dormitory</h3>
                            <p class="mb-3 opacity-75"><i class="bi bi-geo-alt-fill me-1"></i> San Juan, Surigao City</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0 fw-bold">₱4,500 <small class="fw-light">/mo</small></span>
                                <button type="button" class="btn btn-brand-orange rounded-pill px-4 shadow" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    View Details <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden small-dorm-card" style="height: 230px;">
                        <img src="{{ asset('images/landingpage/dorm2.webp') }}" class="w-100 h-100 zoom-img" alt="Palm Grove" style="object-fit: cover;">
                        <div class="card-img-overlay d-flex flex-column justify-content-end bg-gradient-brand">
                            <div class="d-flex justify-content-between align-items-end text-white w-100">
                                <div>
                                    <h5 class="fw-bold mb-0">Palm Grove Dorm</h5>
                                    <p class="small mb-0 opacity-75">Surigao City</p>
                                </div>
                                <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#exampleModal">Explore</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden small-dorm-card" style="height: 200px;">
                        <img src="{{ asset('images/landingpage/dorm4.jpg') }}" class="w-100 h-100 zoom-img" alt="Lapu Cozy" style="object-fit: cover;">
                        <div class="card-img-overlay d-flex flex-column justify-content-end bg-gradient-brand p-3">
                            <h6 class="fw-bold text-white mb-0">Lapu Cozy</h6>
                            <p class="x-small text-white opacity-75 mb-0">⭐ 4.7 • Libuac</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-6">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden small-dorm-card" style="height: 200px;">
                        <img src="{{ asset('images/landingpage/dorm3.jpg') }}" class="w-100 h-100 zoom-img" alt="Cityside" style="object-fit: cover;">
                        <div class="card-img-overlay d-flex flex-column justify-content-end bg-gradient-brand p-3">
                            <h6 class="fw-bold text-white mb-0">Cityside Stay</h6>
                            <p class="x-small text-white opacity-75 mb-0">⭐ 4.5 • Alang-alang</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        <!-- Modal -->
       <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div style="height: 6px; background: linear-gradient(90deg, #003C87 0%, #FC7D07 100%);"></div>
            
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="exampleModalLabel" style="color: #003C87;">
                    Ready to find your home?
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center py-4 px-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4 shadow-sm" 
                     style="width: 80px; height: 80px; background-color: rgba(252, 125, 7, 0.1);">
                    <i class="bi bi-door-open-fill" style="color: #FC7D07; font-size: 2.5rem;"></i>
                </div>
                
                <h6 class="fw-bold mb-2" style="color: #000;">Authentication Required</h6>
                <p class="text-muted px-2" style="font-size: 15px; line-height: 1.6;">
                    To view full dormitory details and contact owners, please join the <strong style="color: #003C87;">DormDash</strong> community.
                </p>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 d-flex flex-column gap-2">
                <a href="{{ route('login-tenant') }}" class="btn w-100 py-3 rounded-3 text-white fw-bold shadow-sm auth-btn-primary" 
                   style="background-color: #003C87; transition: 0.3s;">
                    Sign Up / Log In
                </a>
                
                <button type="button" class="btn w-100 py-2 border-0 text-muted small fw-semibold" data-bs-dismiss="modal">
                    Maybe Later
                </button>
            </div>
        </div>
    </div>
</div>
    </div>
</section>

<!-- Optional Custom Styles -->



    <!-- About Us Section -->
   <section class="about-us section py-5" data-aos="fade-up" id="about-us" style="background-color: #FFFFFF; overflow: hidden;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            
            <div class="col-lg-6">
                <span class="badge mb-3 px-3 py-2 rounded-pill" style="background-color: rgba(0, 60, 135, 0.1); color: #003C87; font-weight: 600; letter-spacing: 0.5px;">
                    EST. 2025
                </span>
                
                <h2 class="fw-bold text-uppercase mb-4" style="color: #003C87; border-left: 5px solid #FC7D07; padding-left: 15px;">
                    About <span style="color: #FC7D07;"><span style="color: #003C87;">Dorm</span><span style="color: #FC7D07;">Dash?</span></span>
                </h2>
                
                <p class="lead mb-4" style="color: #333; font-weight: 500; line-height: 1.6;">
                    The premier platform tailored for students searching for 
                    <strong>dormitory accommodations</strong> across Surigao City. We connect students and young professionals with a curated selection of safe, affordable, and conveniently located dorms, ensuring a seamless housing experience.
                </p>

                <p class="text-muted mb-5" style="font-size: 16px;">
                    We understand that finding a home away from home is about more than just a bed—it's about safety, 
                    proximity to campus, and peace of mind. Our mission is to bridge the gap between students and the perfect living space.
                </p>

                <div class="row g-4 mb-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3 d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px; background-color: #003C87;">
                                <i class="bi bi-geo-alt-fill text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #000;">Smart Mapping</h6>
                                <small class="text-muted">Proximity to schools</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <div class="icon-box me-3 d-flex align-items-center justify-content-center rounded" style="width: 50px; height: 50px; background-color: #FC7D07;">
                                <i class="bi bi-shield-check text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold" style="color: #000;">Verified Units</h6>
                                <small class="text-muted">Safe & secure housing</small>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="#listings" class="btn btn-lg px-4 text-white fw-bold shadow-sm" style="background-color: #003C87; border-radius: 8px; transition: 0.3s;">
                    Browse Dorms
                </a>
            </div>

            <div class="col-lg-6 text-center position-relative">
                <div class="position-absolute top-50 start-50 translate-middle" style="width: 110%; height: 110%; background: radial-gradient(circle, rgba(252,125,7,0.08) 0%, rgba(255,255,255,0) 70%); z-index: -1;"></div>
                
                <div class="image-wrapper p-2 bg-white shadow-lg rounded-4">
                    <img src="{{ asset('images/landingpage/surigao.jpg') }}" class="img-fluid rounded-3" 
                         alt="Map of Surigao showing dormitory locations" style="display: block; height: 100%; width: 100%;">
                </div>
                
                <div class="position-absolute bottom-0 start-0 mb-4 ms-n2 p-3 bg-white shadow rounded-3 d-none d-md-flex align-items-center border-start border-4" style="border-color: #FC7D07 !important;">
                    <i class="bi bi-people-fill fs-3 me-2" style="color: #003C87;"></i>
                    <div class="text-start">
                        <p class="mb-0 fw-bold" style="font-size: 14px; color: #000;">Community First</p>
                        <small class="text-muted">Built for Students and Young Professionals</small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
    <!-- Contact Section -->
    <section class="contact py-5 section" id="contact-us" data-aos="fade-up" style="background-color: #fcfcfc;">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-uppercase fw-bold" style="color: #FC7D07; letter-spacing: 2px; font-size: 14px;">Get In Touch</span>
            <h3 class="display-5 fw-bold mt-2" style="color: #003C87;">
                Contact Us
            </h3>
            <div class="mx-auto mt-2" style="width: 60px; height: 4px; background-color: #FC7D07; border-radius: 2px;"></div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3">
                    <div class="p-4 bg-white shadow-sm rounded-4 border-start border-4" style="border-color: #003C87 !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle shadow-sm d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 50px; height: 50px; background-color: rgba(0, 60, 135, 0.1);">
                                <i class="bi bi-envelope-open-fill" style="color: #003C87;"></i>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small text-uppercase fw-bold">Email Us</p>
                                <p class="mb-0 fw-bold" style="font-size: 0.95rem;">kassandraolvis@gmail.com</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-white shadow-sm rounded-4 border-start border-4" style="border-color: #FC7D07 !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle shadow-sm d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 50px; height: 50px; background-color: rgba(252, 125, 7, 0.1);">
                                <i class="bi bi-telephone-outbound-fill" style="color: #FC7D07;"></i>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small text-uppercase fw-bold">Call Anytime</p>
                                <p class="mb-0 fw-bold" style="font-size: 0.95rem;">+63 9062215117</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-white shadow-sm rounded-4 border-start border-4" style="border-color: #003C87 !important;">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle shadow-sm d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 50px; height: 50px; background-color: rgba(0, 60, 135, 0.1);">
                                <i class="bi bi-clock-history" style="color: #003C87;"></i>
                            </div>
                            <div>
                                <p class="mb-0 text-muted small text-uppercase fw-bold">Working Hours</p>
                                <p class="mb-0 fw-bold" style="font-size: 0.95rem;">Mon – Fri, 9AM – 5PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="bg-white p-4 p-md-5 shadow rounded-4">
                    <form action="{{ route('send.email') }}" method="GET" class="row g-4">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Full Name</label>
                            <input type="text" name="name" class="form-control bg-light border-0 py-3 px-4 rounded-3"
                                placeholder="Enter your name" required style="font-size: 15px;" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email Address</label>
                            <input type="email" name="email" class="form-control bg-light border-0 py-3 px-4 rounded-3"
                                placeholder="example@email.com" required style="font-size: 15px;" />
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Your Message</label>
                            <textarea name="message" class="form-control bg-light border-0 py-3 px-4 rounded-3" rows="4"
                                placeholder="How can we help you?" required style="font-size: 15px;"></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn px-5 py-3 rounded-3 text-white fw-bold shadow-sm" style="background-color: #003C87; transition: all 0.3s ease;">
                                Send Message <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>




    <!-- FAQs Section -->
    <section class="faq-section py-5 section" id="faqs" data-aos="fade-up" style="background-color: #ffffff;">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-uppercase fw-bold" style="color: #FC7D07; letter-spacing: 2px; font-size: 14px;">Got Questions?</span>
            <h2 class="display-6 fw-bold mt-2" style="color: #003C87;">
                Frequently Asked <span style="color: #FC7D07;">Questions</span>
            </h2>
            <p class="text-muted mx-auto" style="max-width: 500px;">Quick answers to help you navigate your experience with DormDash.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="accordion accordion-custom" id="faqAccordionLeft">
                    
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" style="color: #003C87; background-color: #f8f9fa;">
                                <i class="bi bi-rocket-takeoff-fill me-3" style="color: #FC7D07;"></i> What is DormDash?
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                            <div class="accordion-body bg-white text-muted">
                                <strong>DormDash</strong> is a premier platform designed to help students find safe, affordable, and conveniently located accommodations across Surigao City.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" style="color: #003C87; background-color: #f8f9fa;">
                                <i class="bi bi-search me-3" style="color: #FC7D07;"></i> How do I search for a dorm?
                            </button>
                        </h2>
                        <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                            <div class="accordion-body bg-white text-muted">
                                Simply use the search bar on our homepage or apply filters based on location, budget, and specific amenities you need.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" style="color: #003C87; background-color: #f8f9fa;">
                                <i class="bi bi-chat-dots-fill me-3" style="color: #FC7D07;"></i> How do I contact an owner?
                            </button>
                        </h2>
                        <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordionLeft">
                            <div class="accordion-body bg-white text-muted">
                                Every listing on <strong>DormDash</strong> includes direct contact details. Just click the "Contact Owner" button on the listing page.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-md-6">
                <div class="accordion accordion-custom" id="faqAccordionRight">
                    
                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" style="color: #003C87; background-color: #f8f9fa;">
                                <i class="bi bi-currency-dollar me-3" style="color: #FC7D07;"></i> Is DormDash free?
                            </button>
                        </h2>
                        <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#faqAccordionRight">
                            <div class="accordion-body bg-white text-muted">
                                Yes! <strong>DormDash</strong> is free to use for students. We want to make your housing search as stress-free as possible.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" style="color: #003C87; background-color: #f8f9fa;">
                                <i class="bi bi-shield-lock-fill me-3" style="color: #FC7D07;"></i> Can I book through DormDash?
                            </button>
                        </h2>
                        <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#faqAccordionRight">
                            <div class="accordion-body bg-white text-muted">
                                While we don't handle payments directly, we provide the connection you need to verify the dorm and finalize your booking with the owner.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-0 shadow-sm mb-3 rounded-4 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" style="color: #003C87; background-color: #f8f9fa;">
                                <i class="bi bi-headset me-3" style="color: #FC7D07;"></i> I need more help.
                            </button>
                        </h2>
                        <div id="collapse6" class="accordion-collapse collapse" data-bs-parent="#faqAccordionRight">
                            <div class="accordion-body bg-white text-muted">
                                If you have more questions, reach out via our contact form or email us at <strong>support@dormdash.com</strong>.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>


</main>

<!-- Footer -->
<!-- Footer -->
<footer class="footer text-white py-5 shadow-lg" style="background-color: #003C87; border-top: 6px solid #FC7D07;">
    <div class="container">
        <div class="row gy-5">
            <div class="col-12 col-md-4 text-center text-md-start">
                <h4 class="fw-bold mb-3" style="letter-spacing: 1px;">
                    Dorm<span style="color: #FC7D07;">Dash</span>
                </h4>
                <p class="small opacity-75" style="max-width: 250px; line-height: 1.6;">
                    Connecting students with safe, affordable, and convenient living spaces in Surigao City. Your trusted platform for dormitory accommodations.
                </p>
                <div class="d-flex justify-content-center justify-content-md-start gap-3 mt-4">
                    <a href="https://www.facebook.com/profile.php?id=61580194851438" class="social-icon" target="_blank">
                        <i class="fab fa-facebook-f fs-5"></i>
                    </a>
                    
                </div>
            </div>

            <div class="col-12 col-md-4 text-center">
                <h5 class="fw-bold mb-4" style="color: #FC7D07;">Quick Navigation</h5>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                    <li><a href="#about-us" class="footer-link">About Our Mission</a></li>
                    <li><a href="#listings" class="footer-link">Browse Dormitories</a></li>
                    <li><a href="#contact-us" class="footer-link">Get In Touch</a></li>
                    <li><a href="#faqs" class="footer-link">Help & FAQs</a></li>
                </ul>
            </div>

            <div class="col-12 col-md-4 text-center text-md-end">
                <h5 class="fw-bold mb-4" style="color: #FC7D07;">Contact Info</h5>
                <p class="mb-2 opacity-75">
                    <i class="bi bi-geo-alt-fill me-2"></i> Surigao City, Philippines
                </p>
                <p class="mb-0">
                    <i class="bi bi-envelope-at-fill me-2"></i>
                    <a href="mailto:support@dormdash.com" class="text-white text-decoration-none border-bottom border-secondary">
                        support@dormdash.com
                    </a>
                </p>
            </div>
        </div>

        <hr class="my-5 opacity-25" style="background-color: #FFFFFF;">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small opacity-75">
            <p class="mb-0">&copy; 2025 <span class="fw-bold">DormDash</span>. Built for Students and Young Professional.</p>
            <div class="d-flex gap-3 mt-3 mt-md-0">
                <a href="#" class="text-white text-decoration-none">Privacy Policy</a>
                <a href="#" class="text-white text-decoration-none">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
<style>
    @media (max-width: 576px) {
        .hover-card h5 { font-size: 1rem; }
        .hover-card p { font-size: 0.85rem; }
        .btn { font-size: 0.9rem; }
    }
    </style

@include('landingpage.partials.footer')
