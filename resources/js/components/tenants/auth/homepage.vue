<template>
    <NotificationList ref="toastRef" />
    <!-- Floating Button -->

    <!-- <div class="offcanvas offcanvas-start rounded-end-4 shadow-lg border-0" data-bs-backdrop="static" tabindex="-1"
        id="staticBackdrop" aria-labelledby="staticBackdropLabel" style="width: 400px;">

        <div class="offcanvas-header text-white shadow-sm py-3 px-4" style="background-color: #003C87;">
            <h5 class="offcanvas-title fw-bold d-flex align-items-center" id="staticBackdropLabel">
                <div class="p-2 bg-white bg-opacity-10 rounded-3 me-3">
                    <i class="bi bi-star-fill text-warning fs-5"></i>
                </div>
                Recommended for You
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-4 bg-light">
            <div v-if="loading" class="d-flex justify-content-center align-items-center" style="height: 300px;">
                <div class="spinner-grow text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div v-else class="d-flex flex-column gap-4 overflow-auto pe-2" style="max-height: calc(100vh - 120px);">

                <div v-for="(dorm, index) in genderPersonalized" :key="index"
                    class="card dorm-card shadow-sm border-0 rounded-4 overflow-hidden transition-all hover-translate-y bg-white">

                    <div class="position-relative">
                        <img :src="dorm?.images?.mainImage || dorm?.mainImage || 'https://via.placeholder.com/400x250'"
                            class="card-img-top object-fit-cover shadow-inner" style="height: 220px;">

                        <div class="position-absolute bottom-0 start-0 m-3 px-3 py-2 rounded-3 shadow-sm text-white fw-bold fs-5"
                            style="background-color: #FC7D07; backdrop-filter: blur(2px);">
                            ₱{{dorm?.rooms?.length ? Math.min(...dorm.rooms.map(r => r.price)).toLocaleString('en-US',
                                {minimumFractionDigits: 2}) : 'N/A' }}
                        </div>
                    </div>

                    <div class="card-body p-4 d-flex flex-column gap-3">
                        <div>
                            <h5 class="fw-bold text-dark text-truncate mb-1">{{ dorm.dormName }}</h5>
                            <p class="text-muted small mb-0 d-flex align-items-center text-truncate">
                                <i class="bi bi-geo-alt-fill text-danger me-2"></i>{{ dorm.address || 'Location unavailable' }}
                            </p>
                        </div>

                        <div class="row g-2 p-2 rounded-3 bg-light border">
                            <div class="col-12 d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted small-caps fw-bold">Occupancy</small>
                                <span class="badge rounded-pill px-2 py-1" :class="{
                                    'bg-primary bg-opacity-10 text-primary': dorm.occupancyType.includes('Male'),
                                    'bg-danger bg-opacity-10 text-danger': dorm.occupancyType.includes('Female'),
                                    'bg-warning bg-opacity-10 text-dark': dorm.occupancyType.includes('Mixed')
                                }">
                                    <i class="bi bi-people-fill me-1"></i>{{ dorm.occupancyType || 'Unspecified' }}
                                </span>
                            </div>

                            <div class="col-12 border-top pt-2">
                                <small class="text-muted small-caps fw-bold d-block mb-2">Highlights</small>
                                <div class="d-flex flex-wrap gap-1">
                                    <template v-if="dorm.amenities && dorm.amenities.length > 0">
                                        <span v-for="amenity in dorm.amenities.slice(0, 4)" :key="amenity.id"
                                            class="badge rounded-pill px-2 py-1 text-truncate transition-all"
                                            :class="tenant.preferred_amenities.includes(amenity.id) ? 'bg-success text-white' : 'bg-white text-secondary border'">
                                            {{ amenity.aminityName }}
                                        </span>
                                    </template>
                                    <template
                                        v-if="dorm.rooms && dorm.rooms.some(r => r.features && r.features.length > 0)">
                                        <template v-for="room in dorm.rooms">
                                            <span v-for="feature in room.features.slice(0, 2)" :key="feature.id"
                                                class="badge rounded-pill px-2 py-1 text-truncate transition-all"
                                                :class="tenant.preferred_features && tenant.preferred_features.includes(feature.id) ? 'bg-success text-white' : 'bg-white text-secondary border'">
                                                {{ feature.featureName }}
                                            </span>
                                        </template>
                                    </template>
                                    <span
                                        v-if="(!dorm.amenities || dorm.amenities.length === 0) && (!dorm.rooms || !dorm.rooms.some(r => r.features))"
                                        class="text-muted small">None listed</span>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-lg w-100 rounded-pill fw-bold text-white shadow-sm mt-2 transition-all"
                            style="background-color: #003C87;" @click="viewDorms(dorm.dormID)">
                            <i class="bi bi-info-circle me-2"></i>View Property Details
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div> -->

    <!-- PERSONALIZE MODAL -->
    <div
        v-if="afterpersonalized"
        class="welcome-section position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
        style="
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1050;
            padding: 1rem;
        "
    >
        <!-- Welcome message -->
        <div
            v-if="showafterWelcome"
            class="welcome-container py-5 animate__animated animate__fadeIn"
        >
            <div
                class="card welcome-card border-0 shadow-lg mx-auto text-center overflow-hidden"
            >
                <div class="accent-bar"></div>

                <div class="card-body p-4 p-md-5">
                    <div class="success-icon-wrapper mb-4">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <h1 class="fw-bold h2 mb-2 text-dark">
                        Preferences <span class="text-dash-blue">Updated!</span>
                    </h1>
                    <p class="text-muted mb-4">
                        We've tailored your experience based on what you need.
                    </p>

                    <hr class="my-4 opacity-25" />

                    <div class="row g-3 mb-4 justify-content-center">
                        <div class="col-6 col-md-5">
                            <div class="preference-pill">
                                <small
                                    class="d-block text-uppercase text-muted fw-bold"
                                    >Location</small
                                >
                                <span class="fw-bold text-dash-blue">{{
                                    preferredLocation
                                }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-5">
                            <div class="preference-pill">
                                <small
                                    class="d-block text-uppercase text-muted fw-bold"
                                    >Budget Limit</small
                                >
                                <span class="fw-bold text-dash-orange"
                                    >₱{{ preferredPrice }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 animate__animated animate__pulse animate__infinite"
                    >
                        <p class="lead fw-semibold text-dark mb-0">
                            Ready to find your
                            <span class="text-dash-blue">DormDash</span> home?
                        </p>
                        <i
                            class="bi bi-chevron-double-down text-dash-orange"
                        ></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="!isPersonalized">
        <div
            v-if="showWelcome"
            class="welcome-section position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center"
            style="background-color: rgba(0, 0, 0, 0.5); z-index: 1050"
        >
            <!-- Loading Spinner -->

            <!-- Welcome message -->
            <div v-if="showWelcome" class="hero-section text-center py-5">
                <div
                    class="d-flex flex-wrap justify-content-center align-items-center gap-2 mb-4 animate-fade-in"
                >
                    <h1
                        class="display-3 fw-extrabold text-white mb-0 tracking-tight"
                    >
                        Welcome to
                    </h1>
                    <h1
                        class="display-3 fw-extrabold mb-0 tracking-tight glow-text"
                        style="color: #fc7d07"
                    >
                        DormDash!
                    </h1>
                </div>

                <p
                    class="lead text-white opacity-90 mb-5 fs-4 fw-medium animate-fade-in-delayed mx-auto"
                    style="max-width: 600px"
                >
                    Find your perfect dormitory and book your room
                    <span class="text-info border-bottom border-2 border-info"
                        >hassle-free</span
                    >.
                </p>

                <div
                    class="d-flex justify-content-center align-items-center gap-4 mt-5 sequential-container"
                >
                    <transition name="slide-up">
                        <div
                            v-if="showText[0]"
                            class="step-card d-flex align-items-center gap-3 px-4 py-3 rounded-4 shadow-sm"
                        >
                            <div class="step-number">1</div>
                            <span class="fs-5 fw-bold text-white"
                                >Find Your Dorm</span
                            >
                        </div>
                    </transition>

                    <transition name="fade">
                        <i
                            v-if="showText[1]"
                            class="bi bi-chevron-right text-white fs-4 opacity-50 d-none d-md-block"
                        ></i>
                    </transition>

                    <transition name="slide-up">
                        <div
                            v-if="showText[1]"
                            class="step-card d-flex align-items-center gap-3 px-4 py-3 rounded-4 shadow-sm highlight-step"
                        >
                            <div class="step-number">2</div>
                            <span class="fs-5 fw-bold text-white"
                                >Book Now</span
                            >
                        </div>
                    </transition>
                </div>
            </div>
        </div>

        <div
            v-if="showModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="
                background-color: rgba(0, 44, 100, 0.4);
                backdrop-filter: blur(4px);
            "
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div
                    class="modal-content rounded-5 shadow-lg border-0 overflow-hidden"
                >
                    <div
                        class="modal-header border-0 p-4 text-white"
                        style="
                            background: linear-gradient(
                                135deg,
                                #003c87 0%,
                                #002554 100%
                            );
                        "
                    >
                        <div class="d-flex align-items-center">
                            <div
                                class="bg-white text-black bg-opacity-20 p-2 rounded-3 me-3"
                            >
                                <i class="bi bi-sliders2-vertical fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">
                                    Personalize Recommendations
                                </h5>
                                <small class="opacity-75"
                                    >Tell us what you're looking for in a
                                    home</small
                                >
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close btn-close-white shadow-none"
                            @click="showModal = false"
                        ></button>
                    </div>

                    <div class="modal-body p-4 p-lg-5 bg-white">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label
                                    class="form-label fw-bold text-dark mb-3"
                                >
                                    <i
                                        class="bi bi-wallet2 me-2 text-primary"
                                    ></i
                                    >Monthly Budget
                                </label>
                                <div
                                    class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden border"
                                >
                                    <span
                                        class="input-group-text bg-white border-0 ps-3 fw-bold text-muted"
                                        >₱</span
                                    >
                                    <input
                                        type="number"
                                        v-model.number="preferredPrice"
                                        class="form-control border-0 fs-6"
                                        placeholder="Maximum price..."
                                    />
                                </div>
                                <div class="form-text mt-2 ps-1">
                                    We'll show rooms within this range.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label
                                    class="form-label fw-bold text-dark mb-3"
                                >
                                    <i
                                        class="bi bi-geo-alt me-2 text-primary"
                                    ></i
                                    >Preferred Area
                                </label>
                                <div
                                    class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden border"
                                >
                                    <span
                                        class="input-group-text bg-white border-0 ps-3"
                                    >
                                        <i class="bi bi-map text-muted"></i>
                                    </span>
                                    <select
                                        v-model="preferredLocation"
                                        class="form-select border-0 fs-6 shadow-none"
                                    >
                                        <option value="" disabled>
                                            Select Area
                                        </option>
                                        <option value="Surigao">
                                            Surigao City
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <hr class="my-4 opacity-25" />

                                <div class="mb-4">
                                    <label
                                        class="fw-bold text-dark d-flex justify-content-between align-items-center mb-3"
                                    >
                                        <span
                                            ><i
                                                class="bi bi-wifi me-2 text-primary"
                                            ></i
                                            >Top Amenities</span
                                        >
                                        <span
                                            class="badge bg-light text-primary rounded-pill fw-normal"
                                            >{{
                                                preferredAmenities.length
                                            }}
                                            selected</span
                                        >
                                    </label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="amenity in aminitiesList"
                                            :key="amenity.id"
                                            @click="toggleAmenity(amenity.id)"
                                            class="btn tag-button rounded-pill px-3 py-2 transition-all shadow-sm"
                                            :class="
                                                preferredAmenities.includes(
                                                    amenity.id,
                                                )
                                                    ? 'tag-active'
                                                    : 'tag-inactive'
                                            "
                                        >
                                            <i
                                                class="bi bi-check2-circle me-1"
                                                v-if="
                                                    preferredAmenities.includes(
                                                        amenity.id,
                                                    )
                                                "
                                            ></i>
                                            {{ amenity.aminityName }}
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="fw-bold text-dark mb-3">
                                        <i
                                            class="bi bi-door-open me-2 text-primary"
                                        ></i
                                        >Room Features
                                    </label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="feature in featuresList"
                                            :key="feature.id"
                                            @click="toggleFeature(feature.id)"
                                            class="btn tag-button rounded-pill px-3 py-2 transition-all shadow-sm"
                                            :class="
                                                preferredFeature.includes(
                                                    feature.id,
                                                )
                                                    ? 'tag-active'
                                                    : 'tag-inactive'
                                            "
                                        >
                                            {{ feature.featureName }}
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="fw-bold text-dark mb-3">
                                        <i
                                            class="bi bi-shield-check me-2 text-primary"
                                        ></i
                                        >House Rules Preference
                                    </label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button
                                            v-for="rule in rulesList"
                                            :key="rule.id"
                                            @click="toggleRule(rule.id)"
                                            class="btn tag-button rounded-pill px-3 py-2 transition-all shadow-sm"
                                            :class="
                                                preferredRules.includes(rule.id)
                                                    ? 'tag-active-orange'
                                                    : 'tag-inactive'
                                            "
                                        >
                                            {{ rule.rulesName }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 p-4 bg-light">
                        <button
                            type="button"
                            class="btn btn-link text-muted fw-bold text-decoration-none me-auto"
                            @click="showModal = false"
                        >
                            Maybe Later
                        </button>
                        <button
                            @click="updateSubmitPersonalized"
                            class="btn btn-lg rounded-4 px-5 py-3 fw-bold text-white shadow-orange transition-all"
                            style="background-color: #fc7d07; border: none"
                        >
                            Update My Results
                            <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Navigation -->
    <div
        class="quick-actions-container mx-3 my-4 p-3 shadow-sm rounded-4 bg-white border border-light"
    >
        <p
            class="text-start small fw-bold text-muted text-uppercase mb-3 px-2 tracking-wider"
        >
            Quick Services
        </p>

        <div class="actions-grid">
            <button @click="viewBooking" class="action-card">
                <div class="icon-wrapper bg-blue-soft">
                    <i class="bi bi-calendar2-check text-blue"></i>
                </div>
                <span class="action-label">Bookings</span>
            </button>

            <button @click="viewPayment" class="action-card">
                <div class="icon-wrapper bg-orange-soft">
                    <i class="bi bi-credit-card text-orange"></i>
                </div>
                <span class="action-label">Payments</span>
            </button>

            <button @click="viewMyrooms" class="action-card">
                <div class="icon-wrapper bg-success-soft">
                    <i class="bi bi-door-open text-success"></i>
                </div>
                <span class="action-label">My Rooms</span>
            </button>

            <button @click="viewReservation" class="action-card">
                <div class="icon-wrapper bg-info-soft">
                    <i class="bi bi-bookmark-star text-info"></i>
                </div>
                <span class="action-label">Reservations</span>
            </button>

            <button
                @click="viewnotifications"
                class="action-card position-relative"
            >
                <div class="icon-wrapper bg-purple-soft">
                    <i class="bi bi-bell text-purple"></i>
                </div>
                <span class="action-label">Alerts</span>
            </button>
        </div>
    </div>
    <!-- Content Section -->
    <div class="map-section px-3 py-5">
        <div class="text-center mb-5 animate-fade-in">
            <span class="badge rounded-pill explorer-badge mb-2">
                <i class="bi bi-geo-alt-fill me-1 text-dash-orange"></i> Local
                Explorer
            </span>
            <h2 class="fw-bold display-5 mb-0">
                Find Your Stay in
                <span class="text-dash-blue">Surigao City</span>
            </h2>
            <p class="text-muted tracking-wider mt-2">
                Discover verified dormitories across the City of Island
                Adventures
            </p>
        </div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div
                        class="card map-container border-0 shadow-lg rounded-5 overflow-hidden"
                    >
                        <div
                            class="card-header bg-white border-0 p-4 d-flex align-items-center justify-content-between flex-wrap gap-3"
                        >
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-blue-soft me-3">
                                    <i
                                        class="bi bi-map-fill text-dash-blue"
                                    ></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-0">
                                        City Map Overview
                                    </h4>
                                    <small class="text-muted">
                                        <span class="pulse-dot"></span>
                                        Currently showing 15+ verified
                                        properties
                                    </small>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button
                                    class="btn btn-dash-blue rounded-pill px-4 shadow-sm"
                                >
                                    <i class="bi bi-list-ul me-1"></i> View All
                                    Listings
                                </button>
                                <button
                                    class="btn btn-outline-dash-orange rounded-pill px-3"
                                >
                                    <i class="bi bi-filter"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0 position-relative">
                            <div
                                id="map-surigao"
                                class="map-frame"
                                style="height: 550px"
                            ></div>

                            <div class="map-controls shadow">
                                <div class="control-item border-bottom">
                                    <i class="bi bi-plus-lg"></i>
                                </div>
                                <div class="control-item">
                                    <i class="bi bi-dash-lg"></i>
                                </div>
                            </div>

                            <div
                                class="map-floating-label shadow-sm animate-bounce"
                            >
                                <i class="bi bi-cursor-fill me-1"></i> Drag to
                                explore Surigao City
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 rounded shadow-sm text-center mb-4 bg-light border-info">
        <h2 class="h4 fw-bold text-info">Top Rated Dormitories</h2>
        <p class="text-muted">Check out the best dorms in your area</p>
    </div>

    <div class="top-rated-section m-2 py-5">
        <div
            class="section-header mb-4 px-2 d-flex justify-content-between align-items-end"
        >
            <div>
                <h2 class="fw-bold mb-0">
                    Top <span class="text-dash-orange">Rated</span> Dorms
                </h2>
                <p class="text-muted mb-0">
                    The most loved stays in Surigao City
                </p>
            </div>
            <a
                href="#"
                class="btn btn-link text-dash-blue fw-bold text-decoration-none"
                >View All <i class="bi bi-arrow-right"></i
            ></a>
        </div>

        <div class="row g-3">
            <div class="col-lg-7 col-md-12" v-if="topDorms.length > 0">
                <div
                    class="card featured-card border-0 shadow-sm h-100 position-relative overflow-hidden bento-item"
                    @click="viewDorms(topDorms[0].dorm.dormID)"
                >
                    <div
                        class="bento-image"
                        :style="{
                            backgroundImage: `url(${topDorms[0].dorm.images?.mainImage || '/default-image.jpg'})`,
                        }"
                    ></div>
                    <div class="bento-overlay"></div>

                    <div class="bento-content p-4">
                        <span class="badge glass-badge mb-2"
                            ><i class="bi bi-trophy-fill text-warning me-1"></i>
                            Top Choice</span
                        >
                        <h3 class="fw-bold text-white">
                            {{ topDorms[0].dorm.dormName }}
                        </h3>
                        <p class="text-white-50 mb-3">
                            <i class="bi bi-geo-alt me-1"></i>
                            {{ topDorms[0].dorm.address }}
                        </p>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rating-pill">
                                <i class="bi bi-star-fill me-1"></i>
                                {{ Number(topDorms[0].avg_rating).toFixed(1) }}
                            </div>
                            <span class="btn btn-light btn-sm rounded-pill px-3"
                                >Details</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-12">
                <div class="row g-3">
                    <div class="col-12" v-if="topDorms.length > 1">
                        <div
                            class="card secondary-card border-0 shadow-sm position-relative overflow-hidden bento-item"
                            @click="viewDorms(topDorms[1].dorm.dormID)"
                        >
                            <div
                                class="bento-image"
                                :style="{
                                    backgroundImage: `url(${topDorms[1].dorm.images?.mainImage || '/default-image.jpg'})`,
                                }"
                            ></div>
                            <div class="bento-overlay"></div>
                            <div class="bento-content p-3">
                                <h5 class="fw-bold text-white mb-1">
                                    {{ topDorms[1].dorm.dormName }}
                                </h5>
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <span class="small text-white-50"
                                        ><i
                                            class="bi bi-star-fill text-warning"
                                        ></i>
                                        {{
                                            Number(
                                                topDorms[1].avg_rating,
                                            ).toFixed(1)
                                        }}</span
                                    >
                                    <span class="btn btn-glass-sm">View</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="col-6"
                        v-for="(dorm, index) in topDorms.slice(2, 4)"
                        :key="dorm.fkdormID"
                    >
                        <div
                            class="card mini-card border-0 shadow-sm position-relative overflow-hidden bento-item"
                            @click="viewDorms(dorm.dorm.dormID)"
                        >
                            <div
                                class="bento-image"
                                :style="{
                                    backgroundImage: `url(${dorm.dorm.images?.mainImage || '/default-image.jpg'})`,
                                }"
                            ></div>
                            <div class="bento-overlay"></div>
                            <div class="bento-content p-3 text-center">
                                <div class="mini-rating mb-1">
                                    <i class="bi bi-star-fill"></i>
                                    {{ Number(dorm.avg_rating).toFixed(1) }}
                                </div>
                                <h6
                                    class="fw-bold text-white mb-0 text-truncate"
                                >
                                    {{ dorm.dorm.dormName }}
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Fixed Bottom-Right Button -->
    <!-- Floating Button -->
    <div class="fixed-actions-container">
        <!-- <button
            type="button"
            class="btn-dash-float mb-3 shadow-lg animate__animated animate__fadeInRight"
            data-bs-toggle="offcanvas"
            data-bs-target="#staticBackdrop"
            aria-controls="staticBackdrop"
        >
            <div class="float-content">
                <i class="bi bi-stars"></i>
                <span class="btn-text">Recommended</span>
            </div>
        </button>

        <div
            v-if="isPersonalized === true"
            class="animate__animated animate__fadeInUp"
        >
            <button
                @click="openPreferences"
                class="btn-dash-sub-float shadow-lg"
            >
                <div class="float-content">
                    <i class="bi bi-sliders2-vertical"></i>
                    <span class="btn-text">Preferences</span>
                </div>
            </button>
        </div>
    </div> -->
    </div>
