<template>
    <div class="px-4 mt-5">
        <Loader ref="loader" />
        <NotificationList ref="toastRef" />
        <Toastcomponents ref="toast" />

        <!-- Header -->
        <div class="d-flex justify-content-end align-items-center mb-4">
            <div class="d-flex justify-content-end align-items-center mb-4">
                <div
                    class="glass-toolbar d-flex align-items-center gap-2 p-1-5 rounded-pill shadow-sm"
                >
                    <button
                        @click="viewDormitories"
                        class="btn-modern btn-blue-soft d-flex align-items-center gap-2 px-4 py-2 rounded-pill"
                    >
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span class="fw-700 small-caps">View Dormitories</span>
                    </button>

                    <div class="v-divider"></div>

                    <button
                        @click="AddModal()"
                        class="btn-modern btn-orange-glow d-flex align-items-center gap-2 px-4 py-2 rounded-pill"
                    >
                        <div class="pulse-icon">
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <span class="fw-800 small-caps">New Room</span>
                    </button>
                </div>
            </div>
        </div>
        <div
            class="filter-toolbar mb-4 p-3 bg-white rounded-4 shadow-sm border border-light-subtle"
        >
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="filter-group">
                        <label class="filter-label"
                            ><i class="bi bi-house-door me-2"></i>Room
                            Type</label
                        >
                        <select
                            class="form-select-modern"
                            v-model="selectedRoomType"
                            @change="filterRoomsByRoomType"
                        >
                            <option disabled value="">Select Type</option>
                            <option value="all">All Room Types</option>
                            <option value="Single Room">Single Room</option>
                            <option value="Double Room">Double Room</option>
                            <option value="Bedspace / Multi-Sharing Room">
                                Bedspace
                            </option>
                            <option value="Studio-Type Room">
                                Studio-Type
                            </option>
                            <option
                                value="Partitioned Bedspace (Cubicle Style)"
                            >
                                Cubicle Style
                            </option>
                            <option value="Loft Room / Mezzanine Type">
                                Loft / Mezzanine
                            </option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 col-lg-2">
                    <div class="filter-group">
                        <label class="filter-label"
                            ><i class="bi bi-calendar-check me-2"></i
                            >Status</label
                        >
                        <select
                            class="form-select-modern"
                            v-model="selectedAvailability"
                            @change="filterRoomsByAvailability"
                        >
                            <option disabled value="">Select Status</option>
                            <option value="all">All Availability</option>
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Under Maintenance">
                                Maintenance
                            </option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="filter-group">
                        <label class="filter-label"
                            ><i class="bi bi-people me-2"></i>Gender</label
                        >
                        <select
                            class="form-select-modern"
                            v-model="selectedGender"
                            @change="filterRoomsByGender"
                        >
                            <option disabled value="">Select Preference</option>
                            <option value="all">All Preferences</option>
                            <option value="Male Only">Male Only</option>
                            <option value="Female Only">Female Only</option>
                            <option value="Any Gender">Any Gender</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="filter-group">
                        <label class="filter-label"
                            ><i class="bi bi-building me-2"></i>Dormitory
                            Location</label
                        >
                        <select
                            class="form-select-modern select-brand-orange"
                            v-model="selectedDormitory"
                            @change="filterRoomsByDormitory"
                        >
                            <option disabled value="">Select Dormitory</option>
                            <option value="all">All Dormitories</option>
                            <option
                                v-for="dorm in dorms"
                                :key="dorm.dormID"
                                :value="dorm.dormID"
                            >
                                {{ dorm.dormName }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div
            v-if="!rooms.length"
            class="empty-state-container d-flex flex-column justify-content-center align-items-center animate-fade-in"
        >
            <div class="illustration-box mb-4">
                <div class="circle-bg"></div>
                <i class="bi bi-search-heart brand-icon-glow"></i>
                <div class="floating-particle p1"></div>
                <div class="floating-particle p2"></div>
            </div>

            <h4 class="fw-800 text-dark mb-2">No Rooms Found</h4>
            <p class="text-muted text-center px-4" style="max-width: 350px">
                We couldn't find any rooms matching your current filters. Try
                adjusting your search or
                <span
                    class="text-brand-orange fw-bold cursor-pointer"
                    @click="resetFilters"
                    >reset filters</span
                >.
            </p>

            <button
                class="btn btn-outline-brand-blue rounded-pill px-4 mt-2 shadow-sm"
                @click="AddModal"
            >
                <i class="bi bi-plus-lg me-2"></i> Add New Room
            </button>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mt-2 mb-3">
            <div class="col" v-for="room in rooms" :key="room.roomID">
                <div
                    class="card h-100 premium-room-card border-0 shadow-sm rounded-4 overflow-hidden"
                    @click="ViewRoom(room.roomID)"
                >
                    <div
                        class="card-badges position-absolute top-0 start-0 w-100 p-3 d-flex justify-content-between align-items-start z-2"
                    >
                        <span class="price-tag shadow-sm"
                            >₱{{ room.price }}<small>/mo</small></span
                        >

                        <div class="d-flex flex-column gap-2 align-items-end">
                            <button
                                class="btn-delete-blur shadow-sm"
                                @click.stop="deleteRoom(room.roomID)"
                                title="Delete"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <span
                                class="badge-status shadow-sm"
                                :class="
                                    room.availability
                                        .toLowerCase()
                                        .replace(' ', '-')
                                "
                            >
                                {{ room.availability }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-4 pt-5">
                        <div class="mb-3 mt-2">
                            <h5 class="fw-800 text-dark mb-1">
                                Room #{{ room.roomNumber }}
                            </h5>
                            <p
                                class="text-muted small d-flex align-items-center gap-1"
                            >
                                <i
                                    class="bi bi-geo-alt-fill text-brand-blue"
                                ></i>
                                {{ room.dorm?.dormName }}
                            </p>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="feature-item">
                                    <i class="bi bi-door-open"></i>
                                    <span>{{ room.roomType }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-item">
                                    <i class="bi bi-gender-ambiguous"></i>
                                    <span>{{ room.genderPreference }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-brand-blue-soft w-100 rounded-3 fw-bold py-2"
                                @click.stop="editRoom(room.roomID)"
                            >
                                <i class="bi bi-pencil-square me-2"></i>Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-if="lastPage > 1"
            class="d-flex justify-content-center align-items-center my-4"
        >
            <nav
                aria-label="Page navigation"
                class="pagination-container p-2 shadow-sm"
            >
                <ul class="pagination gap-2 mb-0 border-0">
                    <li
                        :class="[
                            'page-item border-0',
                            { disabled: currentPage === 1 },
                        ]"
                    >
                        <button
                            class="btn-nav"
                            :disabled="currentPage === 1"
                            @click="handlePagination(currentPage - 1)"
                            aria-label="Previous"
                        >
                            <i class="bi bi-chevron-left"></i>
                            <span class="ms-1 d-none d-sm-inline">Prev</span>
                        </button>
                    </li>

                    <li class="page-item d-flex align-items-center mx-2">
                        <div class="page-indicator">
                            <span class="current-text">Page</span>
                            <span class="badge-page mx-2">{{
                                currentPage
                            }}</span>
                            <span class="total-text text-muted"
                                >of {{ lastPage }}</span
                            >
                        </div>
                    </li>

                    <li
                        :class="[
                            'page-item border-0',
                            { disabled: currentPage === lastPage },
                        ]"
                    >
                        <button
                            class="btn-nav"
                            :disabled="currentPage === lastPage"
                            @click="handlePagination(currentPage + 1)"
                            aria-label="Next"
                        >
                            <span class="me-1 d-none d-sm-inline">Next</span>
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </li>
                </ul>
            </nav>
        </div>

        <!--modal add room-->
        <div
            v-if="VisibleAddModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0, 20, 50, 0.6); backdrop-filter: blur(8px)"
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content border-0 shadow-2xl rounded-5 overflow-hidden"
                >
                    <div
                        class="modal-header border-0 p-4 bg-white d-flex align-items-center justify-content-between"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-orange">
                                <i class="bi bi-plus-lg"></i>
                            </div>
                            <div>
                                <h4 class="fw-800 text-dark mb-0">
                                    Create New Room
                                </h4>
                                <p class="text-muted small mb-0">
                                    Complete the details below to add a new
                                    listing
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close-custom"
                            @click="VisibleAddModal = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="modal-body p-4 p-md-5 bg-light-subtle">
                        <div class="row g-5">
                            <div class="col-lg-5">
                                <label class="form-label-custom"
                                    >Room Presentation</label
                                >
                                <div
                                    class="upload-zone rounded-4 mb-2"
                                    :class="{
                                        'has-image': roomImagePreview,
                                        'border-danger pulse-error':
                                            errors.roomImageFile,
                                    }"
                                    @click="triggerroomImagePreview3"
                                >
                                    <input
                                        ref="RoomsImages3Input"
                                        class="d-none"
                                        type="file"
                                        accept="image/*"
                                        @change="handleroomImagePreview3"
                                    />

                                    <div
                                        v-if="!roomImagePreview"
                                        class="text-center py-5"
                                    >
                                        <div class="upload-icon-circle mb-3">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">
                                            Click to Upload Photo
                                        </h6>
                                        <p class="text-muted small">
                                            JPG, PNG or WEBP
                                        </p>
                                    </div>

                                    <div
                                        v-else
                                        class="preview-wrapper position-relative"
                                    >
                                        <img
                                            :src="roomImagePreview"
                                            class="img-fluid rounded-4 shadow-sm"
                                        />
                                        <button
                                            type="button"
                                            @click.stop="
                                                removeroomImagePreviews3
                                            "
                                            class="btn-remove-img"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="errors.roomImageFile"
                                    class="error-msg-premium mb-3"
                                >
                                    <i
                                        class="bi bi-exclamation-circle-fill"
                                    ></i>
                                    {{ errors.roomImageFile[0] }}
                                </div>

                                <label class="form-label-custom mt-3"
                                    >Select Dormitory</label
                                >
                                <div class="dropdown w-100">
                                    <button
                                        class="btn btn-dorm-selector w-100 d-flex justify-content-between align-items-center p-3 rounded-4 bg-white shadow-sm"
                                        :class="{
                                            'border-danger': errors.dormsId,
                                        }"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                    >
                                        <span
                                            class="d-flex align-items-center gap-2"
                                        >
                                            <i
                                                class="bi bi-building text-brand-blue"
                                            ></i>
                                            <span class="fw-600 text-dark">{{
                                                selectedDormName ||
                                                "Select a Dormitory"
                                            }}</span>
                                        </span>
                                        <i
                                            class="bi bi-chevron-down small text-muted"
                                        ></i>
                                    </button>
                                    <ul
                                        class="dropdown-menu w-100 border-0 shadow-lg p-2 rounded-4"
                                    >
                                        <li
                                            v-for="dorm in dorms"
                                            :key="dorm.dormID"
                                        >
                                            <a
                                                class="dropdown-item rounded-3 py-2"
                                                href="#"
                                                @click.prevent="dormId(dorm)"
                                            >
                                                <div
                                                    class="d-flex justify-content-between align-items-center"
                                                >
                                                    <span>{{
                                                        dorm.dormName
                                                    }}</span>
                                                    <span
                                                        class="badge bg-light text-dark border"
                                                        >ID:
                                                        {{ dorm.dormID }}</span
                                                    >
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <input type="hidden" v-model="dormsId" />
                                <div
                                    v-if="errors.dormsId"
                                    class="error-msg-premium mt-2"
                                >
                                    <i
                                        class="bi bi-exclamation-circle-fill"
                                    ></i>
                                    {{ errors.dormsId[0] }}
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group-premium">
                                            <label>Room Number</label>
                                            <input
                                                type="text"
                                                class="form-control-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.roomNumber,
                                                }"
                                                v-model="roomNumber"
                                                @input="
                                                    roomNumber =
                                                        roomNumber.replace(
                                                            /[^0-9]/g,
                                                            '',
                                                        )
                                                "
                                                placeholder="e.g. 101"
                                            />
                                            <div
                                                v-if="errors.roomNumber"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.roomNumber[0] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-premium">
                                            <label>Monthly Rate (₱)</label>
                                            <input
                                                type="number"
                                                class="form-control-premium highlight-orange"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.price,
                                                }"
                                                v-model="price"
                                                placeholder="0.00"
                                                step="0.01"
                                            />
                                            <div
                                                v-if="errors.price"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.price[0] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-premium">
                                            <label>Room Type</label>
                                            <select
                                                class="form-select-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.roomType,
                                                }"
                                                v-model="roomType"
                                            >
                                                <option value="" disabled>
                                                    Select Type
                                                </option>
                                                <option value="Single Room">
                                                    Single Room
                                                </option>
                                                <option
                                                    value="Double Room / Shared Room"
                                                >
                                                    Double Room / Shared
                                                </option>
                                                <option
                                                    value="Bedspace / Multi-Sharing Room"
                                                >
                                                    Bedspace
                                                </option>
                                                <option
                                                    value="Studio-Type Room"
                                                >
                                                    Studio-Type Room
                                                </option>
                                                <option
                                                    value="Partitioned Bedspace (Cubicle Style)"
                                                >
                                                    Partitioned Bedspace
                                                </option>
                                                <option
                                                    value="Loft Room / Mezzanine Type"
                                                >
                                                    Loft / Mezzanine
                                                </option>
                                            </select>
                                            <div
                                                v-if="errors.roomType"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.roomType[0] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-premium">
                                            <label>Availability Status</label>
                                            <select
                                                class="form-select-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.availability,
                                                }"
                                                v-model="availability"
                                            >
                                                <option value="" selected>
                                                    Select Status
                                                </option>
                                                <option
                                                    v-for="slot in availibilityArray"
                                                    :key="slot"
                                                    :value="slot"
                                                >
                                                    {{ slot }}
                                                </option>
                                            </select>
                                            <div
                                                v-if="errors.availability"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.availability[0] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group-premium">
                                            <label>Area (sqm)</label>
                                            <input
                                                type="number"
                                                class="form-control-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.area_sqm,
                                                }"
                                                v-model="area_sqm"
                                            />
                                            <div
                                                v-if="errors.area_sqm"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.area_sqm[0] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-group-premium">
                                            <label>Bed Type</label>
                                            <select
                                                class="form-select-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.listing_type,
                                                }"
                                                v-model="listing_type"
                                            >
                                                <option
                                                    value=""
                                                    disabled
                                                    selected
                                                >
                                                    Select Bed Type
                                                </option>
                                                <option
                                                    v-for="bed in filteredBeds"
                                                    :key="bed"
                                                    :value="bed"
                                                >
                                                    {{ bed }}
                                                </option>
                                            </select>
                                            <div
                                                v-if="errors.listing_type"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.listing_type[0] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-premium">
                                            <label>Furnishing Status</label>
                                            <select
                                                class="form-select-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.furnishing_status,
                                                }"
                                                v-model="furnishing_status"
                                            >
                                                <option value="" disabled>
                                                    Select Status
                                                </option>
                                                <option value="Fully Furnished">
                                                    Fully Furnished
                                                </option>
                                                <option value="Semi Furnished">
                                                    Semi Furnished
                                                </option>
                                                <option value="Unfurnished">
                                                    Unfurnished
                                                </option>
                                            </select>
                                            <div
                                                v-if="errors.furnishing_status"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{
                                                    errors.furnishing_status[0]
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-premium">
                                            <label>Gender Preference</label>
                                            <select
                                                class="form-select-premium"
                                                :class="{
                                                    'is-invalid-premium':
                                                        errors.gender_preference,
                                                }"
                                                v-model="gender_preference"
                                            >
                                                <option value="" disabled>
                                                    Select Gender
                                                </option>
                                                <option value="Male Only">
                                                    Male Only
                                                </option>
                                                <option value="Female Only">
                                                    Female Only
                                                </option>
                                                <option value="Any Gender">
                                                    Any Gender
                                                </option>
                                            </select>
                                            <div
                                                v-if="errors.gender_preference"
                                                class="error-msg-premium mt-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{
                                                    errors.gender_preference[0]
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <button
                                        type="submit"
                                        @click="submitRoom"
                                        class="btn-submit-premium w-100 shadow-sm"
                                    >
                                        <span>Publish Room Listing</span>
                                    </button>
                                    <button
                                        class="btn btn-link w-100 mt-2 text-muted text-decoration-none small"
                                        @click="VisibleAddModal = false"
                                    >
                                        Discard and Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="visibleRoomFeaturesModal"
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 20, 50, 0.6); backdrop-filter: blur(8px)"
        @click.self="visibleRoomFeaturesModal = false"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div
                class="modal-content border-0 shadow-2xl rounded-5 overflow-hidden"
            >
                <div
                    class="modal-header border-0 p-4 bg-white d-flex align-items-center justify-content-between"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-blue">
                            <i class="fa-solid fa- star text-brand-blue"></i>
                        </div>
                        <div>
                            <h5 class="fw-800 text-dark mb-0">Room Features</h5>
                            <p class="text-muted x-small mb-0">
                                Highlight what makes this room special
                                (Optional)
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close-custom"
                        @click="visibleRoomFeaturesModal = false"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body p-4 p-md-4 bg-light-subtle">
                    <div class="feature-list mb-4">
                        <div
                            v-for="(feature, index) in roomFeatures"
                            :key="index"
                            class="mb-3"
                        >
                            <div
                                class="d-flex align-items-center gap-2 group-premium"
                            >
                                <div class="form-floating flex-grow-1">
                                    <input
                                        type="text"
                                        class="form-control-premium shadow-sm"
                                        v-model="roomFeatures[index]"
                                        :id="'feature' + index"
                                        placeholder="e.g. Free Wi-Fi"
                                    />
                                </div>

                                <button
                                    v-if="roomFeatures.length > 1"
                                    @click="roomFeatures.splice(index, 1)"
                                    class="btn-remove-feature"
                                    title="Remove"
                                >
                                    <i class="bi bi-dash-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mb-3 px-1"
                        v-if="errors.roomFeatures || errors.features"
                    >
                        <div
                            class="error-badge p-2 rounded-3 mb-1"
                            v-if="errors.roomFeatures"
                        >
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            {{ errors.roomFeatures[0] }}
                        </div>
                        <div
                            class="error-badge p-2 rounded-3"
                            v-if="errors.features"
                        >
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            {{ errors.features[0] }}
                        </div>
                    </div>

                    <div class="action-footer mt-4">
                        <button
                            class="btn-add-premium w-100"
                            @click="addRoomFeatures"
                            :disabled="roomFeatures.length >= 4"
                            :class="{
                                'opacity-50 cursor-not-allowed':
                                    roomFeatures.length >= 4,
                            }"
                        >
                            <div
                                class="d-flex align-items-center justify-content-center gap-2"
                            >
                                <i class="fa-solid fa-plus-circle"></i>
                                <span>{{
                                    roomFeatures.length >= 4
                                        ? "Maximum Features Reached"
                                        : "Add Another Feature"
                                }}</span>
                            </div>
                        </button>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 bg-white">
                    <button
                        class="btn-submit-premium w-100"
                        @click="visibleRoomFeaturesModal = false"
                    >
                        Save Features
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--Delete Modal--->

    <div
        v-if="VisibleUpdateModal"
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 20, 50, 0.7); backdrop-filter: blur(10px)"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div
                class="modal-content border-0 shadow-2xl rounded-5 overflow-hidden"
            >
                <div
                    class="modal-header border-0 p-4 bg-white d-flex align-items-center justify-content-between"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-blue bg-warning-subtle">
                            <i
                                class="fa-solid fa-pen-to-square text-warning"
                            ></i>
                        </div>
                        <div>
                            <h4 class="fw-800 text-dark mb-0">
                                Update Room Details
                            </h4>
                            <p class="text-muted small mb-0">
                                Modify room specifications and media
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close-custom"
                        @click="VisibleUpdateModal = false"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div
                    class="modal-body p-4 bg-light-subtle"
                    style="max-height: 80vh; overflow-y: auto"
                >
                    <div class="row mb-4">
                        <div class="col-12">
                            <div
                                class="upload-container-premium position-relative"
                            >
                                <div
                                    v-if="editData.roomImagePreview"
                                    class="preview-hero mb-3"
                                >
                                    <img
                                        :src="editData.roomImagePreview"
                                        alt="Room Preview"
                                        class="img-fluid rounded-4 shadow-sm"
                                        style="
                                            height: 350px;
                                            width: 100%;
                                            object-fit: cover;
                                        "
                                    />
                                    <div
                                        class="edit-overlay"
                                        @click="edittriggerroomImagePreview3"
                                    >
                                        <i class="bi bi-camera-fill me-2"></i>
                                        Change Photo
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="upload-placeholder p-5 text-center rounded-4 border-dashed"
                                    @click="edittriggerroomImagePreview3"
                                    style="cursor: pointer"
                                >
                                    <i
                                        class="bi bi-cloud-arrow-up display-4 text-primary"
                                    ></i>
                                    <h5 class="mt-3 fw-700">
                                        Update Room Image
                                    </h5>
                                    <p class="text-muted small">
                                        Click to browse or drag and drop
                                    </p>
                                </div>

                                <input
                                    ref="editRoomsImages3Input"
                                    class="d-none"
                                    type="file"
                                    accept="image/*"
                                    @change="edithandleroomImagePreview3"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="form-section-card h-100">
                                <h6 class="section-label">Identity & Size</h6>

                                <div class="form-floating mb-3">
                                    <input
                                        type="text"
                                        class="form-control-premium bg-light"
                                        readonly
                                        v-model="editData.fkdormID"
                                    />
                                </div>

                                <div class="form-floating mb-1">
                                    <input
                                        type="text"
                                        class="form-control-premium"
                                        :class="{
                                            'is-invalid':
                                                errors.editData?.roomNumber,
                                        }"
                                        id="roomNumber"
                                        placeholder="Room Number"
                                        v-model="editData.roomNumber"
                                    />
                                </div>
                                <div
                                    class="text-danger x-small mb-3"
                                    v-if="errors.editData?.roomNumber"
                                >
                                    <i
                                        class="bi bi-exclamation-circle-fill"
                                    ></i>
                                    {{ errors.editData.roomNumber[0] }}
                                </div>

                                <div class="form-floating mb-1">
                                    <input
                                        type="number"
                                        class="form-control-premium"
                                        :class="{
                                            'is-invalid':
                                                errors.editData?.areaSqm,
                                        }"
                                        id="areaSqm"
                                        v-model="editData.areaSqm"
                                        placeholder="Area"
                                    />
                                </div>
                                <div
                                    class="text-danger x-small mb-3"
                                    v-if="errors.editData?.areaSqm"
                                >
                                    <i
                                        class="bi bi-exclamation-circle-fill"
                                    ></i>
                                    {{ errors.editData.areaSqm[0] }}
                                </div>

                                <div class="form-floating">
                                    <select
                                        class="form-select-premium"
                                        v-model="editData.roomType"
                                    >
                                        <option value="Single Room">
                                            Single Room
                                        </option>
                                        <option
                                            value="Double Room / Shared Room"
                                        >
                                            Double Room / Shared Room
                                        </option>
                                        <option
                                            value="Bedspace / Multi-Sharing Room"
                                        >
                                            Bedspace / Multi-Sharing Room
                                        </option>
                                        <option value="Studio-Type Room">
                                            Studio-Type Room
                                        </option>
                                        <option value="Loft Room">
                                            Loft Room
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-section-card h-100">
                                <h6 class="section-label">
                                    Availability & Terms
                                </h6>

                                <div class="form-floating mb-1">
                                    <input
                                        type="number"
                                        class="form-control-premium"
                                        v-model="editData.price"
                                        placeholder="Price"
                                    />
                                </div>
                                <div
                                    class="text-danger x-small mb-3"
                                    v-if="errors.editData?.price"
                                >
                                    <i
                                        class="bi bi-exclamation-circle-fill"
                                    ></i>
                                    {{ errors.editData.price[0] }}
                                </div>

                                <div class="form-floating mb-3">
                                    <select
                                        class="form-select-premium"
                                        v-model="editData.availability"
                                    >
                                        <option value="Available">
                                            Available
                                        </option>
                                        <option value="Under Maintenance">
                                            Under Maintenance
                                        </option>
                                        <option value="Occupied">
                                            Occupied
                                        </option>
                                        <option value="Reserved">
                                            Reserved
                                        </option>
                                    </select>
                                </div>

                                <div class="form-floating mb-3">
                                    <select
                                        class="form-select-premium"
                                        v-model="editData.furnishing_status"
                                    >
                                        <option value="Fully Furnished">
                                            Fully Furnished
                                        </option>
                                        <option value="Semi Furnished">
                                            Semi Furnished
                                        </option>
                                        <option value="Unfurnished">
                                            Unfurnished
                                        </option>
                                    </select>
                                </div>

                                <div class="form-floating mb-4">
                                    <select
                                        class="form-select-premium"
                                        v-model="editData.genderPreference"
                                    >
                                        <option value="Male Only">
                                            Male Only
                                        </option>
                                        <option value="Female Only">
                                            Female Only
                                        </option>
                                        <option value="Any Gender">
                                            Any Gender
                                        </option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button
                                        class="btn btn-premium-secondary"
                                        @click="
                                            AllowedRoomReservation(
                                                editData.roomID,
                                            )
                                        "
                                    >
                                        <i class="bi bi-shield-check me-2"></i>
                                        Enable Reservation
                                    </button>
                                    <button
                                        @click="updateRoom"
                                        class="btn btn-premium-primary"
                                    >
                                        <i class="bi bi-cloud-check me-2"></i>
                                        Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div
                                class="form-section-card h-100 bg-white shadow-sm border-0"
                            >
                                <h6 class="section-label">Room Features</h6>

                                <div class="input-group mb-2">
                                    <input
                                        type="text"
                                        class="form-control-premium border-end-0 rounded-start-4"
                                        v-model="newRoomFeature"
                                        placeholder="Add feature..."
                                    />
                                    <button
                                        class="btn btn-primary rounded-end-4 px-3"
                                        @click="addnewRoomFeatures()"
                                    >
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div
                                    class="text-danger x-small mb-3"
                                    v-if="errors.newRoomFeature"
                                >
                                    <i
                                        class="bi bi-exclamation-circle-fill"
                                    ></i>
                                    {{ errors.newRoomFeature[0] }}
                                </div>

                                <div class="feature-table-container mt-3">
                                    <div class="table-header-custom">
                                        <span>Feature Name</span>
                                        <span>Action</span>
                                    </div>
                                    <div class="table-body-custom">
                                        <div
                                            v-for="feature in editData.features"
                                            :key="feature.id"
                                            class="feature-item-row"
                                        >
                                            <span
                                                class="text-truncate fw-600"
                                                >{{ feature.featureName }}</span
                                            >
                                            <button
                                                class="btn-delete-small"
                                                @click.prevent="
                                                    deleteRoomFeatures(
                                                        feature.id,
                                                    )
                                                "
                                            >
                                                <i
                                                    class="bi bi-trash3 text-danger"
                                                ></i>
                                            </button>
                                        </div>
                                        <div
                                            v-if="!editData.features?.length"
                                            class="text-center py-4 text-muted small italic"
                                        >
                                            No features added yet.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Display Data Modal-->
    <div
        v-if="VisibleDisplayDataModal"
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 20, 50, 0.6); backdrop-filter: blur(12px)"
        @click.self="VisibleDisplayDataModal = false"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div
                class="modal-content border-0 shadow-2xl rounded-5 overflow-hidden"
            >
                <div
                    class="modal-header border-0 p-4 bg-white d-flex align-items-center justify-content-between"
                >
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box-blue">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-800 text-dark mb-0">Room Overview</h4>
                            <p class="text-muted small mb-0">
                                Listing ID: #{{ selectedRoom?.id }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close-custom"
                        @click="VisibleDisplayDataModal = false"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div
                    class="modal-body p-0"
                    style="max-height: 75vh; overflow-y: auto"
                >
                    <div class="position-relative overflow-hidden mb-4">
                        <img
                            :src="selectedRoom?.roomImages"
                            class="hero-image"
                            alt="Room Image"
                        />
                        <div class="image-overlay-gradient"></div>

                        <div class="position-absolute top-0 start-0 m-4"></div>
                    </div>

                    <div class="px-4 pb-5">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="info-card h-100">
                                    <h6 class="section-title">
                                        Essential Details
                                    </h6>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-door-open text-primary"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Room Number</label>
                                            <p>
                                                {{ selectedRoom?.roomNumber }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-house-door text-success"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Room Category</label>
                                            <p>{{ selectedRoom?.roomType }}</p>
                                        </div>
                                    </div>

                                    <div class="detail-row highlight-price">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-currency-dollar text-warning"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Monthly Rate</label>
                                            <p class="text-orange">
                                                ₱{{ selectedRoom?.price }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-building text-info"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Dormitory Name</label>
                                            <p>
                                                {{
                                                    selectedRoom?.dorm.dormName
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-card h-100">
                                    <h6 class="section-title">
                                        Status & Contact
                                    </h6>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-shield-check text-success"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Current Status</label>
                                            <span
                                                class="badge rounded-pill mt-1 px-3"
                                                :class="
                                                    selectedRoom?.availability ===
                                                    'Available'
                                                        ? 'bg-success-subtle text-success'
                                                        : 'bg-danger-subtle text-danger'
                                                "
                                            >
                                                {{ selectedRoom?.availability }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-envelope text-primary"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Email Support</label>
                                            <p>
                                                {{
                                                    selectedRoom?.dorm
                                                        .contactEmail
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-telephone text-info"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Phone Number</label>
                                            <p>
                                                {{
                                                    selectedRoom?.dorm
                                                        .contactPhone
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="detail-row">
                                        <div class="detail-icon">
                                            <i
                                                class="bi bi-geo-alt text-danger"
                                            ></i>
                                        </div>
                                        <div>
                                            <label>Precise Address</label>
                                            <p class="small text-muted">
                                                {{ selectedRoom?.dorm.address }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="info-card">
                                    <h6 class="section-title">
                                        Included Features
                                    </h6>
                                    <div class="d-flex flex-wrap gap-2 pt-2">
                                        <span
                                            v-for="feature in selectedRoom?.features"
                                            :key="feature.id"
                                            class="feature-tag"
                                        >
                                            <i
                                                class="bi bi-check2-circle me-1"
                                            ></i>
                                            {{ feature.featureName }}
                                        </span>
                                    </div>
                                    <div
                                        class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center"
                                    >
                                        <span class="text-muted x-small"
                                            >Date Registered:
                                            {{
                                                formatDate(
                                                    selectedRoom.created_at,
                                                )
                                            }}</span
                                        >
                                        <button
                                            class="btn btn-sm btn-outline-primary rounded-pill px-4"
                                            @click="
                                                VisibleDisplayDataModal = false
                                            "
                                        >
                                            Close Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <Toastcomponents ref="toast" />

    <Modalconfirmation ref="modal" />
</template>

<script>
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";
import Loader from "@/components/loader.vue";
import Modalconfirmation from "@/components/modalconfirmation.vue";
import NotificationList from "@/components/notifications.vue";

export default {
    components: {
        Toastcomponents,
        Loader,
        Modalconfirmation,
        NotificationList,
    },
    data() {
        return {
            notifications: [],
            VisibleAddModal: false,
            VisibleDeleteModal: false,
            VisibleUpdateModal: false,
            VisibleDisplayDataModal: false,
            VisibleImage: false,
            selectedDormitory: "",
            selectedGender: "",
            selectedAvailability: "",
            selectedRoomType: "",
            currentPage: 1,
            lastPage: 1,
            dorms: window.allRooms || [],
            dormsId: "",
            roomNumber: "",
            roomType: "",
            availability: "",
            availibilityArray: ["Available"],
            editavailibilityArray: ["Available", "Under Maintenance"],
            price: "",
            listing_type: "",
            bedOptions: {
                "Single Room": ["Single Bed", "Private Room with Bed"],
                "Double Room / Shared Room": ["Single Bed", "Shared Bed"],
                "Bedspace / Multi-Sharing Room": [
                    "Bunk Bed",
                    "Double Deck (Lower)",
                    "Double Deck (Upper)",
                ],
                "Studio-Type Room": ["Private Room with Bed"],
                "Partitioned Bedspace (Cubicle Style)": ["Single Bed"],
                "Loft Room / Mezzanine Type": ["Single Bed", "Bunk Bed"],
            },
            area_sqm: "",
            gender_preference: "",
            furnishing_status: "",
            roomImagePreview: "",
            roomImageFile: "",
            getRoomID: "",
            editSelectedID: "",
            roomFeatures: [""],
            visibleRoomFeaturesModal: false,
            editData: {
                fkdormID: "",
                roomNumber: "",
                roomType: "",
                availability: "",
                price: "",
                amenities: "",
                listingType: "",
                bedOptions: {
                    "Single Room": ["Single Bed", "Private Room with Bed"],
                    "Double Room / Shared Room": ["Single Bed", "Shared Bed"],
                    "Bedspace / Multi-Sharing Room": [
                        "Bunk Bed",
                        "Double Deck (Lower)",
                        "Double Deck (Upper)",
                    ],
                    "Studio-Type Room": ["Private Room with Bed"],
                    "Partitioned Bedspace (Cubicle Style)": ["Single Bed"],
                    "Loft Room / Mezzanine Type": ["Single Bed", "Bunk Bed"],
                },
                areaSqm: "",
                genderPreference: "",
                roomImagePreview: "",
                roomImageFile: "",
            },
            errors: {},
            rooms: [],
            selectedRoom: {},
            newRoomFeature: "",
            currentRoomID: null,
        };
    },
    methods: {
        subscribeToNotifications() {
            if (this.hasSubscribed) return; // prevent multiple subscriptions
            this.hasSubscribed = true;

            this.receiverID = this.landlord_id;
            Echo.private(`notifications.${this.receiverID}`)
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
        dormId(dorm) {
            this.dormsId = dorm.dormID;
        },
        handlePagination(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;

            // 👇 Priority check (only one filter is expected at a time)
            if (
                this.selectedAvailability &&
                this.selectedAvailability !== "all"
            ) {
                this.filterRoomsByAvailability(page);
            } else if (
                this.selectedDormitory &&
                this.selectedDormitory !== "all"
            ) {
                this.filterRoomsByDormitory(page);
            } else if (this.selectedGender && this.selectedGender !== "all") {
                this.filterRoomsByGender(page);
            } else if (
                this.selectedRoomType &&
                this.selectedRoomType !== "all"
            ) {
                this.filterRoomsByRoomType(page);
            } else {
                this.fetchRooms(page);
            }
        },

        async fetchRooms(page = 1) {
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get("/ListRooms", {
                    params: { page: page },
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                this.rooms = response.data.rooms.data;
                this.currentPage = response.data.rooms.current_page;
                this.lastPage = response.data.rooms.last_page;
                this.$refs.loader.loading = false;
            } catch (error) {
                console.error("Error fetching rooms:", error);
            }
        },
        async filterRoomsByDormitory(page = 1) {
            try {
                this.$refs.loader.loading = true;
                if (this.selectedDormitory === "all") {
                    this.fetchRooms(1);
                } else {
                    const response = await axios.get(
                        `/get-rooms-by-dorm/${this.selectedDormitory}`,
                        {
                            params: { page: page },
                            headers: {
                                "X-CSRF-TOKEN": document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content"),
                            },
                        },
                    );
                    this.rooms = response.data.rooms.data;
                    this.currentPage = response.data.rooms.current_page;
                    this.lastPage = response.data.rooms.last_page;
                    this.selectedAvailability = "";
                    this.selectedGender = "";
                    this.selectedRoomType = "";
                    this.$refs.loader.loading = false;
                }
            } catch (error) {}
        },
        async filterRoomsByGender(page = 1) {
            try {
                this.$refs.loader.loading = true;
                if (this.selectedGender === "all") {
                    this.fetchRooms(1);
                    return;
                }
                const response = await axios.get(
                    `/get-rooms-by-gender/${this.selectedGender}`,
                    {
                        params: { page: page },
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                this.selectedAvailability = "";
                this.selectedRoomType = "";
                this.selectedDormitory = "";
                this.rooms = response.data.rooms.data;
                this.currentPage = response.data.rooms.current_page;
                this.lastPage = response.data.rooms.last_page;
                this.$refs.loader.loading = false;
            } catch (error) {
                console.error("Error fetching rooms by gender:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async filterRoomsByAvailability(page = 1) {
            try {
                this.$refs.loader.loading = true;
                if (this.selectedAvailability === "all") {
                    await this.fetchRooms(1);
                    return;
                }
                const response = await axios.get(
                    `/get-rooms-by-availability/${this.selectedAvailability}`,
                    {
                        params: { page: page },
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                this.rooms = response.data.rooms.data;
                this.currentPage = response.data.rooms.current_page;
                this.lastPage = response.data.rooms.last_page;
                this.selectedGender = "";
                this.selectedRoomType = "";
                this.selectedDormitory = "";
                this.$refs.loader.loading = false;
            } catch (error) {
                console.error("Error fetching rooms by availability:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async filterRoomsByRoomType(page = 1) {
            try {
                this.$refs.loader.loading = true;
                if (this.selectedRoomType === "all") {
                    this.fetchRooms(1);
                    return;
                }
                const response = await axios.get("/get-rooms-by-room-type", {
                    params: { page: page, roomType: this.selectedRoomType },
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                this.selectedAvailability = "";
                this.selectedGender = "";
                this.selectedDormitory = "";
                this.rooms = response.data.rooms.data;
                this.currentPage = response.data.rooms.current_page;
                this.lastPage = response.data.rooms.last_page;
                this.$refs.loader.loading = false;
            } catch (error) {
                console.error("Error fetching rooms by room type:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return "";
            const options = { year: "numeric", month: "long", day: "numeric" };
            return new Date(dateString).toLocaleDateString(undefined, options);
        },
        clearFiltered() {
            this.selectedAvailability = "";
            this.selectedDormitory = "";
            this.selectedGender = "";
            this.selectedRoomType = "";
        },
        AddModal() {
            this.VisibleAddModal = true;
            this.VisibleImage = true;
            this.emptyfill();
            this.clearFiltered();
        },
        handleroomImagePreview3(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.roomImagePreview) {
                    URL.revokeObjectURL(this.roomImagePreview);
                }
                this.roomImageFile = file;
                this.VisibleImage = false;
                this.roomImagePreview = URL.createObjectURL(file);
            }
        },
        triggerroomImagePreview3() {
            if (this.$refs.RoomsImages3Input) {
                this.$refs.RoomsImages3Input.click();
            }
        },

        removeroomImagePreviews3() {
            if (this.roomImagePreview) {
                URL.revokeObjectURL(this.roomImagePreview);
            }
            this.roomImagePreview = null;
            this.roomImageFile = "";
            this.VisibleImage = true;

            // Add null check for safety
            if (this.$refs.roomImagePreview) {
                this.$refs.roomImagePreview.value = ""; // Reset file input
            }
        },

        async submitRoom() {
            // const confirmed = await this.$refs.modal.show({
            //     title: 'Confirm New Room',
            //     message: 'Do you want to add this room now?',
            //     functionName: 'Add Room'
            // });

            // if (!confirmed) {
            //     this.$refs.loader.loading = false;
            //     return;
            // }

            try {
                this.$refs.loader.loading = true;
                const formData = new FormData();
                formData.append("dormsId", this.dormsId);
                formData.append("roomNumber", this.roomNumber);
                formData.append("roomType", this.roomType);
                formData.append("availability", this.availability);
                formData.append("price", this.price);
                formData.append("listing_type", this.listing_type);
                formData.append("area_sqm", this.area_sqm);
                formData.append("gender_preference", this.gender_preference);
                formData.append("furnishing_status", this.furnishing_status);
                formData.append("roomImageFile", this.roomImageFile);
                const response = await axios.post("/addRoom", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                if (response.data.status === "success") {
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.$refs.loader.loading = false;
                    this.fetchRooms();
                    this.emptyfill();
                    this.getRoomID = response.data.room_id;
                    this.roomFeaturesModal();
                    this.VisibleAddModal = false;
                } else if (response.data.status === "error") {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(response.data.message, "danger");
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    const data = error.response.data;

                    // Show backend message in toast
                    if (data.message) {
                        this.$refs.toast.showToast(data.message, "danger");
                        this.VisibleAddModal = false;
                    }

                    // Field validation errors
                    this.errors = data.errors || {};

                    this.$refs.loader.loading = false;
                } else {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(
                        "Something went wrong.",
                        "danger",
                    );
                }
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        roomFeaturesModal() {
            this.visibleRoomFeaturesModal = true;
            this.errors = {};
        },
        async addRoomFeatures() {
            const formData = new FormData();
            formData.append("features", this.roomFeatures.join(","));
            formData.append("room_id", this.getRoomID);
            try {
                const confirmed = await this.$refs.modal.show({
                    title: "Adding Room Features",
                    message: `Are you sure you want to Add Room Features?`,
                    functionName: "Add new Room Features (Optional)",
                });

                if (!confirmed) {
                    this.$refs.loader.loading = false;
                    return;
                }

                this.$refs.loader.loading = true;

                const response = await axios.post(
                    "/add-roomfeatures",
                    formData,
                    {
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.roomFeatures = [""]; // Reset room features
                    this.errors = {};
                } else {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(response.data.message, "danger");
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                } else if (error.response && error.response.status === 400) {
                    this.errors = {
                        roomFeatures: [error.response.data.message],
                    };
                }
            }
        },

        async ViewRoom(roomId) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get(`/ViewRoom/${roomId}`, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.fetchRooms();
                    this.selectedRoom = response.data.room;
                    this.VisibleDisplayDataModal = true;
                } else {
                    console.error(
                        "Failed to fetch dorm details:",
                        response.data.message,
                    );
                }
            } catch (error) {
                console.error("Error fetching dorm details:", error);
            }
        },
        async editRoom(roomId) {
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get(`/ViewRoom/${roomId}`);
                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.editData = {
                        ...response.data.room,
                        room_id: roomId,
                        roomImagePreview: response.data.room.roomImages || "",
                    };

                    this.VisibleUpdateModal = true;
                    this.editSelectedID = roomId;
                } else {
                    console.error(
                        "Failed to fetch dorm details:",
                        response.data.message,
                    );
                    alert("Failed to load dorm details for editing");
                }
            } catch (error) {
                console.error("Error fetching dorm details:", error);
                alert("An error occurred while loading dorm details");
            }
        },
        async deleteRoomFeatures(id) {
            this.rulesId = id;
            const confirmed = await this.$refs.modal.show({
                title: "Delete Feature",
                message: "Are you sure you want to delete this Feature?",
                functionName: "Delete Feature",
            });

            if (!confirmed) return;

            this.$refs.loader.loading = true;
            if (this.rulesId) {
                try {
                    const response = await axios.delete(
                        `/delete-roomfeatures/${this.rulesId}`,
                        {
                            headers: {
                                "X-CSRF-TOKEN": document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content"),
                            },
                        },
                    );

                    this.$refs.loader.loading = false;

                    if (response.data.status === "success") {
                        this.$refs.toast.showToast(
                            response.data.message,
                            "success",
                        );
                        this.VisibleUpdateModal = false;
                    } else {
                        this.$refs.toast.showToast(
                            "Failed to delete rules. Please try again.",
                            "error",
                        );
                    }
                } catch (error) {
                    this.$refs.loader.loading = false;
                    console.error("Error deleting rules:", error);
                    console.log("Deleting rule with ID:", this.rulesId);
                }
            } else {
                console.warn("No dorm ID provided for deleting amenities.");
            }
        },
        async addnewRoomFeatures() {
            this.errors = {};
            const confirmed = await this.$refs.modal.show({
                title: "Adding New Room Feature",
                message: `Are you sure you want to add this room feature?`,
                functionName: "Add new Room Feature (Optional)",
            });
            if (!confirmed) {
                return;
            }

            this.$refs.loader.loading = true;
            try {
                const response = await axios.post(
                    "/add-roomfeatures",
                    {
                        features: this.newRoomFeature,
                        room_id: this.editData.room_id,
                    },
                    {
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(
                        `${this.newRoomFeature} added successfully.`,
                        "success",
                    );
                    this.VisibleUpdateModal = false;
                    this.newRoomFeature = "";
                    this.errors = {};
                } else if (response.data.errors) {
                    this.errors.newRoomFeature = [response.data.message];
                }
            } catch (error) {
                this.$refs.loader.loading = false;

                // Show Laravel validation or custom error message
                if (error.response && error.response.data) {
                    const res = error.response.data;

                    if (res.errors && res.errors.newRoomFeature) {
                        this.errors.newRoomFeature = res.errors.newRoomFeature;
                    } else if (res.message) {
                        this.errors.newRoomFeature = [res.message];

                        this.$refs.loader.loading = false;
                    } else {
                        this.$refs.toast.showToast(
                            "An unexpected error occurred.",
                            "error",
                        );
                        this.$refs.loader.loading = false;
                    }
                } else {
                    this.$refs.toast.showToast(
                        "An unexpected error occurred.",
                        "error",
                    );
                    this.$refs.loader.loading = false;
                }
            } finally {
                this.$refs.loader.loading = false;
            }
        },

        edithandleroomImagePreview3(event) {
            const file = event.target.files[0];
            if (file) {
                // Update both preview and file
                this.editData.roomImagePreview = URL.createObjectURL(file);
                this.editData.roomImageFile = file;
            } else {
                // If no file is selected, reset to existing preview and clear file
                this.editData.roomImageFile = null;
            }
        },
        edittriggerroomImagePreview3() {
            if (this.$refs.editRoomsImages3Input) {
                this.$refs.editRoomsImages3Input.click();
            }
        },
        async updateRoom() {
            // const confirmed = await this.$refs.modal.show({
            //     title: 'Confirm Update',
            //     message: 'Are you sure you want to update the details of this room? This action will overwrite the existing information.',
            //     functionName: 'Update Room'
            // });

            // if (!confirmed) {
            //     this.$refs.loader.loading = false;
            //     return;
            // }

            this.$refs.loader.loading = true;

            const formData = new FormData();
            formData.append("dormitory_id", this.editData.fkdormID);
            formData.append("room_number", this.editData.roomNumber);
            formData.append("room_type", this.editData.roomType);
            formData.append("availability", this.editData.availability);
            formData.append("price", this.editData.price);
            formData.append("listing_type", this.editData.listingType);
            formData.append("area_sqm", this.editData.areaSqm);
            formData.append(
                "gender_preference",
                this.editData.genderPreference,
            );
            formData.append(
                "furnishing_status",
                this.editData.furnishing_status,
            );
            if (this.editData.roomImageFile) {
                // User selected a new image file, send it
                formData.append("roomImageFile", this.editData.roomImageFile);
                formData.append("isNewImage", "true"); // Optional flag to indicate new image uploaded
            } else if (this.editData.roomImagePreview) {
                // No new file, send the current image path or name to keep it
                formData.append(
                    "existingImage",
                    this.editData.roomImagePreview,
                );
                formData.append("isNewImage", "false"); // Optional flag
            } else {
                // No image at all — handle accordingly (maybe send empty or alert user)
                formData.append("existingImage", "");
                formData.append("isNewImage", "false");
            }
            try {
                const response = await axios.post(
                    `/update-room/${this.editSelectedID}`,
                    formData,
                    {
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                if (response.data.status === "success") {
                    this.handlePagination(this.currentPage);
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.VisibleUpdateModal = false;
                    this.errors = {};
                } else if (response.data.errors) {
                    this.errors = response.data.errors;
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    const laravelErrors = error.response.data.errors;
                    this.errors.editData = {};

                    // Convert snake_case to camelCase
                    for (const key in laravelErrors) {
                        const camelKey = key.replace(/_([a-z])/g, (g) =>
                            g[1].toUpperCase(),
                        );
                        this.errors.editData[camelKey] = laravelErrors[key];
                    }

                    this.$refs.loader.loading = false;
                }
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async deleteRoom(roomId) {
            this.currentRoomID = roomId;
            const confirmed = await this.$refs.modal.show({
                title: "Delete Room?",
                message: "This will permanently remove the room. Proceed?",
                functionName: "Delete Room",
            });

            if (!confirmed) {
                this.$refs.loader.loading = false;
                return;
            }
            this.$refs.loader.loading = true; // Show loading indicator
            try {
                const response = await axios.delete(
                    `/DeleteRoom/${this.currentRoomID}`,
                    {},
                    {
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                if (response.data.status === "success") {
                    this.fetchRooms();

                    this.$refs.loader.loading = false;

                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );

                    this.VisibleDeleteModal = false; // Close the modal
                } else {
                    this.$refs.toast.showToast(response.data.message, "error");
                }
            } catch (error) {
                console.error("Error deleting room:", error);
                this.$refs.toast.showToast("Failed to delete room.", "error");
            } finally {
                this.$refs.loader.loading = false; // Hide loading indicator
            }
        },
        updateEmpty() {
            this.editData = {
                room_id: "",
                room_number: "",
                room_type: "",
                availability: "",
                price: "",
                listing_type: "",
                area_sqm: "",
                gender_preference: "",
                roomImagePreview: "",
                roomImageFile: "",
            };
            this.errors = {};
        },
        emptyfill() {
            this.dormsId = "";
            this.roomNumber = "";
            this.roomType = "";
            this.availability = "";
            this.price = "";
            this.furnishing_status = "";
            this.listing_type = "";
            this.area_sqm = "";
            this.gender_preference = "";
            this.roomImagePreview = "";
            this.roomImageFile = "";
            this.errors = {};
        },
        viewDormitories() {
            window.location.href = `/landlordDormManagement/${this.landlord_id}`;
        },
        async AllowedRoomReservation(id) {
            try {
                const confirmed = await this.$refs.modal.show({
                    title: "Allow Room Reservation?",
                    message: "Do you want to allow reservations for this room?",
                    functionName: "Confirm",
                });

                if (!confirmed) {
                    this.$refs.loader.loading = false;
                    return;
                }
                this.$refs.loader.loading = true;
                const response = await axios.post(
                    `/rooms/allow-reserve/${id}`,
                    {},
                    {
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                if (response.data.success) {
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.VisibleUpdateModal = false;
                }
            } catch (error) {
                console.error(error);
                alert("Something went wrong.");
            } finally {
                this.$refs.loader.loading = false;
            }
        },
    },
    mounted() {
        const element = document.getElementById("landlordroomManagement"); // ✅ define it
        this.landlord_id = element.dataset.landlordId;
        this.subscribeToNotifications();
        this.handlePagination();
        // this.fetchRooms();
    },
    watch: {
        ListRooms(newVal) {
            if (newVal !== "") {
                this.filterMode = "list";
                this.handlePagination(1);
            }
        },
        selectedDormitory(newVal) {
            this.filterRoomsByDormitory(newVal);
        },
        selectedGender(newVal) {
            this.filterRoomsByGender(newVal);
        },
        selectedAvailability(newVal) {
            this.filterRoomsByAvailability(newVal);
        },
        selectedRoomType(newVal) {
            this.filterRoomsByRoomType(newVal);
        },
    },
    computed: {
        filteredBeds() {
            return this.bedOptions[this.roomType] || [];
        },
        editfilteredBeds() {
            return this.bedOptions[this.editData.roomType] || [];
        },
    },
    watch: {},
};
</script>

<style scoped src="../../../../css/landlord/roomManagement.css"></style>
<style scoped src="../../../../css/partials/pagination.css"></style>
