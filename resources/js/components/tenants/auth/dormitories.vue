.
<template>
    <Loader ref="loader" />
    <NotificationList ref="toastRef" />
    <button
        class="floating-recommendation"
        :class="{ active: isOpen }"
        title="AI Recommendations"
        @click="isOpen = !isOpen"
    >
        <i :class="isOpen ? 'bi bi-x-lg' : 'bi bi-stars'"></i>
    </button>

    <!-- Slide-Out AI Assistant Panel -->

    <aside v-show="isOpen" class="floating-ai-panel">
        <!-- AI Header -->
        <div
            class="ai-panel-header d-flex align-items-center justify-content-between"
        >
            <div class="d-flex align-items-center gap-2">
                <div class="ai-header-icon">
                    <i class="bi bi-robot"></i>
                </div>

                <div>
                    <h6 class="mb-0 fw-bold">Smart Assistant</h6>
                    <small class="ai-header-subtitle"
                        >DormDash AI Recommendations</small
                    >
                </div>
            </div>

            <button
                class="btn-close btn-close-white"
                @click="isOpen = false"
                aria-label="Close"
            ></button>
        </div>

        <!-- AI Question Input -->
        <div class="ai-input-container">
            <div class="input-group ai-input-group">
                <input
                    v-model="question"
                    type="text"
                    class="form-control border-0 shadow-none"
                    placeholder="Ask for dorm recommendations..."
                    @keyup.enter="aiQuestion"
                    :disabled="aiLoading"
                />

                <button
                    @click="aiQuestion"
                    class="btn ai-ask-button"
                    :disabled="aiLoading || !question.trim()"
                >
                    <span
                        v-if="aiLoading"
                        class="spinner-border spinner-border-sm me-1"
                        role="status"
                    ></span>

                    <i v-else class="bi bi-send-fill me-1"></i>

                    {{ aiLoading ? "Thinking..." : "Ask" }}
                </button>
            </div>

            <small class="text-muted d-block mt-2">
                <i class="bi bi-lightbulb me-1"></i>
                Ask about price, room type, location, or amenities.
            </small>
        </div>

        <!-- AI Scroll Content -->
        <div class="ai-scroll-content">
            <!-- AI Response -->
            <div class="ai-response-card mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="ai-response-icon">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </div>

                    <span class="fw-bold text-dash-blue small">
                        AI Response
                    </span>
                </div>

                <p class="mb-0 ai-response-text">
                    {{
                        chatresponse ||
                        "Hi! Tell me what price range, room type, or location you prefer. I'll help you find suitable dormitories."
                    }}
                </p>
            </div>

            <!-- Suggested Properties Heading -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="ai-section-title mb-0">Suggested Properties</h6>

                <span v-if="dormReccomend.length > 0" class="ai-count-badge">
                    {{ dormReccomend.length }}
                    {{ dormReccomend.length === 1 ? "Property" : "Properties" }}
                </span>
            </div>

            <!-- AI Loading -->
            <div v-if="aiLoading" class="ai-loading-container">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>

                <p class="fw-bold text-dash-blue mb-1">
                    Finding the best dormitories...
                </p>

                <small class="text-muted">
                    Please wait while our AI processes your request.
                </small>
            </div>

            <!-- Suggested Properties List -->
            <div
                v-else-if="dormReccomend.length > 0"
                class="d-flex flex-column gap-3"
            >
                <div
                    v-for="(dorm, idx) in dormReccomend"
                    :key="dorm.dormID || idx"
                    class="dorm-ai-card"
                >
                    <div class="p-3">
                        <!-- Dorm Name -->
                        <div
                            class="d-flex justify-content-between align-items-start gap-2 mb-2"
                        >
                            <h6 class="dorm-ai-name mb-0">
                                {{ dorm.dormName }}
                            </h6>

                            <span class="gender-badge">
                                <i class="bi bi-people-fill me-1"></i>
                                {{ dorm.occupancyType }}
                            </span>
                        </div>

                        <!-- Address -->
                        <div
                            class="d-flex align-items-start gap-2 dorm-address"
                        >
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>{{ dorm.address }}</span>
                        </div>

                        <!-- Rooms -->
                        <div
                            v-if="dorm.rooms && dorm.rooms.length"
                            class="rooms-section"
                        >
                            <div class="rooms-heading">
                                <span>
                                    <i class="bi bi-door-open me-1"></i>
                                    Room Details
                                </span>

                                <span class="room-count">
                                    {{ dorm.rooms.length }}
                                    {{
                                        dorm.rooms.length === 1
                                            ? "Room"
                                            : "Rooms"
                                    }}
                                </span>
                            </div>

                            <!-- Individual Room -->
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-for="(room, rIdx) in dorm.rooms"
                                    :key="room.roomID || rIdx"
                                    class="room-ai-card"
                                >
                                    <!-- Room Name and Availability -->
                                    <div
                                        class="d-flex justify-content-between align-items-start gap-2"
                                    >
                                        <div class="room-info">
                                            <div class="room-name">
                                                Room {{ room.roomNumber }}
                                            </div>

                                            <small class="room-type">
                                                {{ room.type }}
                                            </small>
                                        </div>

                                        <span
                                            class="room-status"
                                            :class="
                                                String(
                                                    room.availability,
                                                ).toLowerCase() === 'available'
                                                    ? 'status-available'
                                                    : 'status-occupied'
                                            "
                                        >
                                            <i
                                                class="bi me-1"
                                                :class="
                                                    String(
                                                        room.availability,
                                                    ).toLowerCase() ===
                                                    'available'
                                                        ? 'bi-check-circle-fill'
                                                        : 'bi-x-circle-fill'
                                                "
                                            ></i>

                                            {{ room.availability }}
                                        </span>
                                    </div>

                                    <!-- Room Price -->
                                    <div class="room-price-container">
                                        <span class="room-price">
                                            ₱{{
                                                Number(
                                                    room.price || 0,
                                                ).toLocaleString("en-PH", {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2,
                                                })
                                            }}
                                        </span>

                                        <small class="room-price-label">
                                            / month
                                        </small>
                                    </div>

                                    <!-- Room Features -->
                                    <div
                                        v-if="
                                            room.features &&
                                            room.features.length
                                        "
                                        class="room-features"
                                    >
                                        <span
                                            v-for="(
                                                feature, fIdx
                                            ) in room.features"
                                            :key="feature.id || fIdx"
                                            class="room-feature-badge"
                                        >
                                            <i
                                                class="bi bi-check2-circle me-1"
                                            ></i>

                                            {{
                                                typeof feature === "object"
                                                    ? feature.featureName
                                                    : feature
                                            }}
                                        </span>
                                    </div>

                                    <div v-else class="no-features">
                                        <i class="bi bi-info-circle me-1"></i>
                                        No amenities listed.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Rooms -->
                        <div v-else class="no-rooms-message">
                            <i class="bi bi-info-circle me-1"></i>
                            No room information available.
                        </div>

                        <!-- View Property -->
                        <button
                            class="view-property-button"
                            @click="viewDormsDetails(dorm.dormID)"
                            :disabled="!dorm.dormID"
                        >
                            <i class="bi bi-eye me-1"></i>
                            View Property
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="ai-empty-state">
                <div class="ai-empty-icon">
                    <i class="bi bi-building"></i>
                </div>

                <h6>No Suggested Properties</h6>

                <p class="mb-0">
                    Ask our Smart Assistant to find dormitories that match your
                    preferences.
                </p>
            </div>
        </div>
    </aside>

    <!-- Main Workspace View -->
    <div class="container-fluid d-flex flex-column gap-4">
        <div class="flex-grow-1" style="overflow-x: hidden">
            <!-- Hero Header Section -->
            <div class="container-fluid mb-4">
                <div class="text-center animate__animated animate__fadeIn">
                    <span
                        class="badge rounded-pill bg-blue-soft text-dash-blue mb-2 px-3 py-2 fw-semibold"
                    >
                        <i class="bi bi-patch-check-fill me-1"></i> Verified
                        Stays
                    </span>
                    <h1 class="display-6 fw-bold mb-2">
                        Find Your Ideal Dorm in
                        <span class="text-dash-orange">Surigao City</span>
                    </h1>
                    <p
                        class="text-muted lead-sm mx-auto"
                        style="max-width: 600px"
                    >
                        Browse verified listings, filter by amenities, or
                        consult our AI matching tool to find your next home.
                    </p>
                </div>
            </div>

            <!-- Most Watched Carousel Section -->
            <section class="most-watched-section mb-5 px-2">
                <div
                    class="d-flex justify-content-between align-items-center mb-3 px-1"
                >
                    <h5
                        class="fw-bold text-dash-blue mb-0 d-flex align-items-center"
                    >
                        <i class="bi bi-fire text-dash-orange me-2"></i>Most
                        Watched
                    </h5>
                    <div
                        class="d-flex align-items-center gap-1 text-muted x-small italic"
                    >
                        <span>Swipe to browse</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>

                <div class="scroll-content d-flex gap-3 pb-2">
                    <div
                        class="card watched-dorm-card border-0 rounded-4 overflow-hidden shadow-sm"
                        v-for="(dorm, index) in mostwatchdorm"
                        :key="index"
                        @click="viewDormsDetails(dorm.dormID)"
                    >
                        <div
                            class="position-relative card-img-wrap"
                            style="height: 140px"
                        >
                            <img
                                :src="
                                    dorm?.images?.mainImage ||
                                    dorm?.mainImage ||
                                    '/images/default-dorm.webp'
                                "
                                class="w-100 h-100 object-fit-cover"
                                :alt="dorm.dormName"
                            />
                            <div class="position-absolute top-0 start-0 m-2">
                                <span
                                    class="badge glass-rating px-2 py-1 rounded-pill text-white x-small"
                                >
                                    <i class="bi bi-eye-fill me-1"></i
                                    >{{ dorm.views || 0 }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-3 d-flex flex-column">
                            <h6 class="fw-bold text-dark text-truncate mb-1">
                                {{ dorm.dormName }}
                            </h6>
                            <p
                                class="card-text text-muted x-small mb-3 text-truncate"
                            >
                                <i
                                    class="bi bi-geo-alt-fill text-dash-orange me-1"
                                ></i>
                                {{ dorm.address || "Surigao City" }}
                            </p>
                            <button
                                class="btn btn-sm btn-filter-pill w-100 mt-auto"
                            >
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Search & Filters Container -->
            <div class="py-2 px-2">
                <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                    <!-- Search Box -->
                    <div class="search-wrapper mb-4">
                        <div
                            class="input-group rounded-pill border px-3 bg-light"
                        >
                            <span
                                class="input-group-text bg-transparent border-0"
                            >
                                <i class="bi bi-search text-primary fs-5"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control border-0 bg-transparent shadow-none py-3"
                                placeholder="Search by location, street, or dorm name..."
                                v-model="searchQuery"
                                @input="debouncedSearch"
                            />
                        </div>
                    </div>

                    <!-- Quick Filters Buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button
                            class="btn btn-filter-pill"
                            :class="{ active: selectedButtons === 'All' }"
                            @click="btnAllFilter"
                        >
                            <i class="bi bi-grid-fill me-1"></i> All ({{
                                numberdorms
                            }})
                        </button>

                        <button
                            class="btn btn-filter-pill"
                            :class="{ active: selectedButtons === 'Surigao' }"
                            @click="btnCityFilter('Surigao')"
                        >
                            Surigao City ({{ surigao_dorms || 0 }})
                        </button>
                    </div>

                    <!-- Advanced Dropdown Filter Grid -->
                    <p
                        class="fw-bold text-muted x-small text-uppercase mb-3 d-flex align-items-center"
                    >
                        <i class="bi bi-sliders me-2"></i>Filter Attributes
                    </p>

                    <div class="filter-wrapper my-4 p-4 rounded-4 shadow-sm">
                        <div class="row g-3">
                            <!-- Price Filter -->
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="form-floating custom-floating">
                                    <select
                                        class="form-select custom-select shadow-none"
                                        v-model="selectedPriceRange"
                                        @change="
                                            drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8
                                        "
                                    >
                                        <option value="all">All Prices</option>
                                        <option value="0-500">
                                            &#8369;0 - &#8369;500
                                        </option>
                                        <option value="501-1000">
                                            &#8369;501 - &#8369;1000
                                        </option>
                                        <option value="1001-1500">
                                            &#8369;1001 - &#8369;1500
                                        </option>
                                        <option value="1501+">
                                            &#8369;1501 &amp; up
                                        </option>
                                    </select>
                                    <label
                                        ><i class="bi bi-tag-fill me-1"></i
                                        >Price</label
                                    >
                                </div>
                            </div>

                            <!-- Occupancy Filter -->
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="form-floating custom-floating">
                                    <select
                                        class="form-select custom-select shadow-none"
                                        v-model="selectedOccupancyType"
                                        @change="
                                            drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8
                                        "
                                    >
                                        <option value="all">All Types</option>
                                        <option value="Male">Male Only</option>
                                        <option value="Female">
                                            Female Only
                                        </option>
                                        <option value="Mixed">
                                            Mixed/Co-ed
                                        </option>
                                    </select>
                                    <label
                                        ><i class="bi bi-people-fill me-1"></i
                                        >Occupancy</label
                                    >
                                </div>
                            </div>

                            <!-- Amenities Filter -->
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="form-floating custom-floating">
                                    <select
                                        class="form-select custom-select shadow-none"
                                        v-model="selectedAmenity"
                                        @change="
                                            drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8
                                        "
                                    >
                                        <option value="">Select Amenity</option>
                                        <option
                                            v-for="amenity in amenitiesList"
                                            :key="amenity.id"
                                            :value="amenity.id"
                                        >
                                            {{ amenity.aminityName }}
                                        </option>
                                    </select>
                                    <label
                                        ><i class="bi bi-wifi me-1"></i
                                        >Amenities</label
                                    >
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="form-floating custom-floating">
                                    <select
                                        class="form-select custom-select shadow-none"
                                        v-model="selectedAvailability"
                                        @change="
                                            dropdownAvailabilityRecommendations
                                        "
                                    >
                                        <option value="all">All Status</option>
                                        <option value="Available">
                                            Available Only
                                        </option>
                                        <option value="Not Available">
                                            Fully Booked
                                        </option>
                                    </select>
                                    <label
                                        ><i
                                            class="bi bi-calendar-check me-1"
                                        ></i
                                        >Status</label
                                    >
                                </div>
                            </div>

                            <!-- Rating Filter -->
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="form-floating custom-floating">
                                    <select
                                        class="form-select custom-select shadow-none"
                                        v-model="selectedRating"
                                        @change="
                                            drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8
                                        "
                                    >
                                        <option value="all">Any Rating</option>
                                        <option value="5">
                                            &#9733;&#9733;&#9733;&#9733;&#9733;
                                            (5 Stars)
                                        </option>
                                        <option value="4">
                                            &#9733;&#9733;&#9733;&#9733;&#9734;
                                            (4+ Stars)
                                        </option>
                                        <option value="3">
                                            &#9733;&#9733;&#9733;&#9734;&#9734;
                                            (3+ Stars)
                                        </option>
                                    </select>
                                    <label
                                        ><i class="bi bi-star-fill me-1"></i
                                        >Min. Rating</label
                                    >
                                </div>
                            </div>

                            <!-- Sort Filter -->
                            <div class="col-6 col-md-4 col-lg-2">
                                <div class="form-floating custom-floating">
                                    <select
                                        class="form-select custom-select shadow-none"
                                        v-model="sortBy"
                                        @change="sortDateDropDown"
                                    >
                                        <option value="new-old">
                                            Newest First
                                        </option>
                                        <option value="old-new">
                                            Oldest First
                                        </option>
                                    </select>
                                    <label
                                        ><i class="bi bi-sort-down me-1"></i
                                        >Sort By</label
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Property Grid -->
            <div class="container-fluid px-2">
                <div class="row g-4 mb-5">
                    <div
                        class="col-12 col-sm-6 col-md-4 col-lg-3 animate__animated animate__fadeInUp"
                        v-for="(dorm, dormID) in dormitories"
                        :key="dormID"
                    >
                        <div
                            class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden dorm-main-card"
                        >
                            <div class="position-relative card-img-wrap">
                                <img
                                    :src="
                                        dorm?.images?.mainImage ||
                                        dorm?.mainImage ||
                                        '/images/default-dorm.webp'
                                    "
                                    class="w-100"
                                    :alt="dorm.dormName"
                                    style="height: 200px; object-fit: cover"
                                />

                                <div
                                    class="position-absolute top-0 start-0 m-2"
                                >
                                    <span
                                        class="badge rounded-pill shadow-sm px-3 py-2 text-white"
                                        :class="
                                            dorm.availability === 'Available'
                                                ? 'bg-success'
                                                : 'bg-secondary'
                                        "
                                    >
                                        <i
                                            class="bi me-1"
                                            :class="
                                                dorm.availability ===
                                                'Available'
                                                    ? 'bi-check-circle-fill'
                                                    : 'bi-dash-circle-fill'
                                            "
                                        ></i>
                                        {{ dorm.availability }}
                                    </span>
                                </div>

                                <div
                                    v-if="boolrate"
                                    class="position-absolute bottom-0 end-0 m-2"
                                >
                                    <div
                                        class="glass-rating px-2 py-1 rounded-3 text-white small"
                                    >
                                        <i
                                            class="bi bi-star-fill text-warning me-1"
                                        ></i
                                        >{{ dorm.rating_percentage }}%
                                    </div>
                                </div>
                            </div>

                            <div class="card-body d-flex flex-column p-4">
                                <div class="mb-3">
                                    <h5
                                        class="fw-bold text-dark mb-1 text-truncate"
                                    >
                                        {{ dorm.dormName }}
                                    </h5>
                                    <p
                                        class="text-muted small mb-0 text-truncate"
                                    >
                                        <i
                                            class="bi bi-geo-alt-fill text-dash-orange me-1"
                                        ></i
                                        >{{ dorm.address }}
                                    </p>
                                </div>

                                <div
                                    class="d-flex justify-content-between align-items-center mb-4 bg-light p-2.5 rounded-3"
                                >
                                    <div
                                        class="text-center flex-fill border-end"
                                    >
                                        <small
                                            class="d-block text-muted x-small"
                                            >TYPE</small
                                        >
                                        <span
                                            class="fw-bold small text-dash-blue"
                                            >{{ dorm.occupancyType }}</span
                                        >
                                    </div>
                                    <div class="text-center flex-fill">
                                        <small
                                            class="d-block text-muted x-small"
                                            >STARTING AT</small
                                        >
                                        <span class="fw-bold small text-success"
                                            >₱{{ dorm.price }}</span
                                        >
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <button
                                        class="btn btn-filter-pill active w-100 py-2 shadow-sm fw-bold d-flex align-items-center justify-content-center gap-2"
                                        @click="viewDormsDetails(dorm.dormID)"
                                    >
                                        <span>Explore Details</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Pagination -->
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination justify-content-center gap-2">
                        <li
                            class="page-item"
                            :class="{ disabled: currentPage === 1 }"
                        >
                            <a
                                class="page-link border-0 shadow-sm rounded-circle p-pagination d-flex align-items-center justify-content-center"
                                href="#"
                                @click.prevent="goToPage(currentPage - 1)"
                            >
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <li
                            class="page-item"
                            v-for="page in totalPages"
                            :key="page"
                        >
                            <a
                                class="page-link border-0 shadow-sm rounded-4 px-3 py-2 fw-bold"
                                :class="
                                    currentPage === page
                                        ? 'bg-dash-blue text-white'
                                        : 'bg-white text-dark'
                                "
                                href="#"
                                @click.prevent="goToPage(page)"
                            >
                                {{ page }}
                            </a>
                        </li>

                        <li
                            class="page-item"
                            :class="{ disabled: currentPage === totalPages }"
                        >
                            <a
                                class="page-link border-0 shadow-sm rounded-circle p-pagination d-flex align-items-center justify-content-center"
                                href="#"
                                @click.prevent="goToPage(currentPage + 1)"
                            >
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import _ from "lodash";
import debounce from "lodash/debounce";
import Loader from "@/components/loader.vue";
import NotificationList from "@/components/notifications.vue";

export default {
    components: {
        Loader,
        NotificationList,
    },
    name: "ProductGrid",
    data() {
        return {
            isOpen: false,
            dormitories: [],
            filteredDorms: [],
            searchQuery: "",
            selectedPriceRange: "",
            selectedOccupancyType: "",
            recommendations: [],
            recommendloading: false,
            isGenderBased: false,
            recommend: "",
            dorms: [],
            dormReccomend: [],
            question: "",
            chatresponse: "",
            mostwatchdorm: [],
            tenant_id: "",
            notifications: [],
            receiverID: "",
            //new filters
            surigao_dorms: [],
            rooms: [],
            numberdorms: [],
            currentFilter: null, // e.g. 'all', 'city', 'price', etc.
            currentFilterParams: {},
            selectedAmenity: "",
            selectedRating: "",
            sortBy: "",
            selectedAvailability: "",
            currentPage: 1,
            totalPages: 1,
            itemsPerPage: 12,
            selectedButtons: "",
            amenitiesList: [],
            amenitieslength: false,
            boolrate: false,
            aiLoading: false,
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
        //filter
        async btnAllFilter() {
            this.selectedAmenity = "";
            this.selectedRating = "";
            this.sortBy = "";
            this.selectedAvailability = "";
            this.selectedOccupancyType = "";
            this.selectedPriceRange = "";
            this.rooms = [];
            this.boolrate = false;
            this.amenitieslength = false;
            this.dormListingfetch();
        },
        async btnCityFilter(city, page = 1) {
            this.selectedButtons = city;

            this.currentFilter = "city";
            this.currentFilterParams = { city };
            this.selectedAmenity = "";
            this.selectedRating = "";
            this.sortBy = "";
            this.selectedAvailability = "";
            this.selectedOccupancyType = "";
            this.selectedPriceRange = "";
            this.rooms = [];
            this.boolrate = false;
            this.amenitieslength = false;
            this.$refs.loader.loading = true;
            const response = await axios.get("/select-cities", {
                params: {
                    city: this.selectedButtons,
                    page: page,
                    per_page: this.itemsPerPage,
                },
            });
            this.currentPage = response.data.dorms.current_page;
            this.totalPages = response.data.dorms.last_page;
            this.dormitories = response.data.dorms.data;
            this.$refs.loader.loading = false;
        },

        async drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8(page = 1) {
            this.selectedButtons = "";
            this.selectedAmenity = "";
            this.currentFilter = "price";
            this.selectedRating = "";
            this.sortBy = "";
            this.selectedAvailability = "";
            this.selectedOccupancyType = "";
            this.$refs.loader.loading = true;
            this.rooms = [];
            this.boolrate = false;
            this.amenitieslength = false;

            const [min, max] = this.getPriceRange(this.selectedPriceRange);

            try {
                const response = await fetch("/pricerecommendations", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        min_price: Number(min),
                        max_price: Number(max),
                        page: page,
                        per_page: this.itemsPerPage,
                    }),
                });

                const result = await response.json();

                if (result.status === "success") {
                    this.dormitories = result.data.data; // current page dorms
                    this.currentPage = result.data.current_page;
                    this.totalPages = result.data.last_page;
                    this.rooms = result.data.data;
                    console.log(this.rooms);
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },

        getPriceRange(range) {
            switch (range) {
                case "0-500":
                    return [0, 500];
                case "501-1000":
                    return [501, 1000];
                case "1001-1500":
                    return [1001, 1500];
                case "1501+":
                    return [1501, 999999];
                default:
                    return [0, 999999]; // fallback
            }
        },
        async drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8(page = 1) {
            try {
                this.$refs.loader.loading = true;
                this.selectedButtons = "";
                this.currentFilter = "gender";

                this.selectedPriceRange = "";
                this.selectedAmenity = "";
                this.selectedRating = "";
                this.sortBy = "";
                this.selectedAvailability = "";
                this.rooms = [];
                this.boolrate = false;
                this.amenitieslength = false;
                this.$refs.loader.loading = true;

                const response = await fetch("gender-recommendations", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        occupancy_type:
                            this.selectedOccupancyType.toLowerCase(),
                        page: page,
                        per_page: this.itemsPerPage,
                    }),
                });
                const result = await response.json();

                if (result.status === "success") {
                    this.dormitories = result.recommendations.data; // only items
                    this.currentPage = result.recommendations.current_page;
                    this.totalPages = result.recommendations.last_page;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async dropdownAmenities(page = 1) {
            try {
                this.$refs.loader.loading = true;
                this.selectedButtons = "";
                this.selectedRating = "";
                this.currentFilter = "amenity";
                this.sortBy = "";
                this.selectedAvailability = "";
                this.selectedOccupancyType = "";
                this.selectedPriceRange = "";
                this.rooms = [];
                this.boolrate = false;
                this.$refs.loader.loading = true;
                const response = await axios.post("/get/amenities", {
                    amenities: Array.isArray(this.selectedAmenity)
                        ? this.selectedAmenity
                        : [this.selectedAmenity],
                    params: { page: page, per_page: this.itemsPerPage },
                });

                // Update dorms list and count
                this.dormitories = response.data.dorms.data;
                this.currentPage = response.data.dorms.current_page;
                this.totalPages = response.data.dorms.last_page;
                this.amenitieslength = true;
            } catch (error) {
                console.error("Error fetching amenities:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async dropdownRate(page = 1) {
            try {
                this.$refs.loader.loading = true;
                this.selectedButtons = "";
                this.selectedAmenity = "";
                this.sortBy = "";
                this.selectedAvailability = "";
                this.selectedOccupancyType = "";
                this.selectedPriceRange = "";
                this.rooms = [];
                this.amenitieslength = false;
                this.$refs.loader.loading = true;
                const response = await axios.get("/get/rate", {
                    params: {
                        rating: this.selectedRating,
                        page: page,
                        per_page: this.itemsPerPage,
                    },
                });
                this.boolrate = true;
                this.dormitories = response.data.dorms.data;
                this.currentPage = response.data.dorms.current_page;
                this.totalPages = response.data.dorms.last_page;
            } catch (error) {
                console.error("Error fetching by rate:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async sortDateDropDown(page = 1) {
            try {
                this.$refs.loader.loading = true;
                this.currentFilter = "sort";
                this.selectedButtons = "";
                this.selectedAmenity = "";
                this.selectedRating = "";
                this.selectedAvailability = "";
                this.selectedOccupancyType = "";
                this.selectedPriceRange = "";
                this.rooms = [];
                this.amenitieslength = false;
                this.boolrate = false;
                this.$refs.loader.loading = true;
                const response = await axios.get("/get/sortByDate", {
                    params: {
                        sortBy: this.sortBy,
                        page: page,
                        per_page: this.itemsPerPage,
                    },
                });

                this.dormitories = response.data.dorms.data;
                this.currentPage = response.data.dorms.current_page;
                this.totalPages = response.data.dorms.last_page;
            } catch (error) {
                console.error("Error sorting by date:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async getAvailability(page = 1) {
            try {
                this.$refs.loader.loading = true;
                this.selectedButtons = "";
                this.currentFilter = "availability";
                this.selectedAmenity = "";
                this.selectedRating = "";
                this.sortBy = "";
                this.selectedOccupancyType = "";
                this.selectedPriceRange = "";
                this.rooms = [];
                this.amenitieslength = false;
                this.boolrate = false;
                this.$refs.loader.loading = true;
                const response = await axios.get("/get/availability", {
                    params: {
                        availability: this.selectedAvailability,
                        page: page,
                        per_page: this.itemsPerPage,
                    },
                });

                this.dormitories = response.data.dorms.data;
                this.currentPage = response.data.dorms.current_page;
                this.totalPages = response.data.dorms.last_page;
            } catch (error) {
                console.error("Error fetching by availability:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },

        async dormListingfetch(page = 1) {
            try {
                this.selectedButtons = "All";
                this.$refs.loader.loading = true;

                const response = await axios.get("/list-dorms", {
                    params: {
                        page: page,
                        per_page: this.itemsPerPage,
                    },
                });

                this.currentPage = response.data.dorms.current_page;
                this.totalPages = response.data.dorms.last_page;

                this.dormitories = response.data.dorms.data || [];

                this.numberdorms = response.data.total_dorms || 0;
                this.surigao_dorms = response.data.surigao_dorms || 0;

                this.rooms = [];

                console.log("Surigao Dormitories:", this.dormitories);
                console.log("Total Dormitories:", this.numberdorms);
            } catch (error) {
                console.error(
                    "Error fetching Surigao dorms:",
                    error.response?.data || error.message,
                );
            } finally {
                this.$refs.loader.loading = false;
            }
        },

        viewDormsDetails(dormitoryId) {
            this.tenant_id = window.tenant_id;
            window.location.href = `/room-details/${dormitoryId}/${this.tenant_id}`;
        },

        async goToPage(page) {
            if (page < 1 || page > this.totalPages) return;

            // Always call the right filter function based on currentFilter
            switch (this.currentFilter) {
                case "city":
                    await this.btnCityFilter(
                        this.currentFilterParams.city,
                        page,
                    );
                    break;
                case "price":
                    await this.drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8(page);
                    break;
                case "gender":
                    await this.drahJ91ZuNL8Y2px8iYciYeHN8sfSh5eXH8(page);
                    break;
                case "amenity":
                    await this.dropdownAmenities(page);
                    break;
                case "rating":
                    await this.dropdownRate(page);
                    break;
                case "sort":
                    await this.sortDateDropDown(page);
                    break;
                case "availability":
                    await this.getAvailability(page);
                    break;
                case "search":
                    await this.searchLocations(page);
                    break;
                default:
                    await this.dormListingfetch(page);
            }

            this.currentPage = page; // Update currentPage for UI
        },
        async searchLocations(page = 1) {
            try {
                this.$refs.loader.loading = true;
                this.selectedButtons = "";
                this.currentFilter = "search";
                this.selectedAmenity = "";
                this.selectedRating = "";
                this.sortBy = "";
                this.selectedAvailability = "";
                this.selectedOccupancyType = "";
                this.selectedPriceRange = "";
                this.rooms = [];
                this.amenitieslength = false;
                this.boolrate = false;

                this.$refs.loader.loading = true;
                if (!this.searchQuery.trim()) {
                    await this.dormListingfetch(); // re-fetch all dorms
                    return;
                }

                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content");
                const response = await fetch("/search-locations", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({
                        location: this.searchQuery,
                        page: page,
                        per_page: this.itemsPerPage,
                    }),
                });

                const result = await response.json();
                if (result.status === "success") {
                    this.dormitories = result.recommendations.data;
                    this.currentPage = result.recommendations.current_page; // must access from recommendations
                    this.totalPages = result.recommendations.last_page; // also from recommendations
                    this.$refs.loader.loading = false;
                } else {
                    console.error(
                        "Server responded with error:",
                        result.message,
                    );
                    this.dormitories = [];
                    this.$refs.loader.loading = false;
                }
            } catch (err) {
                console.error("Search failed:", err);
                this.filteredDorms = [];
                this.$refs.loader.loading = false;
            }
        },

        async aiQuestion() {
            if (!this.question.trim() || this.aiLoading) return;

            this.aiLoading = true;

            const userQuestion = this.question.trim();

            try {
                const response = await axios.post(
                    "/ai/question/reccomendations",
                    {
                        question: userQuestion,
                    },
                );

                this.chatresponse =
                    response.data.message ||
                    "Here are your dormitory recommendations.";

                const recs = response.data.recommendations || [];

                this.dormReccomend = recs.map((d) => ({
                    dormID: d.dormID,
                    dormName: d.dormName || "Unnamed Dorm",
                    address: d.address || "Surigao City",
                    occupancyType: d.occupancyType || "Mixed",

                    rooms: (d.rooms || []).map((r) => ({
                        roomNumber: r.roomNumber || "N/A",
                        type: r.type || r.roomType || "Standard",
                        price: r.price ?? "Contact landlord",
                        availability: r.availability || "Unknown",

                        features: Array.isArray(r.features)
                            ? r.features
                            : typeof r.features === "string"
                              ? r.features.split(",").map((f) => f.trim())
                              : [],
                    })),
                }));

                console.log("AI Recommendations:", this.dormReccomend);

                console.log("AI Response:", this.chatresponse);

                this.question = "";
            } catch (error) {
                console.error(
                    "AI Recommendation Error:",
                    error.response?.data || error.message,
                );

                this.chatresponse =
                    error.response?.data?.message ||
                    "Unable to get AI recommendations. Please try again.";

                this.dormReccomend = [];
            } finally {
                this.aiLoading = false;
            }
        },

        async mostWatchDorm() {
            try {
                this.tenant_id = window.tenant_id; // siguro naa ni sa global js
                const response = await axios.get(
                    `/most/watched/dorm/${this.tenant_id}`,
                );
                this.mostwatchdorm = response.data.dorm;
                // I-update imong UI with this.mostwatchdorm
                console.log("Most watched dorm:", this.mostwatchdorm);
            } catch (error) {
                console.error("Error fetching most watched dorm:", error);
            }
        },
    },
    created() {
        this.debouncedSearch = debounce(this.searchLocations, 500);
    },
    mounted() {
        this.dormListingfetch();
        this.mostWatchDorm();
        this.tenant_id = window.tenant_id;
        this.subscribeToNotifications();
        axios.get("/fetch-amenities").then((response) => {
            this.amenitiesList = response.data.amenities;
        });
    },
};
</script>
<style scoped src="../../../../css/tenant/dormitory.css"></style>