</template>
<script>
import axios from "axios";
import Loader from "@/components/loader.vue";
import NotificationList from "@/components/notifications.vue";
import { get } from "lodash";

export default {
    components: {
        Loader,
        NotificationList,
    },
    data() {
        return {
            rooms: [],
            tenant_id: "",
            tenant: {
                preferred_amenities: [],
                preferred_features: [],
                preferred_rules: [],
            },
            notifications: [],
            receiverID: "",
            topDorms: [],
            genderPersonalized: [],
            preferredPrice: null,
            preferredLocation: "",
            showModal: false,
            showWelcome: false,
            showafterWelcome: false,
            showText: [false, false],
            isPersonalized: false,
            afterpersonalized: false,
            aminitiesList: {},
            featuresList: {},
            amenitiesList: {},
            preferredAmenities: [],
            preferredRules: [],
            preferredFeature: [],
            loading: false,
        };
    },
    methods: {
        subscribeToNotifications() {
            if (this.hasSubscribed) return;
            this.hasSubscribed = true;

            this.receiverID = this.tenant_id;
            Echo.private(`notifications.${this.tenant_id}`)
                .subscribed(() => {
                    console.log("✔ Subscribed!");
                })
                .listen(".NewNotificationEvent", (e) => {
                    this.notifications.unshift(e); // save for list
                    this.$refs.toastRef.pushNotification({
                        title: e.title || "New Notification",
                        message: e.message,
                        color: "success",
                    });
                });
        },

        viewDorms(dormID) {
            this.tenant_id = window.tenant_id;
            window.location.href = `/room-details/${dormID}/${this.tenant_id}`;
        },
        viewBooking() {
            this.tenant_id = window.tenant_id;
            window.location.href = `/view/booking/${this.tenant_id}`;
        },
        viewPayment() {
            this.tenant_id = window.tenant_id;
            window.location.href = `/view/payment/${this.tenant_id}`;
        },
        viewMyrooms() {
            this.tenant_id = window.tenant_id;
            window.location.href = `/view/myrooms/${this.tenant_id}`;
        },
        viewReservation() {
            this.tenant_id = window.tenant_id;
            window.location.href = `/view/reservation/${this.tenant_id}`;
        },
        viewnotifications() {
            this.tenant_id = window.tenant_id;
            window.location.href = `/view/notifications/${this.tenant_id}`;
        },
        async genderPreferencePersonalize() {
            try {
                const response = await axios.get(
                    `/gender-preference-dorms/${this.tenant_id}`,
                );
                this.genderPersonalized = response.data.dorms;
                console.log(this.genderPersonalized);
            } catch (error) {
                console.error(error);
            }
        },
        initMap() {
            this.tenant_id = window.tenant_id;

            // Surigao City Coordinates
            const surigaoCity = { lat: 9.7915, lng: 125.4953 };

            const customStyle = [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }],
                },
            ];

            // Initialize Surigao Map
            const mapSurigao = new google.maps.Map(
                document.getElementById("map-surigao"),
                {
                    zoom: 14,
                    center: surigaoCity,
                    draggable: true, // Gihimo nakong true para ma-explore sa user
                    disableDoubleClickZoom: false,
                    mapTypeControl: false,
                    fullscreenControl: true,
                    mapTypeId: "roadmap", // 'roadmap' kasagaran mas limpyo tan-awon sa city
                    styles: customStyle,
                },
            );

            const infoWindow = new google.maps.InfoWindow();

            // Fetch Surigao Dorms
            // Siguroha nga ang imong backend route kay '/tenant/dorms/surigao'
            axios
                .get("/tenant/dorms/surigao")
                .then((response) => {
                    response.data.forEach((dorm) => {
                        const marker = new google.maps.Marker({
                            position: {
                                lat: parseFloat(dorm.latitude),
                                lng: parseFloat(dorm.longitude),
                            },
                            map: mapSurigao,
                            title: dorm.dorm_name,
                            icon: {
                                url: "/images/tenant/allimagesResouces/dormmap.webp",
                                scaledSize: new google.maps.Size(45, 45), // Slightly larger for better visibility
                            },
                            animation: google.maps.Animation.DROP,
                        });

                        const content = `
                    <div style="width: 220px; font-family: 'Poppins', sans-serif; padding: 5px;">
                        <img src="${dorm.images.mainImage}" alt="${dorm.dormName}"
                            style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-weight: 700; color: #003C87; font-size: 16px; margin-bottom: 4px;">
                            🏠 ${dorm.dormName}
                        </div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 12px;">Verified Dormitory in Surigao</p>
                        <a href="/room-details/${dorm.dormID}/${this.tenant_id}" 
                           style="display: block; text-align: center; background: #FC7D07; color: white; 
                                  text-decoration: none; padding: 8px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                            View Details
                        </a>
                    </div>
                `;

                        marker.addListener("click", () => {
                            infoWindow.setContent(content);
                            infoWindow.open(mapSurigao, marker);
                        });
                    });
                })
                .catch((error) => {
                    console.error("Error fetching Surigao dorms:", error);
                });
        },

        async fetchTopRatedDorms() {
            try {
                const response = await axios.get("/api/top-rated-dorms");
                this.topDorms = response.data.map((dorm) => ({
                    ...dorm,
                    avg_rating: Number(dorm.avg_rating),
                }));
            } catch (error) {
                console.error("Error fetching top rated:", error);
            }
        },
        async getTenant() {
            try {
                const response = await axios.get("/get/preferred-tenants");
                this.isPersonalized = response.data.tenant.isPersonalized;
                this.preferredLocation =
                    response.data.tenant.preferred_location;
                this.preferredPrice = response.data.tenant.preferred_room_price;
            } catch (error) {}
        },
        async updateSubmitPersonalized() {
            try {
                const payload = {
                    preferredAmenities: this.preferredAmenities,
                    preferredFeature: this.preferredFeature,
                    preferredRules: this.preferredRules,
                    preferredPrice: this.preferredPrice,
                    preferredLocation: this.preferredLocation,
                };

                const response = await axios.post(
                    "/update/submit-personalized",
                    payload,
                );
                this.showModal = false;
                this.afterpersonalized = true;
                this.showafterWelcome = true;
                this.genderPreferencePersonalize();

                setTimeout(() => {
                    if (this.$refs.loader) {
                        this.$refs.loader.loading = false;
                    }
                    this.showafterWelcome = false;
                    this.afterpersonalized = false;
                }, 3000); // 3 seconds
            } catch (error) {
                console.error("Error saving preferences:", error);
            }
        },

        async welcomeLoader() {
            await this.getTenant();
            this.showWelcome = true;
            // Simulate delay
            setTimeout(() => {
                this.showWelcome = false;
                this.showModal = true;
            }, 3000); // 3 seconds
        },

        startSequence() {
            // Show "Find Your Dorm" after 1 second
            setTimeout(() => {
                this.showText[0] = true;
            }, 1000);

            // Show "Book Now" after 2.5 seconds
            setTimeout(() => {
                this.showText[1] = true;
            }, 1000);
        },
        openPreferences() {
            this.showModal = true;
            this.showWelcome = false;
            this.isPersonalized = false;
        },
        async getPreferences() {
            try {
                this.loading = true;
                const response = await axios.get(
                    "/get/room-and-dorm-personalized",
                );
                // Direct assignment (no map)
                this.rulesList = response.data.rulesarray || [];
                this.featuresList = response.data.featureArray || [];
                this.aminitiesList = response.data.amenitiesArray || [];
                this.loading = false;
            } catch (error) {
                console.error("Error fetching preferences:", error);
            } finally {
                this.loading = false;
            }
        },
        toggleAmenity(id) {
            const index = this.preferredAmenities.indexOf(id);
            if (index === -1) {
                this.preferredAmenities.push(id);
            } else {
                this.preferredAmenities.splice(index, 1);
            }
        },
        toggleFeature(id) {
            const index = this.preferredFeature.indexOf(id);
            if (index === -1) {
                this.preferredFeature.push(id);
            } else {
                this.preferredFeature.splice(index, 1);
            }
        },
        toggleRule(id) {
            const index = this.preferredRules.indexOf(id);
            if (index === -1) {
                this.preferredRules.push(id);
            } else {
                this.preferredRules.splice(index, 1);
            }
        },
    },
    mounted() {
        // Load Google Maps script dynamically
        const script = document.createElement("script");
        script.src =
            "https://maps.googleapis.com/maps/api/js?key=AIzaSyCbVSKsv35IGFWYg9C96B5swf6UaVj9IGQ&callback=initMap";
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        // Attach initMap function globally
        window.initMap = this.initMap;
        this.tenant_id = window.tenant_id;
        this.getPreferences();
        this.subscribeToNotifications();
        this.genderPreferencePersonalize();
        this.fetchTopRatedDorms();
        this.welcomeLoader();
        this.startSequence();
    },
};
</script>
<style scoped src="../../../../css/tenant/homepage.css"></style>
