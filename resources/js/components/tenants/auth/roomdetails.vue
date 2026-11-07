<template>
    <div v-if="this.is_deactivated === 0" class="bg-light min-vh-100 pb-5">
        <Loader ref="loader" />
        <Toastcomponents ref="toast" />
        <NotificationList ref="toastRef" />

        <div class="container-fluid px-lg-5 pt-4" v-if="dorm">
            <button class="floating-bot" @click="askAI">
                <i class="bi bi-robot"></i>
            </button>
            <div
                v-if="askAIModal"
                class="modal fade show d-block custom-modal-backdrop"
                tabindex="-1"
            >
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div
                        class="modal-content shadow-2xl rounded-4 overflow-hidden border-0"
                    >
                        <!-- Header -->
                        <div
                            class="modal-header custom-modal-header text-white px-4 py-3"
                        >
                            <h5
                                class="modal-title d-flex align-items-center fw-bold"
                            >
                                <div class="icon-wrapper me-3">
                                    <i class="bi bi-robot fs-4"></i>
                                </div>
                                Smart Guide for
                                <span class="ms-2 text-accent fw-bold">{{
                                    selectedDormAI.dormName
                                }}</span>
                            </h5>
                            <button
                                type="button"
                                class="btn-close btn-close-white shadow-none"
                                @click="askAIModal = false"
                            ></button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body p-4 p-md-5 bg-light-subtle">
                            <div class="mb-4">
                                <label
                                    for="aiQuestion"
                                    class="form-label fw-semibold text-dark mb-2"
                                >
                                    <i
                                        class="bi bi-chat-square-text-fill me-1 text-primary"
                                    ></i>
                                    Your Question
                                </label>
                                <textarea
                                    id="aiQuestion"
                                    v-model="aiQuestion"
                                    class="form-control custom-textarea shadow-none p-3"
                                    rows="4"
                                    placeholder="Ask anything about the dormitory, house rules, curfew, utilities, or payment terms..."
                                ></textarea>
                            </div>

                            <!-- AI Response Card -->
                            <div
                                v-if="aiResponse"
                                class="ai-response-card p-4 rounded-4 shadow-sm position-relative mb-2"
                            >
                                <div class="d-flex align-items-center mb-2">
                                    <div
                                        class="ai-badge rounded-circle d-flex align-items-center justify-content-center me-2"
                                    >
                                        <i
                                            class="bi bi-cpu text-white fs-6"
                                        ></i>
                                    </div>
                                    <h6 class="fw-bold mb-0 text-primary">
                                        AI Assistant Response
                                    </h6>
                                </div>
                                <p class="mb-0 text-secondary lh-base">
                                    {{ aiResponse }}
                                </p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="modal-footer px-4 py-3 bg-white border-top-0 d-flex justify-content-between align-items-center"
                        >
                            <button
                                type="button"
                                class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-medium"
                                @click="askAIModal = false"
                            >
                                <i class="bi bi-x-circle me-2"></i> Close
                            </button>
                            <button
                                type="button"
                                class="btn custom-btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm"
                                @click="sendToAI"
                            >
                                <i class="bi bi-send-fill me-2"></i> Ask AI
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div
                class="landlord-profile-card mb-4 p-4 rounded-4 shadow-sm bg-white border-0 animate__animated animate__fadeIn"
            >
                <div class="row align-items-center g-3">
                    <div
                        class="col-12 col-md-8 d-flex align-items-center gap-3"
                    >
                        <div class="avatar-wrapper position-relative">
                            <div
                                class="landlord-avatar d-flex align-items-center justify-content-center bg-blue-soft text-dash-blue fw-bold fs-4 rounded-circle shadow-sm"
                                style="
                                    width: 65px;
                                    height: 65px;
                                    border: 2px solid #fff;
                                "
                            >
                                {{ landlordname.charAt(0) }}
                            </div>
                            <div
                                class="verified-check position-absolute bottom-0 end-0 bg-white rounded-circle text-primary lh-1 shadow-sm"
                                style="font-size: 1.2rem"
                            >
                                <i class="bi bi-patch-check-fill"></i>
                            </div>
                        </div>
                        <div class="info-content">
                            <h4 class="fw-bold text-dark mb-1">
                                {{ landlordname }}
                            </h4>
                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                <span
                                    class="badge bg-light text-muted fw-normal border rounded-pill px-3 py-2"
                                >
                                    <i
                                        class="bi bi-calendar3 me-1 text-primary"
                                    ></i>
                                    Posted
                                    {{ formatDate(dorm.dorm.created_at) }}
                                </span>
                                <span
                                    class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2"
                                >
                                    <i class="bi bi-shield-check me-1"></i>
                                    Verified Landlord
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex justify-content-md-end">
                        <button
                            class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center"
                            @click="messagePage"
                        >
                            <i class="bi bi-chat-dots-fill me-2"></i> Message
                            Landlord
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div
                        class="bg-white rounded-4 shadow-sm border-0 p-3 mb-4 animate__animated animate__fadeInUp"
                    >
                        <div
                            class="main-image-wrapper mb-3 rounded-4 overflow-hidden position-relative shadow-sm"
                        >
                            <img
                                :src="mainImage"
                                alt="Main Image"
                                class="w-100 transition-img"
                                style="height: 450px; object-fit: cover"
                            />
                            <div
                                class="position-absolute bottom-0 start-0 m-3 glass-effect px-3 py-2 rounded-pill text-white small fw-bold"
                                style="
                                    background: rgba(0, 0, 0, 0.5);
                                    backdrop-filter: blur(5px);
                                "
                            >
                                <i class="bi bi-camera-fill me-2"></i>
                                {{ images.length }} Photos
                            </div>
                        </div>
                        <div
                            class="d-flex gap-2 overflow-auto pb-2 custom-scrollbar"
                        >
                            <div
                                v-for="(img, index) in images"
                                :key="index"
                                class="flex-shrink-0"
                            >
                                <img
                                    :src="img"
                                    :alt="'Thumbnail ' + (index + 1)"
                                    class="rounded-3 border-2 clickable-thumbnail shadow-xs"
                                    :class="{
                                        'border-primary active-thumb':
                                            mainImage === img,
                                        'border-transparent': mainImage !== img,
                                    }"
                                    @click="changeMainImage(img)"
                                    style="
                                        height: 80px;
                                        width: 100px;
                                        object-fit: cover;
                                        cursor: pointer;
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-4 shadow-sm border-0 p-4 mb-4">
                        <div
                            class="d-flex justify-content-between align-items-start mb-3"
                        >
                            <div>
                                <h3 class="fw-bold text-dark mb-1">
                                    {{ dorm.dorm.dormName }}
                                </h3>
                                <p class="text-muted">
                                    <i
                                        class="bi bi-geo-alt-fill text-danger me-1"
                                    ></i>
                                    {{
                                        dorm.dorm.address.replace(
                                            "at the back of ",
                                            "",
                                        )
                                    }}
                                </p>
                            </div>
                            <span
                                class="badge px-3 py-2 fs-6 rounded-pill"
                                :class="
                                    dorm.dorm.availability === 'Available'
                                        ? 'bg-success'
                                        : 'bg-danger'
                                "
                            >
                                {{ dorm.dorm.availability }}
                            </span>
                        </div>

                        <div class="row g-3 mb-4 text-center">
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded-4 border">
                                    <i
                                        class="bi bi-people text-primary fs-4"
                                    ></i>
                                    <div class="small text-muted mt-1">
                                        Occupancy
                                    </div>
                                    <div class="fw-bold">
                                        {{ dorm.dorm.occupancyType }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded-4 border">
                                    <i
                                        class="bi bi-building text-primary fs-4"
                                    ></i>
                                    <div class="small text-muted mt-1">
                                        Building
                                    </div>
                                    <div class="fw-bold">
                                        {{ dorm.dorm.buildingType }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded-4 border">
                                    <i
                                        class="bi bi-door-open text-primary fs-4"
                                    ></i>
                                    <div class="small text-muted mt-1">
                                        Rooms
                                    </div>
                                    <div class="fw-bold">
                                        {{ dorm.dorm.totalRooms }} Left
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-light rounded-4 border">
                                    <i
                                        class="bi bi-person-check text-primary fs-4"
                                    ></i>
                                    <div class="small text-muted mt-1">
                                        Tenants
                                    </div>
                                    <div class="fw-bold">
                                        {{ dorm.dorm.totalCapacity }} Total
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold">Description</h5>
                        <p class="text-muted">{{ dorm.dorm.description }}</p>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div
                                class="bg-white rounded-4 shadow-sm border-0 p-4 h-100"
                            >
                                <h5 class="fw-bold mb-3 text-primary">
                                    <i class="bi bi-stars me-2"></i>Amenities
                                </h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <span
                                        v-for="aminity in displayedAmenities"
                                        :key="aminity.id"
                                        class="badge bg-light text-dark fw-medium border rounded-pill px-3 py-2"
                                    >
                                        <i
                                            class="bi bi-check2-circle text-success me-1"
                                        ></i>
                                        {{ aminity.aminityName }}
                                    </span>
                                </div>
                                <button
                                    v-if="amenities.length > 3"
                                    @click.prevent="
                                        amenitiesShowMore = !amenitiesShowMore
                                    "
                                    class="btn btn-link text-primary btn-sm p-0 mt-3 text-decoration-none fw-bold"
                                >
                                    {{
                                        amenitiesShowMore
                                            ? "-- Show Less --"
                                            : "-- View All " +
                                              amenities.length +
                                              " --"
                                    }}
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div
                                class="bg-white rounded-4 shadow-sm border-0 p-4 h-100"
                            >
                                <h5 class="fw-bold mb-3 text-warning">
                                    <i
                                        class="bi bi-chat-left-heart-fill me-2"
                                    ></i
                                    >Rating & Review
                                </h5>
                                <div
                                    class="d-flex align-items-center justify-content-between mb-3 bg-light p-3 rounded-3"
                                >
                                    <div>
                                        <h2 class="fw-bold mb-0 text-dark">
                                            {{ averagePercentage }}%
                                        </h2>
                                        <small class="text-muted fw-bold"
                                            >Dorm Score</small
                                        >
                                    </div>
                                    <div class="text-end">
                                        <div class="text-warning fs-5">
                                            <i
                                                v-for="n in 5"
                                                :key="n"
                                                :class="getStarClass(n)"
                                            ></i>
                                        </div>
                                        <small class="text-muted"
                                            >From
                                            {{ totalReviewers }}
                                            reviewers</small
                                        >
                                    </div>
                                </div>
                                <button
                                    class="btn btn-outline-dark btn-sm w-100 rounded-pill fw-bold py-2"
                                    @click="clickRatingandReview()"
                                >
                                    See All Reviews
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div
                                class="bg-white rounded-4 shadow-sm border-0 p-4 h-100"
                            >
                                <h5 class="fw-bold mb-3 text-danger">
                                    <i class="bi bi-shield-exclamation me-2"></i
                                    >Rules & Policies
                                </h5>
                                <ul class="ps-3 mb-0 text-muted small">
                                    <li
                                        v-for="rule in displayedRulesAndPolicy"
                                        :key="rule.id"
                                        class="mb-2"
                                    >
                                        {{ rule.rulesName }}
                                    </li>
                                </ul>
                                <button
                                    v-if="rulesAndPolicy.length > 3"
                                    @click.prevent="
                                        rulesAndPolicyShowMore =
                                            !rulesAndPolicyShowMore
                                    "
                                    class="btn btn-link text-danger btn-sm p-0 mt-2 text-decoration-none fw-bold"
                                >
                                    {{
                                        rulesAndPolicyShowMore
                                            ? "− Show Less"
                                            : "+ View More Policies"
                                    }}
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div
                                class="bg-white rounded-4 shadow-sm border-0 p-4 h-100"
                            >
                                <h5 class="fw-bold mb-3">
                                    <i
                                        class="bi bi-map-fill text-primary me-2"
                                    ></i
                                    >Exact Location
                                </h5>
                                <div
                                    class="rounded-3 overflow-hidden border"
                                    style="height: 180px"
                                >
                                    <div id="map" class="w-100 h-100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-4 shadow-sm border-0 p-4 mb-4">
                        <h5 class="fw-bold mb-3">
                            <i class="bi bi-cash-coin text-success me-2"></i
                            >Available Room Types
                        </h5>
                        <div
                            v-if="rooms.length === 0"
                            class="alert alert-light border text-center py-4"
                        >
                            No rooms available
                        </div>
                        <div v-else class="row g-3">
                            <div
                                v-for="room in rooms"
                                :key="room.roomID"
                                class="col-12 col-md-6"
                            >
                                <div
                                    class="card h-100 border-primary-subtle shadow-sm p-3 rounded-4"
                                    @click="roomDetails(room.roomID)"
                                    style="cursor: pointer"
                                >
                                    <div
                                        class="d-flex justify-content-between align-items-center"
                                    >
                                        <h6 class="fw-bold mb-0 text-dark">
                                            {{ room.roomType }}
                                        </h6>
                                        <span class="fs-5 fw-bold text-success"
                                            >₱{{
                                                room.price.toLocaleString()
                                            }}</span
                                        >
                                    </div>
                                    <small class="text-muted mt-1"
                                        >Click for more details</small
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="sticky-top" style="top: 20px; z-index: 10">
                        <div
                            class="bg-white rounded-4 shadow-lg border-0 p-4 mb-4"
                        >
                            <h5 class="fw-bold mb-4 text-primary">
                                <i class="bi bi-pencil-square me-2"></i>Book a
                                Visit
                            </h5>
                            <div class="mb-3">
                                <label class="form-label small fw-bold"
                                    >Full Name</label
                                >
                                <div class="d-flex gap-2 mb-2">
                                    <input
                                        type="text"
                                        v-model="firstname"
                                        class="form-control bg-light border-0 py-2"
                                        placeholder="First"
                                    />
                                    <input
                                        type="text"
                                        v-model="lastname"
                                        class="form-control bg-light border-0 py-2"
                                        placeholder="Last"
                                    />
                                </div>
                                <div class="d-flex gap-2">
                                    <span
                                        v-if="errors.firstname"
                                        class="text-danger x-small"
                                        >{{ errors.firstname[0] }}</span
                                    >
                                    <span
                                        v-if="errors.lastname"
                                        class="text-danger x-small"
                                        >{{ errors.lastname[0] }}</span
                                    >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold"
                                    >Contact Info</label
                                >
                                <input
                                    type="text"
                                    v-model="contactInfo"
                                    class="form-control bg-light border-0 py-2 mb-2"
                                    placeholder="Phone Number"
                                />
                                <input
                                    type="email"
                                    v-model="email"
                                    class="form-control bg-light border-0 py-2"
                                    placeholder="Email Address"
                                />
                                <span
                                    v-if="errors.email"
                                    class="text-danger x-small"
                                    >{{ errors.email[0] }}</span
                                >
                            </div>
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <label class="form-label small fw-bold"
                                        >Age</label
                                    >
                                    <input
                                        type="number"
                                        v-model.number="age"
                                        class="form-control bg-light border-0 py-2"
                                    />
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold"
                                        >Sex</label
                                    >
                                    <select
                                        v-model="sex"
                                        class="form-select bg-light border-0 py-2"
                                    >
                                        <option value="" disabled>
                                            Select
                                        </option>
                                        <option>Male</option>
                                        <option>Female</option>
                                    </select>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="submitTenantInformation"
                                class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow"
                            >
                                Submit Reservation
                            </button>
                        </div>

                        <div
                            class="contact-card p-4 rounded-4 shadow-sm bg-dark text-white border-0"
                        >
                            <h6
                                class="fw-bold mb-3 small text-uppercase opacity-75"
                            >
                                Quick Contact
                            </h6>
                            <div class="d-flex align-items-center mb-3">
                                <i
                                    class="bi bi-telephone-fill text-primary me-3 fs-5"
                                ></i>
                                <span class="fw-bold">{{
                                    dorm.dorm.contactPhone
                                }}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i
                                    class="bi bi-envelope-at-fill text-primary me-3 fs-5"
                                ></i>
                                <span class="fw-bold text-truncate">{{
                                    dorm.dorm.contactEmail
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="VisibleImagePostModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0, 0, 0, 0.8)"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-5 border-0 p-4">
                    <div class="modal-header border-0 pb-0">
                        <h4 class="fw-bold">Upload ID Picture</h4>
                        <button
                            type="button"
                            class="btn-close"
                            @click="closeImageModal"
                        ></button>
                    </div>
                    <div class="modal-body text-center p-4">
                        <div
                            v-if="isImage"
                            class="upload-zone border-dashed rounded-4 p-5 mb-3 bg-light"
                            @click="triggeridPictureImage"
                            style="cursor: pointer; border: 2px dashed #ddd"
                        >
                            <input
                                ref="idPicturesInput"
                                class="d-none"
                                type="file"
                                accept="image/*"
                                @change="handleidPictre"
                            />
                            <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                            <h5 class="fw-bold mt-2">Click to Browse</h5>
                        </div>
                        <div v-if="idPicturePreview" class="mb-3">
                            <img
                                :src="idPicturePreview"
                                class="img-fluid rounded-4 shadow-sm mb-3"
                                style="max-height: 250px"
                            />
                            <br /><button
                                @click="removeidPicture"
                                class="btn btn-danger btn-sm rounded-pill px-4"
                            >
                                Remove Image
                            </button>
                        </div>
                        <button
                            class="btn btn-primary w-100 py-3 rounded-4 fw-bold mt-3"
                            @click="tenantIdpicture"
                        >
                            Select Room & Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="roomDetailsModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0, 0, 0, 0.8)"
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content rounded-5 border-0 overflow-hidden shadow-lg animate__animated animate__zoomIn"
                >
                    <div class="row g-0">
                        <div
                            class="col-md-6 bg-light d-flex align-items-center justify-content-center border-end"
                        >
                            <img
                                v-if="selectedRoomDetails.roomImages"
                                :src="selectedRoomDetails.roomImages"
                                class="img-fluid w-100 h-100 object-fit-cover"
                            />
                            <div v-else class="text-muted p-5 text-center">
                                <i class="bi bi-image fs-1"></i>
                                <p>No Image Available</p>
                            </div>
                        </div>
                        <div class="col-md-6 p-5">
                            <button
                                type="button"
                                class="btn-close float-end"
                                @click="roomDetailsModal = false"
                            ></button>
                            <h2 class="fw-bold text-dark">
                                {{ selectedRoomDetails.roomType }}
                            </h2>
                            <h3 class="text-success fw-bold mb-4">
                                ₱{{
                                    selectedRoomDetails.price?.toLocaleString()
                                }}
                                <small class="fs-6 text-muted">/ month</small>
                            </h3>

                            <div class="row mb-4">
                                <div class="col-6">
                                    <small class="text-muted d-block"
                                        >Area Size</small
                                    ><strong
                                        >{{
                                            selectedRoomDetails.areaSqm
                                        }}
                                        sqm</strong
                                    >
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block"
                                        >Status</small
                                    ><strong
                                        :class="
                                            selectedRoomDetails.availability
                                                ? 'text-success'
                                                : 'text-danger'
                                        "
                                        >{{
                                            selectedRoomDetails.availability
                                                ? "Available"
                                                : "Occupied"
                                        }}</strong
                                    >
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3">Room Features</h6>
                            <div class="d-flex flex-wrap gap-2 mb-5">
                                <span
                                    v-for="feature in selectedRoomDetails.features"
                                    :key="feature.id"
                                    class="badge bg-success-subtle text-success border px-3 py-2 rounded-pill fw-normal"
                                >
                                    {{ feature.featureName }}
                                </span>
                            </div>
                            <button
                                class="btn btn-dark w-100 rounded-pill py-2"
                                @click="roomDetailsModal = false"
                            >
                                Close Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modalconfirmation ref="modal" />
    </div>

    <div
        v-else
        class="vh-100 d-flex align-items-center justify-content-center bg-white p-4"
    >
        <div
            class="text-center shadow p-5 rounded-5 border"
            style="max-width: 500px"
        >
            <i
                class="bi bi-shield-lock-fill text-danger mb-4"
                style="font-size: 5rem"
            ></i>
            <h2 class="fw-bold text-dark">Dorm Temporarily Unavailable</h2>
            <p class="text-muted mt-3">
                The landlord for this property is restricted. Please check other
                verified dorms in our listing.
            </p>
            <a href="/" class="btn btn-dark px-5 py-2 rounded-pill fw-bold mt-4"
                >Back to Home</a
            >
        </div>
    </div>
</template>

<script>
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";
import Loader from "@/components/loader.vue";
import Modalconfirmation from "@/components/modalconfirmation.vue";
import NotificationList from "@/components/notifications.vue";
import { nextTick } from "vue";
export default {
    components: {
        Toastcomponents,
        Loader,
        NotificationList,
        Modalconfirmation,
    },
    data() {
        return {
            notifications: [],
            askAIModal: false,
            receiverID: "",
            landlordname: [],
            landlordImage: null,
            tenant_id: "",
            mainImage: "",
            paymentIcon: "/images/tenant/allimagesResouces/paymentIcon.jpg",
            id_picture:
                "/images/tenant/allimagesResouces/vector-id-card-icon.jpg",
            payment: {
                gcash: "/images/tenant/allimagesResouces/GCash-Logo.png",
                maya: "/images/tenant/allimagesResouces/maya.png",
                bank_transer:
                    "/images/tenant/allimagesResouces/bank-transfer-logo.png",
            },
            images: [],
            rooms: [],
            lng: "",
            lat: "",
            roomsDetail: null,
            amenities: [],
            rulesAndPolicy: [],
            visibleRooms: 2,
            landlord_id: "",
            room_id: "",
            selectedRoomId: "",
            firstname: "",
            lastname: "",
            contactInfo: "",
            VisibleImagePostModal: false,
            age: 20,
            sex: "",
            email: "",
            student_picture: "",
            dormitory_id: "",
            dorm: null,
            idPicturePreview: "",
            idPictureFile: null,
            openPaymentModel: false,
            openRoomModal: false,
            openRoomDetailsModal: false,
            errors: {},
            payment_type: "",
            totalCapacity: 0,
            PaymentPicturePreview: "",
            PaymentPictureFile: "",
            amenitiesShowMore: false,
            rulesAndPolicyShowMore: false,
            isImage: true,
            totalReviewers: 0,
            averagePercentage: 0,
            roomDetailsModal: false,
            selectedRoomDetails: "",
            is_deactivated: 0,
        };
    },
    mounted() {
        nextTick(() => {
            const element = document.getElementById("RoomDetails");
            if (element) {
                this.dormitory_id = element.dataset.dormId;
                this.tenant_id = element.dataset.tenantId;
                this.displayDorms();
                this.dormLocation();
                this.subscribeToNotifications();
                this.fetchStats();
                this.fillupTenant();
            } else {
                console.error("RoomDetails element not found!");
            }
        });
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
        changeMainImage(imgSrc) {
            this.mainImage = imgSrc;
        },
        loadImagesFromData(data) {
            // Collect images from data.dorm.images object, ignoring null or empty strings
            const imgs = [];
            let dormImages = data.dorm.images;

            if (dormImages.mainImage) imgs.push(dormImages.mainImage);
            if (dormImages.secondaryImage) imgs.push(dormImages.secondaryImage);
            if (dormImages.thirdImage) imgs.push(dormImages.thirdImage);

            this.images = imgs;
            this.mainImage = imgs.length > 0 ? imgs[0] : "";
        },

        async displayDorms() {
            try {
                const res = await axios.get("/dorm-details", {
                    params: { dormitory_id: this.dormitory_id },
                });
                this.loadImagesFromData(res.data);
                this.dorm = res.data;
                this.lat = res.data.dorm.latitude || "";
                this.lng = res.data.dorm.longitude || "";
                this.rooms = res.data.dorm.rooms || [];
                this.amenities = res.data.dorm.amenities || [];
                this.rulesAndPolicy = res.data.dorm.rules_and_policy || [];
                this.landlordname =
                    res.data.landlord?.firstname +
                    " " +
                    res.data.landlord?.lastname;
                this.landlord_id = res.data.landlord?.landlordID;
                this.totalCapacity = res.data.totalcapacity;
                this.is_deactivated = res.data.landlord?.is_deactivated;
                this.$refs.loader.loading = false;
            } catch (error) {
                console.error("Error fetching dormitory:", error);
            }
        },
        formatDate(dateStr) {
            if (!dateStr) return "";
            const options = { year: "numeric", month: "long", day: "numeric" };
            return new Date(dateStr).toLocaleDateString("en-US", options);
        },

        fillForm() {
            this.firstname = "";
            this.lastname = "";
            this.email = "";
            this.contactInfo = "";
            this.age = "";
            this.sex = "";
            this.idPicturePreview = "";
            this.idPictureFile = "";
            this.selectedRoomId = "";
        },
        getInformationData() {
            const data = {
                firstname: this.firstname,
                lastname: this.lastname,
                email: this.email,
                contactInfo: this.contactInfo,
                age: this.age,
                sex: this.sex,
                idPicturePreview: this.idPicturePreview,
                idPictureFile: this.idPictureFile,
            };
            localStorage.setItem("tenantInfo", JSON.stringify(data)); // ✅ Store it
        },
        async submitTenantInformation() {
            this.$refs.loader.loading = true;
            const formData = new FormData();
            formData.append("firstname", this.firstname);
            formData.append("lastname", this.lastname);
            formData.append("contactInfo", this.contactInfo);
            formData.append("age", this.age);
            formData.append("sex", this.sex);
            formData.append("email", this.email);
            try {
                const response = await axios.post(
                    "/tenant-information",
                    formData,
                    {
                        withCredentials: true,
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.errors = {};
                    this.VisibleImagePostModal = true;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                }
            }
        },
        async tenantIdpicture() {
            this.$refs.loader.loading = true;

            const formData = new FormData();
            formData.append("tenant_picture", this.idPictureFile);

            try {
                const response = await axios.post(
                    "/tenant-idPicture",
                    formData,
                    {
                        withCredentials: true,
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                if (response.data.status === "success") {
                    const imageUrl = response.data.image_url;

                    const data = {
                        firstname: this.firstname,
                        lastname: this.lastname,
                        email: this.email,
                        contactInfo: this.contactInfo,
                        age: this.age,
                        sex: this.sex,
                        idPicturePreview: this.idPicturePreview,
                        imageUrl: imageUrl,
                    };
                    localStorage.setItem("tenantInfo", JSON.stringify(data));
                    this.$refs.loader.loading = false;
                    this.VisibleImagePostModal = false;
                    window.location.href = `/room-selection/${this.dormitory_id}/${this.tenant_id}`;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    const errorMessages = Object.values(
                        error.response.data.errors,
                    )
                        .flat()
                        .join(" ");
                    this.$refs.toast.showToast(errorMessages, "danger");
                }
            }
        },
        closeImageModal() {
            this.VisibleImagePostModal = false;
            this.idPicturePreview = "";
            this.idPictureFile = "";
            this.isImage = true;
        },

        handleidPictre(event) {
            const file = event.target.files[0];
            if (file) {
                if (this.idPicturePreview) {
                    URL.revokeObjectURL(this.idPicturePreview); // Clear old preview
                }

                this.idPictureFile = file;
                this.isImage = false;
                this.idPicturePreview = URL.createObjectURL(file);
            }
        },

        triggeridPictureImage() {
            if (this.$refs.idPicturesInput) {
                this.$refs.idPicturesInput.click();
            }
        },

        removeidPicture() {
            if (this.idPicturePreview) {
                URL.revokeObjectURL(this.idPicturePreview);
            }
            this.idPicturePreview = null;
            this.isImage = true;
            // Add null check for safety
            if (this.$refs.idPicturePreview) {
                this.$refs.idPicturePreview.value = ""; // Reset file input
            }
        },

        messagePage() {
            const url = `/tenant-message/${this.tenant_id}?landlord_id=${this.landlord_id}`;
            window.location.href = url;
        },

        initMap() {
            const mapDiv = document.getElementById("map");
            const dormMap = { lat: this.lat, lng: this.lng };
            const customStyle = [
                {
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{ visibility: "off" }],
                },
            ];

            // Initialize the map first
            const mapLapu = new google.maps.Map(mapDiv, {
                zoom: 15,
                center: dormMap,
                draggable: false,
                styles: customStyle,
            });

            // Now place the marker
            new google.maps.Marker({
                position: dormMap,
                map: mapLapu,
                title: this.dorm.dorm.dorm_name || "Dorm Location",
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png",
                    scaledSize: new google.maps.Size(40, 40),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(20, 40),
                },
            });
        },
        dormLocation() {
            if (!window.google || !window.google.maps) {
                const script = document.createElement("script");
                script.src =
                    "https://maps.googleapis.com/maps/api/js?key=AIzaSyCbVSKsv35IGFWYg9C96B5swf6UaVj9IGQ&callback=initMap";
                script.async = true;
                window.initMap = () => this.initMap(); // 👈 Fix here

                document.head.appendChild(script);
            } else {
                this.initMap();
            }
        },
        async fetchStats() {
            try {
                const res = await axios.get(
                    `/dorms/${this.dormitory_id}/review-stats`,
                );
                this.totalReviewers = res.data.total_reviewers;
                this.averagePercentage = res.data.average_percentage;
            } catch (err) {
                console.error(err);
            }
        },
        getStarClass(starNumber) {
            const rating = this.averagePercentage / 20; // convert % to 5-star scale
            if (starNumber <= Math.floor(rating)) {
                return "bi bi-star-fill";
            } else if (starNumber - rating <= 0.5) {
                return "bi bi-star-half";
            } else {
                return "bi bi-star";
            }
        },
        clickRatingandReview() {
            window.location.href = `/rating/reviews/${this.dormitory_id}/${this.tenant_id}`;
        },

        async roomDetails(id) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get(`/roomDetail/${id}`);
                if (response.data.success) {
                    this.selectedRoomDetails = response.data.roomDetail;
                    this.roomDetailsModal = true;
                    this.$refs.loader.loading = false;
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async fillupTenant() {
            try {
                const res = await axios.get("/fillup/tenant", {
                    params: { tenant_id: this.tenant_id },
                });
                this.filluptenant = res.data.tenant;
                this.firstname = this.filluptenant.firstname;
                this.lastname = this.filluptenant.lastname;
                this.contactInfo = this.filluptenant.phoneNumber;
                this.email = this.filluptenant.email;
                this.age = 20;
                this.sex = this.filluptenant.gender;
            } catch (error) {
                console.error("Error fetching tenant fill-up data:", error);
            }
        },
        async askAI() {
            try {
                this.$refs.loader.loading = true;

                const res = await axios.get(
                    `/get/dorm/askai/${this.dormitory_id}`,
                );
                if (res.data.status === "success") {
                    this.askAIModal = true;
                    this.selectedDormAI = res.data.data;
                    this.$refs.loader.loading = false;

                    console.log(this.selectedDormAI);
                }
            } catch (error) {
            } finally {
            }
        },
        async sendToAI() {
            if (!this.aiQuestion || !this.aiQuestion.trim()) {
                this.aiResponse =
                    "⚠️ Please enter a question before asking AI.";
                return;
            }

            this.$refs.loader.loading = true;

            try {
                const formdata = new FormData();

                formdata.append("question", this.aiQuestion.trim());
                formdata.append("dormID", this.dormitory_id);

                const res = await axios.post("/send/ai", formdata, {
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (res.data.success) {
                    this.aiResponse = res.data.answer || "No answer received.";
                } else {
                    this.aiResponse =
                        res.data.error ||
                        res.data.message ||
                        "⚠️ AI could not process your request.";
                }
            } catch (error) {
                console.error(
                    "AI REQUEST ERROR:",
                    error.response?.data || error,
                );

                this.aiResponse =
                    error.response?.data?.error ||
                    error.response?.data?.message ||
                    "❌ Something went wrong while contacting AI.";
            } finally {
                this.$refs.loader.loading = false;
            }
        },
    },
    watch: {
        dorm(newVal) {
            if (
                newVal &&
                newVal.dorm &&
                newVal.dorm.latitude &&
                newVal.dorm.longitude
            ) {
                this.lat = parseFloat(newVal.dorm.latitude);
                this.lng = parseFloat(newVal.dorm.longitude);

                this.$nextTick(() => {
                    this.dormLocation(); // or this.initMap() if already loaded
                });
            }
        },
    },
    computed: {
        displayedAmenities() {
            return this.amenitiesShowMore
                ? this.amenities
                : this.amenities.slice(0, 3);
        },
        displayedRulesAndPolicy() {
            return this.rulesAndPolicyShowMore
                ? this.rulesAndPolicy
                : this.rulesAndPolicy.slice(0, 3);
        },
        displayedRooms() {
            return this.rooms.slice(0, this.visibleRooms);
        },
    },
};
</script>

<style scoped src="../../../../css/tenant/roomdetails.css">
/* Header styles */
</style>
