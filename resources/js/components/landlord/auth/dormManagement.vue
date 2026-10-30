<template>
    <Loader ref="loader" />
    <NotificationList ref="toastRef" />

    <div class="container mt-5">
        <div class="d-flex justify-content-end align-items-center mb-4">
            <div class="d-flex gap-3 flex-wrap">
                <button
                    class="btn btn-modern-blue shadow-sm"
                    @click="ViewRoomsPage"
                >
                    <div class="icon-circle-white me-2">
                        <i class="bi bi-door-open-fill"></i>
                    </div>
                    <span>View Rooms</span>
                </button>

                <button
                    class="btn btn-modern-orange shadow-sm"
                    @click="VisibleAddModal = true"
                    :disabled="!isVerified"
                    :title="
                        isVerified ? 'Add Dormitory' : 'Account not verified'
                    "
                >
                    <div class="icon-circle-white me-2">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <span>Add Dorm</span>
                </button>
            </div>
        </div>
        <div v-if="!isVerified" class="verification-banner mb-4">
            <div class="banner-glass-effect"></div>
            <div
                class="d-flex align-items-center gap-4 position-relative z-index-2"
            >
                <div class="alert-icon-box">
                    <i class="bi bi-shield-lock-fill"></i>
                    <div class="pulse-ring"></div>
                </div>

                <div class="flex-grow-1">
                    <h6
                        class="fw-800 text-dark mb-1 text-uppercase letter-spacing-1"
                    >
                        Account Verification Required
                    </h6>
                    <p class="mb-0 small text-muted opacity-75">
                        Unlock full access to dormitory management and premium
                        security features by verifying your identity.
                    </p>
                </div>

                <button
                    class="btn btn-verify-now"
                    @click="goToVerificationPage"
                >
                    <span>Verify Identity</span>
                    <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </div>
        </div>

        <div
            class="filter-glass-bar d-flex align-items-center gap-2 mb-4 shadow-sm"
        >
            <div
                class="search-segment d-flex align-items-center px-3 flex-grow-1"
            >
                <i class="bi bi-search text-primary-blue me-2"></i>
                <input
                    type="text"
                    class="form-control border-0 shadow-none bg-transparent"
                    placeholder="Search Dormitories name..."
                    v-model="searchTerm"
                />
            </div>

            <div class="v-divider"></div>
            <div class="filter-segment d-none d-md-flex me-2">
                <i class="bi bi-check-circle text-muted me-1"></i>
                <select
                    class="form-select border-0 shadow-none bg-transparent py-0"
                    v-model="selectedAvailability"
                    @change="dropdownAvailability"
                >
                    <option value="" disabled>Availability</option>
                    <option value="all">All</option>
                    <option value="Available">Available</option>
                    <option value="Not Available">Not Available</option>
                </select>
            </div>
        </div>
        <!-- Search Bar -->

        <div v-if="dorms.length === 0" class="empty-state-card py-5">
            <div class="text-center px-4">
                <div class="icon-stack mb-4">
                    <div class="icon-pulse"></div>
                    <i class="bi bi-house-exclamation-fill"></i>
                </div>

                <h5 class="fw-800 text-dark mb-2">No Results Found</h5>
                <p
                    class="text-muted small mx-auto mb-4"
                    style="max-width: 320px"
                >
                    We couldn't find any dormitories matching
                    <span class="text-primary-blue fw-600"
                        >"{{ searchTerm }}"</span
                    >. Try adjusting your search or filters.
                </p>

                <button
                    class="btn btn-reset-filters shadow-sm"
                    @click="resetFilters"
                >
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Clear All Filters
                </button>
            </div>
        </div>
        <!-- Table -->
        <div v-else class="table-container shadow-sm">
            <div class="table-responsive custom-scrollbar">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="align-middle">
                            <th scope="col" class="ps-4">#</th>
                            <th scope="col">Dormitory Name</th>
                            <th scope="col">Address</th>
                            <th scope="col">Contact Info</th>
                            <th scope="col" class="text-center">Rooms</th>
                            <th scope="col" class="text-center pe-4">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="(dorm, index) in dorms"
                            :key="dorm.dormID"
                            class="align-middle"
                        >
                            <td class="ps-4 fw-bold text-primary-blue">
                                {{ dorm.dormID }}
                            </td>
                            <td>
                                <span class="fw-700 text-dark">{{
                                    dorm.dormName
                                }}</span>
                            </td>
                            <td>
                                <div
                                    class="text-truncate text-muted small"
                                    style="max-width: 180px"
                                    :title="dorm.address"
                                >
                                    <i
                                        class="bi bi-geo-alt-fill me-1 text-accent-orange opacity-75"
                                    ></i>
                                    {{ dorm.address }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small fw-600 text-dark">{{
                                        dorm.contactEmail
                                    }}</span>
                                    <span class="text-muted smaller">{{
                                        dorm.contactPhone
                                    }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge rounded-pill bg-soft-primary px-3 py-2"
                                >
                                    {{ dorm.totalRooms }} Rooms
                                </span>
                            </td>
                            <td class="pe-4">
                                <div
                                    class="d-flex justify-content-center gap-2"
                                >
                                    <button
                                        class="btn-action btn-view"
                                        @click="viewDorm(dorm.dormID)"
                                        title="View Details"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button
                                        class="btn-action btn-edit"
                                        @click="editDorm(dorm.dormID)"
                                        title="Edit Dorm"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        class="btn-action btn-delete"
                                        @click="deleteDorm(dorm.dormID)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination with Bootstrap 5 -->
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

        <!-- Modal Add Dorm -->
        <div
            v-if="VisibleAddModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(10px);
                z-index: 1050;
            "
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content border-0 rounded-4 shadow-lg overflow-hidden"
                >
                    <div
                        class="modal-header border-0 bg-white py-3 px-4 d-flex align-items-center justify-content-between"
                    >
                        <h5
                            class="modal-title fw-800 text-dark d-flex align-items-center gap-2"
                        >
                            <span class="header-emoji-box">🏠</span> Add
                            Dormitory
                        </h5>
                        <button
                            type="button"
                            class="btn-close shadow-none"
                            @click="CloseAddModal"
                        ></button>
                    </div>

                    <div class="modal-body bg-white px-4 pb-4">
                        <div
                            class="p-3 d-flex align-items-center flex-wrap bg-light border-0 rounded-4 shadow-sm mb-4"
                            style="border-left: 5px solid #003c87 !important"
                        >
                            <img
                                :src="getAssetPath('images/Logo/logo.png')"
                                alt="Logo"
                                width="50"
                                class="shadow-sm rounded-3 me-3"
                            />
                            <span class="fw-800 fs-5 logo-text me-4"
                                >DormDash</span
                            >
                            <div class="d-flex flex-column flex-grow-1">
                                <small class="text-dark fw-600"
                                    >📍 Click <strong>Select address</strong> to
                                    locate your dormitory.</small
                                >
                                <small class="text-muted"
                                    >✅ Ensure all required fields are filled
                                    before submitting.</small
                                >
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-floating mb-1">
                                    <input
                                        type="text"
                                        class="form-control premium-input"
                                        :class="{
                                            'is-invalid': errors.dorm_name,
                                        }"
                                        id="dormName"
                                        v-model="dorm_name"
                                        placeholder=" "
                                    />
                                    <label for="dormName">Dormitory Name</label>
                                </div>
                                <div class="mb-3" v-if="errors.dorm_name">
                                    <span
                                        class="text-danger small fw-bold d-flex align-items-center gap-1"
                                    >
                                        <i
                                            class="bi bi-exclamation-circle-fill"
                                        ></i>
                                        {{ errors.dorm_name[0] }}
                                    </span>
                                </div>

                                <div class="form-floating mb-2">
                                    <input
                                        type="text"
                                        class="form-control premium-input bg-light-soft"
                                        id="address"
                                        v-model="address"
                                        placeholder=" "
                                        readonly
                                    />
                                    <label for="address">Address</label>
                                </div>
                                <div class="d-grid mb-1">
                                    <button
                                        type="button"
                                        class="btn btn-map-action shadow-sm"
                                        @click="VisibleMap = true"
                                    >
                                        <i class="bi bi-geo-alt-fill me-2"></i>
                                        Select address from Map
                                    </button>
                                </div>
                                <div class="mb-3" v-if="errors.address">
                                    <span
                                        class="text-danger small fw-bold d-flex align-items-center gap-1"
                                    >
                                        <i
                                            class="bi bi-exclamation-circle-fill"
                                        ></i>
                                        {{ errors.address[0] }}
                                    </span>
                                </div>

                                <div class="form-floating mb-1">
                                    <textarea
                                        class="form-control premium-input"
                                        id="description"
                                        v-model="description"
                                        placeholder=" "
                                    ></textarea>
                                    <label for="description">Description</label>
                                </div>
                                <div class="mb-3" v-if="errors.description">
                                    <span
                                        class="text-danger small fw-bold d-flex align-items-center gap-1"
                                    >
                                        <i
                                            class="bi bi-exclamation-circle-fill"
                                        ></i>
                                        {{ errors.description[0] }}
                                    </span>
                                </div>

                                <div class="p-3 rounded-4 bg-light-soft border">
                                    <label
                                        class="fw-bold text-muted small mb-2 d-block"
                                        >TOTAL NUMBER OF ROOMS</label
                                    >
                                    <div
                                        class="d-flex align-items-center gap-3"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-stepper-danger"
                                            @click="decreamnentRooms()"
                                        >
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <input
                                            type="text"
                                            class="form-control stepper-input bg-transparent"
                                            v-model="total_rooms"
                                            readonly
                                        />
                                        <button
                                            type="button"
                                            class="btn btn-stepper-success"
                                            @click="increamentRooms()"
                                        >
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-1">
                                            <input
                                                type="email"
                                                class="form-control premium-input"
                                                id="email"
                                                v-model="contact_email"
                                                placeholder=" "
                                            />
                                            <label for="email"
                                                >Contact Email</label
                                            >
                                        </div>
                                        <div
                                            class="mb-2"
                                            v-if="errors.contact_email"
                                        >
                                            <span
                                                class="text-danger small fw-bold d-flex align-items-center gap-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.contact_email[0] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-1">
                                            <input
                                                type="tel"
                                                class="form-control premium-input"
                                                id="phone"
                                                v-model="contact_phone"
                                                placeholder=" "
                                            />
                                            <label for="phone"
                                                >Contact Phone</label
                                            >
                                        </div>
                                        <div
                                            class="mb-2"
                                            v-if="errors.contact_phone"
                                        >
                                            <span
                                                class="text-danger small fw-bold d-flex align-items-center gap-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.contact_phone[0] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-1">
                                            <input
                                                type="tel"
                                                class="form-control premium-input"
                                                id="gcash"
                                                v-model="gcashNumber"
                                                placeholder=" "
                                            />
                                            <label for="gcash"
                                                >GCash Number</label
                                            >
                                        </div>
                                        <div
                                            class="mb-2"
                                            v-if="errors.gcashNumber"
                                        >
                                            <span
                                                class="text-danger small fw-bold d-flex align-items-center gap-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.gcashNumber[0] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-1">
                                            <input
                                                type="text"
                                                class="form-control premium-input"
                                                id="btype"
                                                v-model="building_type"
                                                placeholder=" "
                                            />
                                            <label for="btype"
                                                >Building Type</label
                                            >
                                        </div>
                                        <div
                                            class="mb-2"
                                            v-if="errors.building_type"
                                        >
                                            <span
                                                class="text-danger small fw-bold d-flex align-items-center gap-1"
                                            >
                                                <i
                                                    class="bi bi-exclamation-circle-fill"
                                                ></i>
                                                {{ errors.building_type[0] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-floating mt-3 mb-1">
                                    <select
                                        class="form-select premium-input"
                                        id="avail"
                                        v-model="availability"
                                    >
                                        <option disabled value="">
                                            Select Status
                                        </option>
                                        <option value="Available">
                                            Available
                                        </option>
                                        <option value="Not Available">
                                            Not Available
                                        </option>
                                    </select>
                                    <label for="avail"
                                        >Availability Status</label
                                    >
                                </div>
                                <div class="mb-3" v-if="errors.availability">
                                    <span
                                        class="text-danger small fw-bold d-flex align-items-center gap-1"
                                    >
                                        <i
                                            class="bi bi-exclamation-circle-fill"
                                        ></i>
                                        {{ errors.availability[0] }}
                                    </span>
                                </div>

                                <div class="form-floating mb-1">
                                    <select
                                        class="form-select premium-input"
                                        id="occ"
                                        v-model="occupancy_type"
                                    >
                                        <option disabled value="">
                                            Select Occupancy
                                        </option>
                                        <option value="Male only">
                                            Male only
                                        </option>
                                        <option value="Female only">
                                            Female only
                                        </option>
                                        <option
                                            value="Mixed (Male & Female – separate floors)"
                                        >
                                            Mixed (Separate Floors)
                                        </option>
                                        <option value="Mixed (Unspecified)">
                                            Mixed (General)
                                        </option>
                                    </select>
                                    <label for="occ">Occupancy Type</label>
                                </div>
                                <div class="mb-4" v-if="errors.occupancy_type">
                                    <span
                                        class="text-danger small fw-bold d-flex align-items-center gap-1"
                                    >
                                        <i
                                            class="bi bi-exclamation-circle-fill"
                                        ></i>
                                        {{ errors.occupancy_type[0] }}
                                    </span>
                                </div>

                                <div class="d-grid pt-2">
                                    <button
                                        type="submit"
                                        @click="DisplayModalImages"
                                        class="btn btn-premium-submit py-3 shadow"
                                    >
                                        <i
                                            class="bi bi-cloud-arrow-up-fill me-2"
                                        ></i>
                                        Upload Dormitory Images
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Toastcomponents ref="toast" />
        </div>

        <!-- MAP MODAL -->
        <div
            v-if="VisibleMap"
            class="modal fade show d-block w-100"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(10px);
                z-index: 1060;
            "
            @click.self="VisibleMap = false"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div
                    class="modal-content border-0 rounded-4 shadow-lg overflow-hidden"
                >
                    <div
                        class="modal-header border-0 bg-white py-3 px-4 d-flex align-items-center justify-content-between"
                    >
                        <h5
                            class="modal-title fw-800 text-dark d-flex align-items-center gap-2"
                        >
                            <span class="header-emoji-box">📍</span> Dormitory
                            Location
                        </h5>
                        <button
                            type="button"
                            class="btn-close shadow-none"
                            @click="VisibleMap = false"
                        ></button>
                    </div>

                    <div class="modal-body p-0 position-relative">
                        <div
                            class="position-absolute top-0 start-0 end-0 p-3"
                            style="z-index: 5"
                        >
                            <div
                                class="bg-white p-3 rounded-4 shadow-sm border d-flex align-items-center gap-3"
                            >
                                <div class="header-emoji-box bg-primary-light">
                                    <i
                                        class="bi bi-geo-alt-fill text-primary"
                                    ></i>
                                </div>
                                <div class="flex-grow-1">
                                    <small
                                        class="text-muted d-block fw-bold"
                                        style="
                                            font-size: 10px;
                                            text-transform: uppercase;
                                            letter-spacing: 1px;
                                        "
                                        >Selected Location</small
                                    >
                                    <div
                                        class="text-dark fw-600 text-truncate"
                                        style="font-size: 14px"
                                    >
                                        {{
                                            address ||
                                            "Drag the pin to select the address..."
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="AddMap"
                            class="bg-light"
                            style="height: 500px; width: 100%"
                        ></div>
                    </div>

                    <div class="modal-footer border-0 bg-white p-3">
                        <button
                            type="button"
                            class="btn btn-premium-back px-4 py-2"
                            @click="VisibleMap = false"
                        >
                            <i class="bi bi-arrow-left me-2"></i> Back to Form
                        </button>
                        <button
                            type="button"
                            class="btn btn-premium-submit px-4 py-2"
                            @click="VisibleMap = false"
                        >
                            Confirm Location
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Amenities Modal -->
        <div
            class="modal fade show d-block w-100"
            v-if="amenitiesModal"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.8);
                backdrop-filter: blur(8px);
            "
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div
                    class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden"
                >
                    <div class="modal-header border-0 bg-white pt-4 px-4">
                        <h5
                            class="modal-title d-flex align-items-center gap-2 fw-800 text-dark"
                        >
                            <i class="fa-solid fa-circle-plus text-primary"></i>
                            Add Amenities
                            <span class="text-muted small fw-normal"
                                >(Optional)</span
                            >
                        </h5>
                    </div>

                    <Toastcomponents ref="toast" />

                    <div class="modal-body px-4 pb-4">
                        <div
                            v-for="(amenity, index) in amenities"
                            :key="index"
                            class="form-floating mb-3 premium-input-group"
                        >
                            <input
                                type="text"
                                class="form-control premium-control"
                                v-model="amenities[index]"
                                :id="'amenity' + index"
                                placeholder="Enter amenity"
                            />
                            <label :for="'amenity' + index" class="text-muted">
                                <i class="fa-solid fa-tag me-1 small"></i>
                                Amenity {{ index + 1 }}
                            </label>

                            <div class="error-wrapper mt-1">
                                <span
                                    class="text-danger x-small fw-bold"
                                    v-if="errors.amenities"
                                >
                                    <i
                                        class="fa-solid fa-circle-exclamation me-1"
                                    ></i>
                                    {{ errors.amenities[0] }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                class="btn btn-add-modern w-100 py-3"
                                @click="addAmenity"
                                :disabled="amenities.length >= 4"
                                :title="
                                    amenities.length >= 4
                                        ? 'Max 4 amenities allowed'
                                        : 'Add Amenity'
                                "
                            >
                                <div
                                    class="d-flex align-items-center justify-content-center gap-2"
                                >
                                    <i class="fa-solid fa-plus-circle fs-5"></i>
                                    <span class="fw-bold"
                                        >Add Another Amenity Slot</span
                                    >
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer border-0 bg-light-subtle p-3">
                        <button
                            type="button"
                            class="btn btn-primary rounded-3 w-100 py-2 fw-bold"
                            @click="closeaminitiemodal"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="modal fade show d-block w-100"
            v-if="rulesandpoliciesModal"
            tabindex="-1"
            style="
                background-color: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(10px);
            "
            @click.self="rulesandpoliciesModal = false"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div
                    class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
                >
                    <div class="modal-header border-0 bg-white pt-4 px-4">
                        <h5
                            class="modal-title d-flex align-items-center gap-2 fw-800 text-dark"
                        >
                            <i
                                class="fa-solid fa-shield-halved text-primary"
                            ></i>
                            Add Rules and Policy
                            <span class="text-muted small fw-normal"
                                >(Optional)</span
                            >
                        </h5>
                    </div>

                    <Toastcomponents ref="toast" />

                    <div class="modal-body px-4 pb-4">
                        <div
                            v-for="(rule, index) in rules"
                            :key="index"
                            class="form-floating mb-3 premium-input-group"
                        >
                            <input
                                type="text"
                                class="form-control premium-control"
                                v-model="rules[index]"
                                :id="'rule' + index"
                                placeholder="Enter rule or policy"
                            />
                            <label :for="'rule' + index" class="text-muted">
                                <i class="fa-solid fa-gavel me-1 small"></i>
                                Rule/Policy {{ index + 1 }}
                            </label>

                            <div class="error-wrapper mt-1">
                                <span
                                    class="text-danger x-small fw-bold"
                                    v-if="errors.rules"
                                >
                                    <i
                                        class="fa-solid fa-circle-exclamation me-1"
                                    ></i>
                                    {{ errors.rules[0] }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                class="btn btn-add-modern w-100 py-3"
                                @click="addRulesAndpolicy"
                                :disabled="rules.length >= 4"
                                :title="
                                    rules.length >= 4
                                        ? 'Max 4 rules allowed'
                                        : 'Add Rule'
                                "
                            >
                                <div
                                    class="d-flex align-items-center justify-content-center gap-2"
                                >
                                    <i class="fa-solid fa-plus-circle fs-5"></i>
                                    <span class="fw-bold"
                                        >Add Another Rule</span
                                    >
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer border-0 bg-light-subtle p-3">
                        <button
                            type="button"
                            class="btn btn-primary rounded-3 w-100 py-2 fw-bold"
                            @click="rulesandpoliciesModal = false"
                        >
                            Confirm Policies
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Update Modal -->
        <div
            v-if="VisibleUpdateModal"
            class="modal fade show d-block w-100"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(8px);
                z-index: 1055;
            "
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content border-0 rounded-24 shadow-2xl overflow-hidden"
                >
                    <div class="modal-header border-0 bg-white pt-4 px-4 pb-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box-primary shadow-sm">
                                <i class="fa-solid fa-pen-nib"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-800 text-dark mb-0">
                                    Update Property Details
                                </h5>
                                <p class="text-muted small mb-0">
                                    Modify your dormitory information and
                                    policies
                                </p>
                            </div>
                        </div>
                    </div>

                    <Toastcomponents ref="toast" />

                    <form @submit.prevent="updateDorm">
                        <div
                            class="modal-body custom-scrollbar bg-white px-4 pb-4"
                            style="max-height: 80vh; overflow-y: auto"
                        >
                            <div
                                class="modern-banner mb-4 d-flex align-items-center justify-content-between p-3 rounded-20 shadow-sm border border-light"
                            >
                                <div class="d-flex align-items-center">
                                    <img
                                        :src="
                                            getAssetPath('images/Logo/logo.png')
                                        "
                                        alt="Logo"
                                        width="42"
                                        class="me-3 rounded-12 shadow-sm"
                                    />
                                    <div>
                                        <span
                                            class="fw-800 fs-5 text-primary tracking-tight"
                                            >DormDash</span
                                        >
                                        <div
                                            class="text-muted small opacity-75"
                                        >
                                            📍 Select address to sync location
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none d-md-block text-end">
                                    <span
                                        class="badge bg-success-soft text-success rounded-pill px-3 py-2"
                                    >
                                        <i
                                            class="fa-solid fa-shield-check me-1"
                                        ></i>
                                        Form Secure
                                    </span>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="input-group-stack">
                                        <div class="form-floating mb-3">
                                            <input
                                                type="text"
                                                class="form-control modern-input"
                                                id="dormName"
                                                v-model="editDormData.dormName"
                                                placeholder="Dorm Name"
                                            />
                                            <label for="dormName"
                                                >Property Name</label
                                            >
                                            <span
                                                class="error-msg"
                                                v-if="
                                                    errors.editDormData
                                                        ?.dormName
                                                "
                                            >
                                                <i
                                                    class="fa-solid fa-circle-exclamation me-1"
                                                ></i>
                                                {{
                                                    errors.editDormData
                                                        .dormName[0]
                                                }}
                                            </span>
                                        </div>

                                        <div class="address-input-wrapper mb-3">
                                            <div class="form-floating">
                                                <input
                                                    type="text"
                                                    class="form-control modern-input-readonly"
                                                    id="address"
                                                    v-model="
                                                        editDormData.address
                                                    "
                                                    placeholder="Address"
                                                    readonly
                                                />
                                                <label for="address"
                                                    >Verified Address</label
                                                >
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-map-trigger mt-2"
                                                @click="UpdateVisibleMap = true"
                                            >
                                                <i
                                                    class="fa-solid fa-map-location-dot me-2"
                                                ></i>
                                                Update Location
                                            </button>
                                            <span
                                                class="error-msg"
                                                v-if="
                                                    errors.editDormData?.address
                                                "
                                            >
                                                {{
                                                    errors.editDormData
                                                        .address[0]
                                                }}
                                            </span>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <textarea
                                                class="form-control modern-input h-120"
                                                id="description"
                                                v-model="
                                                    editDormData.description
                                                "
                                                placeholder="Description"
                                            ></textarea>
                                            <label for="description"
                                                >Property Narrative</label
                                            >
                                            <span
                                                class="error-msg"
                                                v-if="
                                                    errors.editDormData
                                                        ?.description
                                                "
                                            >
                                                {{
                                                    errors.editDormData
                                                        .description[0]
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="amenity-manager-card rounded-20 p-3 bg-light-subtle border"
                                        >
                                            <label
                                                class="small fw-800 text-muted text-uppercase mb-3 d-block"
                                                >Facilities Management</label
                                            >
                                            <div class="input-group mb-2">
                                                <input
                                                    type="text"
                                                    class="form-control modern-input-sm"
                                                    v-model="
                                                        editDormData.newAmenities
                                                    "
                                                    placeholder="New facility..."
                                                />
                                                <button
                                                    class="btn btn-primary px-3 rounded-12 ms-2"
                                                    type="button"
                                                    @click="addnewAmenity()"
                                                >
                                                    <i
                                                        class="fa-solid fa-plus"
                                                    ></i>
                                                </button>
                                            </div>
                                            <div
                                                class="amenity-scroll custom-scrollbar mt-3"
                                            >
                                                <div
                                                    v-for="amenity in editDormData.amenities"
                                                    :key="
                                                        amenity.pivot?.id ||
                                                        amenity.id
                                                    "
                                                    class="amenity-row shadow-sm"
                                                >
                                                    <span
                                                        class="text-truncate fw-semibold"
                                                        >{{
                                                            amenity.aminityName
                                                        }}</span
                                                    >
                                                    <button
                                                        @click.prevent="
                                                            deleteAmenity(
                                                                amenity.pivot
                                                                    .id,
                                                            )
                                                        "
                                                        class="btn-delete-small"
                                                    >
                                                        <i
                                                            class="fa-solid fa-trash-can"
                                                        ></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div
                                        class="spec-card rounded-24 p-4 border border-primary-subtle shadow-sm mb-4"
                                    >
                                        <h6 class="fw-800 mb-4 text-dark">
                                            <i
                                                class="fa-solid fa-sliders text-primary me-2"
                                            ></i>
                                            Core Specifications
                                        </h6>

                                        <div class="form-floating mb-3">
                                            <input
                                                type="email"
                                                class="form-control modern-input"
                                                id="contact_email"
                                                v-model="
                                                    editDormData.contactEmail
                                                "
                                                placeholder="Email"
                                            />
                                            <label for="contact_email"
                                                >Support Email</label
                                            >
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input
                                                type="tel"
                                                class="form-control modern-input"
                                                id="contact_phone"
                                                v-model="
                                                    editDormData.contactPhone
                                                "
                                                placeholder="Phone"
                                            />
                                            <label for="contact_phone"
                                                >Contact Hotline</label
                                            >
                                        </div>

                                        <div class="form-floating mb-3">
                                            <input
                                                type="tel"
                                                class="form-control modern-input"
                                                id="gcash"
                                                v-model="
                                                    editDormData.gcashNumber
                                                "
                                                placeholder="GCash"
                                            />
                                            <label for="gcash"
                                                >GCash Account</label
                                            >
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <select
                                                        class="form-select modern-select"
                                                        v-model="
                                                            editDormData.availability
                                                        "
                                                    >
                                                        <option
                                                            value="Available"
                                                        >
                                                            Active
                                                        </option>
                                                        <option
                                                            value="Not Available"
                                                        >
                                                            Full
                                                        </option>
                                                    </select>
                                                    <label>Status</label>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="form-floating">
                                                    <input
                                                        type="text"
                                                        class="form-control modern-input"
                                                        v-model="
                                                            editDormData.buildingType
                                                        "
                                                    />
                                                    <label>Building</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="room-stepper p-3 rounded-16 border bg-white"
                                        >
                                            <label
                                                class="small fw-800 text-muted d-block text-center mb-2"
                                                >Inventory (Rooms)</label
                                            >
                                            <div
                                                class="d-flex align-items-center justify-content-between"
                                            >
                                                <button
                                                    type="button"
                                                    class="btn-step"
                                                    @click="
                                                        updatedecreamnentRooms()
                                                    "
                                                >
                                                    -
                                                </button>
                                                <span
                                                    class="fs-4 fw-800 text-primary"
                                                    >{{
                                                        editDormData.totalRooms
                                                    }}</span
                                                >
                                                <button
                                                    type="button"
                                                    class="btn-step"
                                                    @click="
                                                        updateincreamentRooms()
                                                    "
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-3">
                                        <button
                                            type="button"
                                            @click="updateImages()"
                                            class="btn btn-glass-dark"
                                        >
                                            <i
                                                class="fa-solid fa-images me-2"
                                            ></i>
                                            Media Manager
                                        </button>
                                        <button
                                            type="submit"
                                            class="btn btn-primary-gradient py-3 shadow-lg"
                                        >
                                            <i
                                                class="fa-solid fa-cloud-arrow-up me-2"
                                            ></i>
                                            Save Changes
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-outline-dark py-3"
                                            @click="VisibleUpdateModal = false"
                                        >
                                            <i
                                                class="fa-solid fa-times me-2"
                                            ></i>
                                            Discard Changes
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div
                                        class="policy-wrapper p-4 rounded-24 bg-dark text-white h-100 shadow-xl"
                                    >
                                        <h6
                                            class="fw-800 mb-4 d-flex align-items-center"
                                        >
                                            <i
                                                class="fa-solid fa-gavel text-warning me-2"
                                            ></i>
                                            Rules & Policies
                                        </h6>

                                        <div class="input-group mb-4">
                                            <input
                                                type="text"
                                                class="form-control dark-input shadow-none"
                                                v-model="newrules"
                                                placeholder="Add rule..."
                                            />
                                            <button
                                                class="btn btn-warning fw-bold px-3 ms-2 rounded-12"
                                                type="button"
                                                @click="addNewRule()"
                                            >
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>

                                        <div
                                            class="rule-scroll custom-scrollbar-light"
                                        >
                                            <div
                                                v-for="rule in editDormData.rules_and_policy"
                                                :key="rule.pivot?.id || rule.id"
                                                class="rule-item-dark"
                                            >
                                                <p class="mb-0 small pe-3">
                                                    {{ rule.rulesName }}
                                                </p>
                                                <button
                                                    @click.prevent="
                                                        deleteRulesAndPolicies(
                                                            rule.pivot.id,
                                                        )
                                                    "
                                                    class="btn-delete-rule"
                                                >
                                                    <i
                                                        class="fa-solid fa-circle-minus"
                                                    ></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            class="mt-4 pt-3 border-top border-secondary"
                                        >
                                            <div
                                                class="form-floating text-dark"
                                            >
                                                <select
                                                    class="form-select modern-select-dark border-0 shadow-none"
                                                    v-model="
                                                        editDormData.occupancyType
                                                    "
                                                >
                                                    <option value="Male only">
                                                        Male Only
                                                    </option>
                                                    <option value="Female only">
                                                        Female Only
                                                    </option>
                                                    <option
                                                        value="Mixed (Unspecified)"
                                                    >
                                                        Mixed
                                                    </option>
                                                </select>
                                                <label class="text-muted"
                                                    >Target Demographic</label
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Update Map View -->
        <div
            v-if="UpdateVisibleMap"
            class="modal fade show d-block w-100"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.8);
                backdrop-filter: blur(12px);
                z-index: 1060;
            "
            @click.self="UpdateVisibleMap = false"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div
                    class="modal-content border-0 rounded-24 shadow-2xl overflow-hidden bg-white"
                >
                    <div
                        class="modal-header border-0 pt-4 px-4 pb-0 d-flex align-items-center justify-content-between"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="icon-box-map shadow-sm bg-primary-soft text-primary"
                            >
                                <i
                                    class="fa-solid fa-location-crosshairs fa-lg"
                                ></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-800 text-dark mb-0">
                                    Pin Location
                                </h5>
                                <p class="text-muted small mb-0">
                                    Drag the marker to your dormitory's exact
                                    spot
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close-custom shadow-sm border-0"
                            @click="UpdateVisibleMap = false"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body p-4">
                        <div
                            class="position-relative overflow-hidden rounded-20 border shadow-inner"
                        >
                            <div
                                id="map"
                                style="height: 480px; width: 100%"
                                class="modern-map-canvas"
                            ></div>

                            <div class="map-search-overlay"></div>
                        </div>

                        <div
                            class="d-flex align-items-center justify-content-between mt-3"
                        >
                            <div
                                class="d-flex align-items-center gap-2 text-primary bg-primary-soft px-3 py-2 rounded-12"
                            >
                                <i class="fa-solid fa-circle-info small"></i>
                                <span class="small fw-bold"
                                    >GPS coordinates will sync
                                    automatically</span
                                >
                            </div>
                            <button
                                type="button"
                                class="btn btn-primary-gradient px-4 py-2"
                                @click="UpdateVisibleMap = false"
                            >
                                Confirm Location
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Display Data Modal -->
        <div
            v-if="VisibleDisplayDataModal"
            class="modal fade show d-block w-100"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.8);
                backdrop-filter: blur(12px);
                z-index: 1055;
            "
            @click.self="VisibleDisplayDataModal = false"
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content border-0 shadow-2xl rounded-24 overflow-hidden"
                >
                    <div
                        class="modal-header border-0 bg-white py-4 px-4 d-flex align-items-center justify-content-between"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="header-icon bg-primary-soft text-primary shadow-sm"
                            >
                                <i class="fa-solid fa-building-user"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-800 text-dark mb-0">
                                    {{ selectedDorm?.dormName }}
                                </h5>
                                <p class="text-muted small mb-0">
                                    <i
                                        class="fa-solid fa-location-dot me-1"
                                    ></i>
                                    {{ selectedDorm?.address }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close-custom shadow-sm"
                            @click="VisibleDisplayDataModal = false"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div
                        class="modal-body custom-scrollbar"
                        style="
                            max-height: 75vh;
                            overflow-y: auto;
                            padding: 0 2rem 2rem 2rem;
                        "
                    >
                        <div class="row g-3 mb-5">
                            <div class="col-lg-9">
                                <div class="main-image-wrapper shadow-sm">
                                    <img
                                        :src="currentMainImage"
                                        class="img-hero-modern"
                                        alt="Main View"
                                    />
                                    <div class="image-badge">Main View</div>
                                </div>
                            </div>
                            <div class="col-lg-3 d-flex flex-column gap-3">
                                <div
                                    v-if="selectedDorm?.images?.secondaryImage"
                                    class="side-thumb-card"
                                    :class="{
                                        active:
                                            currentMainImage ===
                                            selectedDorm.images.secondaryImage,
                                    }"
                                    @click="
                                        changeMainImage(
                                            selectedDorm.images.secondaryImage,
                                        )
                                    "
                                >
                                    <img
                                        :src="
                                            selectedDorm.images.secondaryImage
                                        "
                                        class="img-side"
                                        alt="Secondary"
                                    />
                                </div>
                                <div
                                    v-if="selectedDorm?.images?.thirdImage"
                                    class="side-thumb-card"
                                    :class="{
                                        active:
                                            currentMainImage ===
                                            selectedDorm.images.thirdImage,
                                    }"
                                    @click="
                                        changeMainImage(
                                            selectedDorm.images.thirdImage,
                                        )
                                    "
                                >
                                    <img
                                        :src="selectedDorm.images.thirdImage"
                                        class="img-side"
                                        alt="Third"
                                    />
                                </div>
                                <div
                                    class="side-thumb-card"
                                    :class="{
                                        active:
                                            currentMainImage ===
                                            selectedDorm.images.mainImage,
                                    }"
                                    @click="
                                        changeMainImage(
                                            selectedDorm.images.mainImage,
                                        )
                                    "
                                >
                                    <img
                                        :src="selectedDorm.images.mainImage"
                                        class="img-side"
                                        alt="Reset"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-7">
                                <section class="mb-4">
                                    <h6
                                        class="fw-800 text-uppercase small text-primary tracking-widest mb-3"
                                    >
                                        About Property
                                    </h6>
                                    <p
                                        class="text-secondary leading-relaxed fs-6"
                                    >
                                        {{ selectedDorm?.description }}
                                    </p>
                                </section>

                                <div
                                    class="specs-pill-container d-flex flex-wrap gap-2 mb-4"
                                >
                                    <div class="spec-pill">
                                        <i
                                            class="fa-solid fa-door-closed me-2"
                                        ></i
                                        >{{ selectedDorm?.totalRooms }} Rooms
                                    </div>
                                    <div class="spec-pill">
                                        <i
                                            class="fa-solid fa-user-group me-2"
                                        ></i
                                        >{{ selectedDorm?.occupancyType }}
                                    </div>
                                    <div class="spec-pill">
                                        <i class="fa-solid fa-couch me-2"></i
                                        >{{ selectedDorm?.buildingType }}
                                    </div>
                                </div>

                                <section>
                                    <h6
                                        class="fw-800 text-uppercase small text-primary tracking-widest mb-3"
                                    >
                                        Amenities
                                    </h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span
                                            v-for="amenity in selectedDorm?.amenities"
                                            :key="amenity.id"
                                            class="amenity-tag-modern"
                                        >
                                            {{ amenity.aminityName }}
                                        </span>
                                    </div>
                                </section>
                            </div>

                            <div class="col-md-5">
                                <div
                                    class="gcash-card-v3 mb-4 p-4 text-white shadow-lg"
                                >
                                    <div
                                        class="d-flex justify-content-between align-items-center mb-4"
                                    >
                                        <span class="fw-bold opacity-75 small"
                                            >Payment Partner</span
                                        >
                                        <img
                                            src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/GCash_logo.svg/1280px-GCash_logo.svg.png"
                                            height="20"
                                            alt="GCash"
                                        />
                                    </div>
                                    <div class="mb-1 small opacity-75">
                                        Account Number
                                    </div>
                                    <h3 class="fw-800 mb-0 tracking-widest">
                                        {{ selectedDorm?.gcashNumber }}
                                    </h3>
                                </div>

                                <div class="contact-strip mb-3">
                                    <div
                                        class="icon-circle bg-white text-primary shadow-sm"
                                    >
                                        <i class="fa-solid fa-phone"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="small text-muted">
                                            Phone
                                        </div>
                                        <div class="fw-bold">
                                            {{ selectedDorm?.contactPhone }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="contact-strip mb-4"
                                    @click="showReviews = !showReviews"
                                    style="cursor: pointer"
                                >
                                    <div
                                        class="icon-circle bg-white text-warning shadow-sm"
                                    >
                                        <i class="fa-solid fa-star"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="small text-muted">
                                            Ratings
                                        </div>
                                        <div class="fw-bold text-primary">
                                            {{ totalReviews }} Reviews
                                            <i
                                                class="fa-solid fa-chevron-right ms-2 small"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <transition name="slide-fade">
                            <div
                                v-if="showReviews"
                                class="mt-4 pt-4 border-top"
                            >
                                <div
                                    v-for="review in reviews"
                                    :key="review.id"
                                    class="review-card-minimal mb-3"
                                >
                                    <div class="d-flex gap-3">
                                        <img
                                            :src="review.profileImage"
                                            class="avatar-sm rounded-circle"
                                        />
                                        <div>
                                            <div class="fw-bold text-dark">
                                                {{ review.firstname }}
                                            </div>
                                            <div
                                                class="stars text-warning small mb-1"
                                            >
                                                <i
                                                    v-for="i in 5"
                                                    :key="i"
                                                    class="fa-solid fa-star"
                                                    :class="
                                                        i <= review.stars
                                                            ? 'text-warning'
                                                            : 'text-light'
                                                    "
                                                ></i>
                                            </div>
                                            <p
                                                class="text-secondary small mb-0"
                                            >
                                                {{ review.comment }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-if="VisibleImagePostModal"
            class="modal fade show d-block"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.7);
                backdrop-filter: blur(10px);
            "
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content border-0 rounded-24 shadow-2xl overflow-hidden"
                >
                    <div
                        class="modal-header border-0 bg-white py-4 px-4 d-flex align-items-center justify-content-between"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="header-icon-box bg-soft-blue text-brand-blue"
                            >
                                <i class="bi bi-images fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-800 text-dark mb-0">
                                    Upload Property Gallery
                                </h5>
                                <p class="text-muted small mb-0">
                                    Step-by-step images to showcase your
                                    dormitory
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close-custom"
                            @click="VisibleImagePostModal = false"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="modal-body bg-white px-4 pb-4 pt-0">
                        <div
                            class="container-fluid p-0 mx-auto"
                            style="max-width: 900px"
                        >
                            <div class="premium-stepper-bar mb-5 mt-3">
                                <div class="stepper-line"></div>
                                <div
                                    v-for="(step, index) in steps"
                                    :key="index"
                                    class="stepper-node"
                                    :class="{
                                        active: currentStep === index,
                                        completed: currentStep > index,
                                    }"
                                >
                                    <div class="node-circle">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="node-label">{{ step }}</span>
                                </div>
                            </div>

                            <div
                                v-if="currentStep === 0"
                                class="upload-section"
                            >
                                <input
                                    ref="RoomsImages1Input"
                                    class="d-none"
                                    type="file"
                                    accept="image/*"
                                    @change="handleroomImage1"
                                />

                                <div
                                    v-if="!roomImage1Preview"
                                    class="premium-dropzone"
                                    @click="triggerRoomImage1"
                                >
                                    <i
                                        class="bi bi-cloud-arrow-up fs-1 mb-3 text-brand-blue"
                                    ></i>
                                    <h6 class="fw-bold text-dark">
                                        Dormitory Main Thumbnail
                                    </h6>
                                    <p class="text-muted small mb-0 mt-1">
                                        This image will be shown on search
                                        listings
                                    </p>
                                    <span class="text-muted smaller mt-3"
                                        >JPG, PNG, GIF • Max 5MB</span
                                    >
                                </div>

                                <div
                                    v-if="errors.roomImage1File"
                                    class="error-pill mt-2"
                                >
                                    <i
                                        class="bi bi-exclamation-triangle-fill"
                                    ></i>
                                    {{ errors.roomImage1File[0] }}
                                </div>

                                <div
                                    v-if="roomImage1Preview"
                                    class="preview-card mt-3 shadow-sm border"
                                >
                                    <div
                                        class="card-inner d-flex align-items-center p-3 gap-3"
                                    >
                                        <img
                                            :src="roomImage1Preview"
                                            alt="Main Thumb"
                                            class="preview-image rounded-3"
                                        />
                                        <div class="flex-grow-1">
                                            <span
                                                class="nav-label text-brand-blue"
                                                >MAIN PREVIEW</span
                                            >
                                            <h6 class="fw-bold m-0 text-dark">
                                                Dormitory Thumbnail
                                            </h6>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeRoomImages1"
                                            class="btn-remove"
                                            title="Remove image"
                                        >
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="currentStep === 1"
                                class="upload-section"
                            >
                                <input
                                    ref="RoomsImages2Input"
                                    class="d-none"
                                    type="file"
                                    accept="image/*"
                                    @change="handleroomImage2"
                                />

                                <div
                                    v-if="!roomImage2Preview"
                                    class="premium-dropzone orange-theme"
                                    @click="triggerRoomImage2"
                                >
                                    <i
                                        class="bi bi-camera fs-1 mb-3 text-brand-orange"
                                    ></i>
                                    <h6 class="fw-bold text-dark">
                                        Dorm Room Image 2
                                    </h6>
                                    <p class="text-muted small mb-0 mt-1">
                                        Showcase the interior layout
                                    </p>
                                </div>

                                <div
                                    v-if="errors.roomImage2File"
                                    class="error-pill mt-2"
                                >
                                    <i
                                        class="bi bi-exclamation-triangle-fill"
                                    ></i>
                                    {{ errors.roomImage2File[0] }}
                                </div>

                                <div
                                    v-if="roomImage2Preview"
                                    class="preview-card mt-3 shadow-sm border"
                                >
                                    <div
                                        class="card-inner d-flex align-items-center p-3 gap-3"
                                    >
                                        <img
                                            :src="roomImage2Preview"
                                            alt="Room 2"
                                            class="preview-image rounded-3"
                                        />
                                        <div class="flex-grow-1">
                                            <span
                                                class="nav-label text-brand-orange"
                                                >INTERIOR</span
                                            >
                                            <h6 class="fw-bold m-0 text-dark">
                                                Room View 2
                                            </h6>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeRoomImages2"
                                            class="btn-remove"
                                            title="Remove image"
                                        >
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="currentStep === 2"
                                class="upload-section"
                            >
                                <input
                                    ref="RoomsImages3Input"
                                    class="d-none"
                                    type="file"
                                    accept="image/*"
                                    @change="handleroomImage3"
                                />

                                <div
                                    v-if="!roomImage3Preview"
                                    class="premium-dropzone orange-theme"
                                    @click="triggerRoomImage3"
                                >
                                    <i
                                        class="bi bi-camera fs-1 mb-3 text-brand-orange"
                                    ></i>
                                    <h6 class="fw-bold text-dark">
                                        Dorm Room Image 3
                                    </h6>
                                    <p class="text-muted small mb-0 mt-1">
                                        Provide another perspective of the room
                                    </p>
                                </div>

                                <div
                                    v-if="errors.roomImage3File"
                                    class="error-pill mt-2"
                                >
                                    <i
                                        class="bi bi-exclamation-triangle-fill"
                                    ></i>
                                    {{ errors.roomImage3File[0] }}
                                </div>

                                <div
                                    v-if="roomImage3Preview"
                                    class="preview-card mt-3 shadow-sm border"
                                >
                                    <div
                                        class="card-inner d-flex align-items-center p-3 gap-3"
                                    >
                                        <img
                                            :src="roomImage3Preview"
                                            alt="Room 3"
                                            class="preview-image rounded-3"
                                        />
                                        <div class="flex-grow-1">
                                            <span
                                                class="nav-label text-brand-orange"
                                                >INTERIOR</span
                                            >
                                            <h6 class="fw-bold m-0 text-dark">
                                                Room View 3
                                            </h6>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeRoomImages3"
                                            class="btn-remove"
                                            title="Remove image"
                                        >
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="d-flex justify-content-between mt-5 gap-3 border-top pt-4"
                            >
                                <button
                                    type="button"
                                    class="btn btn-modern-secondary px-4"
                                    @click="prevStep"
                                    :disabled="currentStep === 0"
                                >
                                    <i class="bi bi-arrow-left me-2"></i>
                                    Previous
                                </button>

                                <button
                                    v-if="currentStep < steps.length - 1"
                                    type="button"
                                    class="btn btn-modern-primary px-4"
                                    @click="nextStep"
                                >
                                    Next <i class="bi bi-arrow-right ms-2"></i>
                                </button>

                                <button
                                    v-if="currentStep === steps.length - 1"
                                    class="btn btn-modern-orange-submit px-4 fw-800"
                                    @click="AddnewDorm"
                                >
                                    <i
                                        class="bi bi-cloud-arrow-down-fill me-2"
                                    ></i>
                                    Submit Dorm Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Update Images-->
        <div
            v-if="VisibleUpdateImagePostModal"
            class="modal fade show d-block w-100"
            tabindex="-1"
            style="
                background: rgba(15, 23, 42, 0.8);
                backdrop-filter: blur(12px);
                z-index: 1060;
            "
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div
                    class="modal-content border-0 rounded-24 shadow-2xl overflow-hidden bg-light"
                >
                    <div
                        class="modal-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div
                                class="icon-box-update shadow-sm bg-success-soft text-success"
                            >
                                <i class="fa-solid fa-images fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-800 text-dark mb-0">
                                    Gallery Management
                                </h5>
                                <p class="text-muted small mb-0">
                                    Update your dormitory's visual showcase
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn-close-custom shadow-sm border-0"
                            @click="VisibleUpdateImagePostModal = false"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body p-4 p-lg-5">
                        <div class="stepper-wrapper mb-5">
                            <div
                                v-for="(step, index) in steps"
                                :key="index"
                                class="stepper-item"
                                :class="{
                                    active: editcurrentStep === index,
                                    completed: editcurrentStep > index,
                                }"
                            >
                                <div class="step-counter shadow-sm">
                                    {{ index + 1 }}
                                </div>
                                <div class="step-name">{{ step }}</div>
                            </div>
                        </div>

                        <div class="container mx-auto">
                            <div
                                v-if="editcurrentStep === 0"
                                class="step-content-box animate-fade-in"
                            >
                                <div
                                    class="upload-dropzone border-dashed-2 rounded-24 p-5 text-center mb-4 transition-all"
                                    @click="edittriggerRoomImage1"
                                >
                                    <input
                                        ref="editRoomsImages1Input"
                                        class="d-none"
                                        type="file"
                                        accept="image/*"
                                        @change="edithandleroomImage1"
                                    />

                                    <div
                                        v-if="
                                            !editDormData.roomImage1Preview &&
                                            !editDormData.roomImage1
                                        "
                                        class="py-4"
                                    >
                                        <div
                                            class="upload-icon-circle bg-white shadow-sm mb-3 mx-auto"
                                        >
                                            <i
                                                class="fa-solid fa-camera-retro text-primary fa-2x"
                                            ></i>
                                        </div>
                                        <h5 class="fw-700 text-dark">
                                            Main Room Photo
                                        </h5>
                                        <p class="text-muted small">
                                            This is the first photo users will
                                            see.
                                        </p>
                                        <span
                                            class="btn btn-white shadow-sm rounded-pill px-4 fw-600 border"
                                            >Choose File</span
                                        >
                                    </div>

                                    <div
                                        v-else
                                        class="preview-container position-relative rounded-20 overflow-hidden shadow-lg mx-auto"
                                        style="max-width: 500px"
                                    >
                                        <img
                                            :src="
                                                editDormData.roomImage1Preview ||
                                                editDormData.roomImage1
                                            "
                                            class="img-fluid w-100"
                                        />
                                        <div
                                            class="preview-overlay d-flex align-items-center justify-content-center"
                                        >
                                            <button
                                                type="button"
                                                @click.stop="
                                                    editremoveRoomImages1
                                                "
                                                class="btn btn-blur-danger rounded-pill px-4"
                                            >
                                                <i
                                                    class="fa-solid fa-trash-can me-2"
                                                ></i>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="editcurrentStep === 1"
                                class="step-content-box animate-fade-in"
                            >
                                <div
                                    class="upload-dropzone border-dashed-2 rounded-24 p-5 text-center mb-4 transition-all"
                                    @click="edittriggerRoomImage2"
                                >
                                    <input
                                        ref="editRoomsImages2Input"
                                        class="d-none"
                                        type="file"
                                        accept="image/*"
                                        @change="edithandleroomImage2"
                                    />

                                    <div
                                        v-if="
                                            !editDormData.roomImage2Preview &&
                                            !editDormData.roomImage2
                                        "
                                        class="py-4"
                                    >
                                        <div
                                            class="upload-icon-circle bg-white shadow-sm mb-3 mx-auto"
                                        >
                                            <i
                                                class="fa-solid fa-bed text-primary fa-2x"
                                            ></i>
                                        </div>
                                        <h5 class="fw-700 text-dark">
                                            Secondary Photo
                                        </h5>
                                        <p class="text-muted small">
                                            Show another angle of the room.
                                        </p>
                                        <span
                                            class="btn btn-white shadow-sm rounded-pill px-4 fw-600 border"
                                            >Choose File</span
                                        >
                                    </div>

                                    <div
                                        v-else
                                        class="preview-container position-relative rounded-20 overflow-hidden shadow-lg mx-auto"
                                        style="max-width: 500px"
                                    >
                                        <img
                                            :src="
                                                editDormData.roomImage2Preview ||
                                                editDormData.roomImage2
                                            "
                                            class="img-fluid w-100"
                                        />
                                        <div
                                            class="preview-overlay d-flex align-items-center justify-content-center"
                                        >
                                            <button
                                                type="button"
                                                @click.stop="
                                                    editremoveRoomImages2
                                                "
                                                class="btn btn-blur-danger rounded-pill px-4"
                                            >
                                                <i
                                                    class="fa-solid fa-trash-can me-2"
                                                ></i>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="editcurrentStep === 2"
                                class="step-content-box animate-fade-in"
                            >
                                <div
                                    class="upload-dropzone border-dashed-2 rounded-24 p-5 text-center mb-4 transition-all"
                                    @click="edittriggerRoomImage3"
                                >
                                    <input
                                        ref="editRoomsImages3Input"
                                        class="d-none"
                                        type="file"
                                        accept="image/*"
                                        @change="edithandleroomImage3"
                                    />

                                    <div
                                        v-if="
                                            !editDormData.roomImage3Preview &&
                                            !editDormData.roomImage3
                                        "
                                        class="py-4"
                                    >
                                        <div
                                            class="upload-icon-circle bg-white shadow-sm mb-3 mx-auto"
                                        >
                                            <i
                                                class="fa-solid fa-clapperboard text-primary fa-2x"
                                            ></i>
                                        </div>
                                        <h5 class="fw-700 text-dark">
                                            Additional Photo
                                        </h5>
                                        <p class="text-muted small">
                                            Add a photo of the bathroom or
                                            common area.
                                        </p>
                                        <span
                                            class="btn btn-white shadow-sm rounded-pill px-4 fw-600 border"
                                            >Choose File</span
                                        >
                                    </div>

                                    <div
                                        v-else
                                        class="preview-container position-relative rounded-20 overflow-hidden shadow-lg mx-auto"
                                        style="max-width: 500px"
                                    >
                                        <img
                                            :src="
                                                editDormData.roomImage3Preview ||
                                                editDormData.roomImage3
                                            "
                                            class="img-fluid w-100"
                                        />
                                        <div
                                            class="preview-overlay d-flex align-items-center justify-content-center"
                                        >
                                            <button
                                                type="button"
                                                @click.stop="
                                                    editremoveRoomImages3
                                                "
                                                class="btn btn-blur-danger rounded-pill px-4"
                                            >
                                                <i
                                                    class="fa-solid fa-trash-can me-2"
                                                ></i>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="d-flex justify-content-between align-items-center mt-4"
                            >
                                <button
                                    type="button"
                                    class="btn btn-light rounded-pill px-4 py-2 fw-600 border"
                                    @click="updateprevStep"
                                    :disabled="editcurrentStep === 0"
                                >
                                    <i
                                        class="fa-solid fa-chevron-left me-2"
                                    ></i>
                                    Previous
                                </button>

                                <div class="d-flex gap-2">
                                    <button
                                        v-if="
                                            editcurrentStep < steps.length - 1
                                        "
                                        type="button"
                                        class="btn btn-primary-gradient rounded-pill px-5 py-2 fw-700"
                                        @click="updatenextStep"
                                    >
                                        Next Step
                                        <i
                                            class="fa-solid fa-chevron-right ms-2"
                                        ></i>
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="btn btn-success-gradient rounded-pill px-5 py-2 fw-700 shadow"
                                        @click="editImages"
                                    >
                                        Finish & Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modalconfirmation ref="modal" />
    <Toastcomponents ref="toast" />

    <!-- Modal Footer -->
</template>

<script>
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";
import Loader from "@/components/loader.vue";
import Modalconfirmation from "@/components/modalconfirmation.vue";
import NotificationList from "@/components/notifications.vue";
import { debounce } from "lodash";

export default {
    components: {
        Toastcomponents,
        Loader,
        Modalconfirmation,
        NotificationList,
    },
    name: "MapView",
    data() {
        return {
            landlord_id: "",
            searchTerm: "",

            debouncedSearchTerm: "",
            selectedLocation: "",
            selectedAvailability: "",
            VisibleAddModal: false,
            VisibleMap: false,
            UpdateVisibleMap: false,
            VisibleDeleteModal: false,
            VisibleUpdateModal: false,
            VisibleDisplayDataModal: false,
            VisibleImagePostModal: false,
            VisibleUpdateImagePostModal: false,
            amenitiesModal: false,
            steps: ["Upload Images 1 ", "Upload Images 2", "Upload Images 3"],
            rulesandpoliciesModal: false,
            currentStep: 0,
            roomImage1Preview: "",
            roomImage1File: "",
            roomImage2Preview: "",
            roomImage2File: "",
            roomImage3Preview: "",
            roomImage3File: "",
            dorm_name: "",
            address: "",
            description: "",
            total_rooms: 1,
            contact_email: "",
            contact_phone: "",
            contact_phone: "",
            availability: "",
            occupancy_type: "",
            building_type: "",
            longitude: "",
            latitude: "",
            amenities: [""],
            inputamenities: "",
            gcashNumber: "",
            rules: [""],
            inputrules: "",
            errors: {},
            errorsEdit: {},
            dorms: [],
            dormId: "",
            selectedDorm: null,
            currentDormId: null,
            currntamenitiesId: null,
            editDormData: {
                dorm_id: null,
                dormName: "",
                address: "",
                description: "",
                totalRooms: "",
                contactEmail: "",
                contactPhone: "",
                availability: "",
                occupancyType: "",
                buildingType: "",
                latitude: "",
                longitude: "",
                newAmenities: "",
                roomImage1: "",
                roomImage1Preview: "",
                roomImage1File: "",
                roomImage2: "",
                roomImage2Preview: "",
                roomImage2File: "",
                roomImage3: "",
                roomImage3Preview: "",
                roomImage3File: "",
                gcashNumber: "",
                image_id: "",
                images: {
                    main_image: null,
                    secondary_image: null,
                    third_image: null,
                    image_id: null,
                },
            },
            editcurrentStep: 0,
            lastPage: 1,
            currentPage: 1,
            newAmenity: "",
            newrules: "",
            notifications: [],
            currentMainImage: null,
            isVerified: false,
            firstname: "",
            lastname: "",
            profilePic: "",
            averageRating: 0,
            average_rating: 0,
            average_stars: "",
            reviews: [], // load from API
            totalReviews: 0,
            showReviews: false,
        };
    },
    methods: {
        async getlandlordVerifiedStatus() {
            const res = await axios.get("/getlandlordVerifiedStatus");
            if (res.data.status === "success") {
                this.isVerified = res.data.isVerified;
            } else {
                console.error("Failed to fetch landlord verified status");
                this.isVerified = false;
            }
        },
        goToVerificationPage() {
            window.location.href = "/paymentLandlord/" + this.landlord_id;
        },
        modalconfirmation() {
            this.$refs.modal.visible = true;
        },
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
        handlePagination(page) {
            this.currentPage = page;

            if (this.filterMode === "search") {
                this.fetchDorms(page, this.searchTerm.toLowerCase());
            } else if (this.filterMode === "location") {
                this.dropdownLocation(page);
            } else if (this.filterMode === "availability") {
                this.dropdownAvailability(page);
            } else {
                // default fallback
                this.fetchDorms(page);
            }
        },

        //list of dormitories and search
        async fetchDorms(page = 1, term = "") {
            this.$refs.loader.loading = true;
            try {
                const response = await axios.get("/SearchDorms", {
                    params: { page, search: term },
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                if (response.data.status === "success") {
                    this.dorms = response.data.dorms.data;
                    this.currentPage = response.data.dorms.current_page;
                    this.lastPage = response.data.dorms.last_page;
                    this.$refs.loader.loading = false;
                    this.selectedAvailability = "";
                    this.selectedLocation = "";
                } else {
                    console.error(
                        "Failed to fetch dorms:",
                        response.data.message,
                    );
                }
            } catch (error) {
                console.error("Error fetching dorms:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },

        async dropdownLocation(page = 1) {
            this.$refs.loader.loading = true;
            try {
                const response = await axios.get("/filter-locations", {
                    params: {
                        page: this.currentPage,
                        location: this.selectedLocation,
                    },
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;

                    this.dorms = response.data.dorms.data;
                    this.currentPage = response.data.dorms.current_page;
                    this.lastPage = response.data.dorms.last_page;
                } else {
                    console.error(
                        "Failed to fetch dorms by location:",
                        response.data.message,
                    );
                    this.$refs.loader.loading = false;
                }
            } catch (error) {
                console.error("Error fetching dorms by location:", error);
                this.$refs.loader.loading = false;
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async dropdownAvailability(page = 1) {
            this.$refs.loader.loading = true;
            const response = await axios.get("/filter-availability", {
                params: {
                    page: this.currentPage,
                    availability: this.selectedAvailability,
                },
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            });
            if (response.data.status === "success") {
                this.dorms = response.data.dorms.data;
                this.currentPage = response.data.dorms.current_page;
                this.lastPage = response.data.dorms.last_page;
                this.$refs.loader.loading = false;
            } else {
                console.error(
                    "Failed to fetch dorms by availability:",
                    response.data.message,
                );
                this.$refs.loader.loading = false;
            }
        },
        //view Dorm data
        async viewDorm(dormId) {
            this.$refs.loader.loading = true;
            try {
                const response = await axios.get(`/view-dorm/${dormId}`, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.selectedDorm = response.data.dorm;
                    this.VisibleDisplayDataModal = true;
                    this.totalReviews = response.data.total_reviews;

                    // Add these lines
                    this.average_rating = response.data.average_rating;
                    this.average_stars = response.data.average_stars;
                    this.reviews = response.data.reviews;
                } else {
                    console.error(
                        "Failed to fetch dorm details:",
                        response.data.message,
                    );
                }
            } catch (error) {
                console.error("Error fetching dorm details:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        //end view doorm
        fill() {
            this.dorm_name = " ";
            this.address = " ";
            this.description = " ";
            this.total_rooms = 1;
            this.contact_email = " ";
            this.contact_phone = " ";
            this.rules = " ";
            ((this.gcashNumber = " "), (this.roomImage1File = " "));
            if (this.$refs.roomImage1Preview) {
                this.$refs.roomImage1Preview.value = ""; // Reset file input
            }

            this.roomImage2File = " ";
            if (this.$refs.roomImage2Preview) {
                this.$refs.roomImage2Preview.value = ""; // Reset file input
            }
            this.roomImage3Preview = " ";
            if (this.$refs.roomImage3Preview) {
                this.$refs.roomImage3Preview.value = ""; // Reset file input
            }

            this.currentStep = 0;
        },
        changeMainImage(imageUrl) {
            this.currentMainImage = imageUrl;
        },
        //end fill data
        //redirect rooms page
        ViewRoomsPage() {
            window.location.href = `/landlordRoomManagement/${this.landlord_id}`;
        },
        //end room page
        //functions for adding dormitories
        async AddnewDorm() {
            // this.$refs.loader.loading = true;
            const formData = new FormData();
            formData.append("dorm_name", this.dorm_name);
            formData.append("address", this.address);
            formData.append("description", this.description);
            formData.append("latitude", this.latitude);
            formData.append("longitude", this.longitude);
            formData.append("total_rooms", this.total_rooms);
            formData.append("contact_email", this.contact_email);
            formData.append("contact_phone", this.contact_phone);
            formData.append("availability", this.availability);
            formData.append("occupancy_type", this.occupancy_type);
            formData.append("building_type", this.building_type);
            formData.append("gcashNumber", this.gcashNumber);
            formData.append("roomImage1File", this.roomImage1File);
            formData.append("roomImage2File", this.roomImage2File);
            formData.append("roomImage3File", this.roomImage3File);

            try {
                const confirmed = await this.$refs.modal.show({
                    title: "Adding Dorm",
                    message: `Are you sure you want to Add Dorm? ${this.dorm_name}`,
                    functionName: "Add new Dormitory",
                });

                if (!confirmed) {
                    this.$refs.loader.loading = false;
                    return;
                }

                const response = await axios.post("/AddDorm", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                this.$refs.loader.loading = false;

                if (response.data.status === "success") {
                    this.VisibleAddModal = false;
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.VisibleImagePostModal = false;
                    this.fill();
                    this.amenitiesModal = true;
                    this.errors = {};
                    this.currentStep = 0;
                    this.dormId = response.data.dormId;
                    await this.fetchDorms(); // Refresh dorm list
                } else {
                    // Handle other statuses if necessary
                    this.$refs.toast.showToast(
                        "Failed to add dorm. Please try again.",
                        "danger",
                    );
                    this.$refs.loader.loading = false; // <== Add this here!
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                    this.$refs.loader.loading = false;
                } else if (
                    error.response &&
                    error.response.data &&
                    error.response.data.errors
                ) {
                    this.errors = error.response.data.errors;
                } else {
                    console.error("Error adding dorm:", error);
                    alert(
                        "An error occurred while adding the dorm. Please try again.",
                    );
                }
            }
        },

        CloseAddModal() {
            this.VisibleAddModal = false;
            this.fill();
            this.errors = {};
        },
        increamentRooms() {
            this.total_rooms++;
        },
        decreamnentRooms() {
            if (this.total_rooms <= 1) {
            } else {
                this.total_rooms--;
            }
        },
        async DisplayModalImages() {
            this.$refs.loader.loading = true;
            const formData = new FormData();
            formData.append("dorm_name", this.dorm_name);
            formData.append("address", this.address);
            formData.append("description", this.description);
            formData.append("total_rooms", this.total_rooms);
            formData.append("contact_email", this.contact_email);
            formData.append("contact_phone", this.contact_phone);
            formData.append("availability", this.availability);
            formData.append("occupancy_type", this.occupancy_type);
            formData.append("building_type", this.building_type);
            formData.append("gcashNumber", this.gcashNumber);

            try {
                const response = await axios.post("/input-text", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;

                    this.VisibleImagePostModal = true;
                    this.errors = {};
                }
            } catch (error) {
                this.$refs.loader.loading = false;

                if (error.response && error.response.status === 422) {
                    console.error(
                        "Validation errors:",
                        error.response.data.message,
                    );
                    this.errors = error.response.data.message;
                    // this.$refs.toast.showToast(response.data.message, 'danger');
                } else {
                    console.error("An error occurred:", error);
                }
            }
        },
        //Room Images Picture
        handleroomImage1(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.roomImage1Preview) {
                    URL.revokeObjectURL(this.roomImage1Preview);
                }
                this.roomImage1File = file;

                this.roomImage1Preview = URL.createObjectURL(file);
            }
        },
        triggerRoomImage1() {
            if (this.$refs.RoomsImages1Input) {
                this.$refs.RoomsImages1Input.click();
            }
        },

        removeRoomImages1() {
            if (this.roomImage1Preview) {
                URL.revokeObjectURL(this.roomImage1Preview);
            }
            this.roomImage1Preview = null;
            this.roomImage1File = null;
            // Add null check for safety
            if (this.$refs.roomImage1Preview) {
                this.$refs.roomImage1Preview.value = "";
                this.$refs.roomImage1File = "";
            }
        },
        //image 2
        handleroomImage2(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.roomImage2Preview) {
                    URL.revokeObjectURL(this.roomImage2Preview);
                }
                this.roomImage2File = file;

                this.roomImage2Preview = URL.createObjectURL(file);
            }
        },
        triggerRoomImage2() {
            if (this.$refs.RoomsImages2Input) {
                this.$refs.RoomsImages2Input.click();
            }
        },

        removeRoomImages2() {
            if (this.roomImage2Preview) {
                URL.revokeObjectURL(this.roomImage2Preview);
            }
            this.roomImage2Preview = null;
            this.roomImage2File = "";

            // Add null check for safety
            if (this.$refs.roomImage2Preview) {
                this.$refs.roomImage2Preview.value = ""; // Reset file input
            }
        },
        //image 3
        handleroomImage3(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.roomImage3Preview) {
                    URL.revokeObjectURL(this.roomImage3Preview);
                }
                this.roomImage3File = file;

                this.roomImage3Preview = URL.createObjectURL(file);
            }
        },
        triggerRoomImage3() {
            if (this.$refs.RoomsImages3Input) {
                this.$refs.RoomsImages3Input.click();
            }
        },

        removeRoomImages3() {
            if (this.roomImage3Preview) {
                URL.revokeObjectURL(this.roomImage3Preview);
            }
            this.roomImage3Preview = null;
            this.roomImag32File = "";

            // Add null check for safety
            if (this.$refs.roomImage3Preview) {
                this.$refs.roomImage3Preview.value = ""; // Reset file input
            }
        },
        async addnewAmenity() {
            if (
                !this.editDormData?.newAmenities ||
                this.editDormData.newAmenities.trim() === ""
            ) {
                this.errors.editDormData ??= {};
                this.errors.editDormData.newAmenities = [
                    "Please fill in the amenity field before submitting.",
                ];
                return;
            }
            this.errors.amenities = [];

            const confirmed = await this.$refs.modal.show({
                title: "Adding new Aminity",
                message: `Confirm adding this amenity to your dorm?`,
                functionName: "Add new Aminity (Optional)",
            });

            if (!confirmed) {
                this.$refs.loader.loading = false;
                return;
            }

            this.$refs.loader.loading = true;
            const formData = new FormData();
            formData.append("amenities", this.editDormData.newAmenities);
            formData.append("dorm_id", this.editDormData.dorm_id);

            try {
                const response = await axios.post("/add-amenities", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                const res = response.data;

                if (res.status === "success") {
                    this.$refs.loader.loading = false;

                    this.$refs.toast.showToast(
                        this.editDormData.newAmenities + "added successfully.",
                    );
                    //this.editDormData.newAmenities = '';
                    this.VisibleUpdateModal = false;
                    this.errors = {};
                    this.fetchDorms();
                } else {
                    this.$refs.loader.loading = false;

                    this.errors.amenities = [
                        res.message || "Failed to add amenity.",
                    ];
                    this.$refs.toast.showToast(
                        this.errors.amenities[0],
                        "error",
                    );
                }
            } catch (error) {
                if (error.response && error.response.data) {
                    this.$refs.loader.loading = false;

                    const res = error.response.data;

                    // Laravel's default validation error structure
                    if (res.errors) {
                        this.errors.editDormData ??= {};

                        if (res.errors.amenities) {
                            this.errors.editDormData.amenities =
                                res.errors.amenities;
                            this.$refs.toast.showToast(
                                res.errors.amenities[0],
                                "error",
                            );
                        }
                    } else if (res.message) {
                        this.errors.editDormData ??= {};
                        this.errors.editDormData.newAmenities = [res.message];
                    } else {
                        // Unexpected format
                        this.errors.editDormData ??= {};
                        this.errors.editDormData.amenities = [
                            "An unexpected error occurred.",
                        ];
                        this.$refs.toast.showToast(
                            "An unexpected error occurred.",
                            "error",
                        );
                    }
                } else {
                    this.errors.editDormData ??= {};
                    this.errors.editDormData.amenities = [
                        "Network or unknown error occurred.",
                    ];
                    this.$refs.toast.showToast(
                        "Network or unknown error occurred.",
                        "error",
                    );
                }
            }
        },
        addRulesAndPoliciesModal() {
            this.rulesandpoliciesModal = true;
            this.errors = {};
            this.rules = [""];
        },
        async addRulesAndpolicy() {
            const formData = new FormData();
            formData.append("rules", this.rules.join(","));
            formData.append("dorm_id", this.dormId);
            try {
                const confirmed = await this.$refs.modal.show({
                    title: "Adding Rules and Policies",
                    message: `Are you sure you want to Add Rules and Policies?`,
                    functionName: "Add new Rules and Policies (Optional)",
                });

                if (!confirmed) {
                    this.$refs.loader.loading = false;
                    return;
                }

                this.$refs.loader.loading = true;

                const response = await axios.post("/add-rules", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.rules = [""];
                    this.errors = {};
                    this.inputrules = "";
                } else {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(response.data.message, "danger");
                }
            } catch (error) {
                this.$refs.loader.loading = false;

                if (error.response && error.response.status === 422) {
                    const validationErrors = error.response.data.message;
                    // let messages = Object.values(validationErrors).flat().join('\n');
                    this.errors.rules = [validationErrors];
                }
                if (error.response.status === 400) {
                    const message =
                        error.response.data.message || "Something went wrong.";
                    this.errors.rules = [message];
                }
            }
        },
        async deleteRulesAndPolicies(id) {
            this.rulesId = id;
            const confirmed = await this.$refs.modal.show({
                title: "Delete Rule",
                message: "Are you sure you want to delete this Rule?",
                functionName: "Delete Rule",
            });

            if (!confirmed) return;

            this.$refs.loader.loading = true;
            if (this.rulesId) {
                try {
                    const response = await axios.delete(
                        `/delete-rules/${this.rulesId}`,
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
                        this.fetchDorms();
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
        async addNewRule() {
            if (!this.newRule === "") {
                this.$refs.toast.showToast(
                    "Please enter a rule before adding.",
                    "danger",
                );
                return;
            }
            this.rules.push(this.newRule);
            this.newRule = "";
            this.errors = {};
            const confirmed = await this.$refs.modal.show({
                title: "Adding New Rule",
                message: `Are you sure you want to add this rule?`,
                functionName: "Add new Rule (Optional)",
            });
            if (!confirmed) {
                this.rules.pop();
                return;
            }
            this.$refs.loader.loading = true;
            try {
                const response = await axios.post(
                    "/add-rules",
                    {
                        rules: this.newrules,
                        dorm_id: this.editDormData.dorm_id,
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
                        `${this.newrules} added successfully.`,
                        "success",
                    );
                    this.VisibleUpdateModal = false;
                    this.newrules = "";
                } else if (response.data.errors) {
                    this.errors.rules = [response.data.message];
                }
            } catch (error) {
                this.$refs.loader.loading = false;

                // Show Laravel validation or custom error message
                if (error.response && error.response.data) {
                    const res = error.response.data;

                    if (res.errors && res.errors.rules) {
                        this.errors.newrules = res.errors.rules;
                    } else if (res.message) {
                        this.errors.newrules = [res.message];

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
        async UploadImages1() {
            try {
                this.$refs.loader.loading = true;
                const formData = new FormData();
                formData.append("roomImage1File", this.roomImage1File);
                const response = await axios.post(
                    "/upload-main-image",
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
                    this.currentStep = 1;
                    this.$refs.loader.loading = false;
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    // Validation errors from backend
                    this.errors = error.response.data.errors;
                    this.$refs.loader.loading = false;
                } else {
                    // Other errors
                    const message =
                        error.response?.data?.message || "Something went wrong";
                    this.$refs.toast.showToast(message, "danger");
                    console.error(error);
                }
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async UploadImages2() {
            try {
                this.$refs.loader.loading = true;

                const formData = new FormData();
                formData.append("roomImage2File", this.roomImage2File);
                const response = await axios.post(
                    "/upload-secondary-image",
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
                    this.currentStep = 2;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                    this.$refs.loader.loading = false;
                } else {
                    this.$refs.toast.showToast(
                        response.data.messages,
                        "danger",
                    );
                    console.error(error);
                }
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async addAmenity() {
            if (this.amenities.some((a) => a.trim() === "")) {
                this.errors.amenities = [
                    "Please fill in all amenity fields before submitting.",
                ];
                return;
            }
            this.errors = {};
            const confirmed = await this.$refs.modal.show({
                title: "Adding Dorm",
                message: `Are you sure you want to Add Dorm?`,
                functionName: "Add new Aminity (Optional)",
            });

            if (!confirmed) {
                this.$refs.loader.loading = false;
                return;
            }
            this.$refs.loader.loading = true;

            for (const amenity of this.amenities) {
                try {
                    const response = await axios.post(
                        "/add-amenities",
                        {
                            amenities: amenity.trim(),
                            dorm_id: this.dormId,
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
                        this.amenities = [""];
                        this.inputamenities = "";
                        this.amenitiesModal = true;

                        this.$refs.toast.showToast(
                            `${amenity} added successfully.`,
                            "success",
                        );
                    } else if (response.data.errors) {
                        this.errors.amenities = [response.data.message];
                    }
                } catch (error) {
                    this.$refs.loader.loading = false;

                    // Show Laravel validation or custom error message
                    if (error.response && error.response.data) {
                        const res = error.response.data;

                        if (res.errors && res.errors.amenities) {
                            this.errors.amenities = res.errors.amenities;
                        } else if (res.message) {
                            this.errors.amenities = [res.message];
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
                }
            }
        },
        closeaminitiemodal() {
            this.amenities = [""];
            this.inputamenities = "";
            this.amenitiesModal = false;
            this.errors = {};
            this.addRulesAndPoliciesModal();
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },
        goToStep(index) {
            if (index <= this.currentStep) {
                this.currentStep = index;
            }
        },
        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                let isValid = true;

                if (this.currentStep === 0) {
                    isValid = this.UploadImages1();
                } else if (this.currentStep === 1) {
                    isValid = this.UploadImages2();
                }
            }
        },
        //end adding function
        //updating dorm functions
        async editDorm(dormId) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get(`/view-dorm/${dormId}`);
                if (response.data.status === "success") {
                    this.editDormData = {
                        ...response.data.dorm,
                        dorm_id: dormId,
                        roomImage1Preview:
                            response.data.dorm.images?.mainImage || null,
                        roomImage1:
                            response.data.dorm.images?.mainImage || null,
                        roomImage1File: null,
                        roomImage2Preview:
                            response.data.dorm.images?.secondaryImage || null,
                        roomImage2:
                            response.data.dorm.images?.secondaryImage || null,
                        roomImage2File: null,
                        roomImage3Preview:
                            response.data.dorm.images?.thirdImage || null,
                        roomImage3:
                            response.data.dorm.images?.thirdImage || null,
                        roomImage3File: null,
                        image_id: response.data.dorm.images?.imagesID || null,
                    };
                    this.VisibleUpdateModal = true;
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
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        updateImages() {
            this.VisibleUpdateImagePostModal = true;
        },
        async updateDorm() {
            // Hide spinner

            const formData = new FormData();
            formData.append("dormName", this.editDormData.dormName);
            formData.append("address", this.editDormData.address);
            formData.append("description", this.editDormData.description);
            formData.append("latitude", this.editDormData.latitude);
            formData.append("longitude", this.editDormData.longitude);
            formData.append("totalRooms", this.editDormData.totalRooms);
            formData.append("contactEmail", this.editDormData.contactEmail);
            formData.append("contactPhone", this.editDormData.contactPhone);
            formData.append("availability", this.editDormData.availability);
            formData.append("occupancyType", this.editDormData.occupancyType);
            formData.append("buildingType", this.editDormData.buildingType);
            formData.append("gcashNumber", this.editDormData.gcashNumber);

            try {
                const confirmed = await this.$refs.modal.show({
                    title: "Update Dorm",
                    message: `Confirm update to this dorm’s information?`,
                    functionName: "Update Dormitory",
                });

                if (!confirmed) {
                    return;
                }
                this.$refs.loader.loading = true;

                const response = await axios.post(
                    `/UpdateDorm/${this.editDormData.dorm_id}`,
                    formData,
                    {
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                // Hide spinner
                if (response.data.status === "success") {
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.$refs.loader.loading = false;
                    this.VisibleUpdateModal = false;
                    this.errors = {};
                    await this.fetchDorms(); // Refresh dorm list
                }
            } catch (error) {
                this.$refs.loader.loading = false; // Hide spinner

                if (error.response && error.response.status === 422) {
                    if (error.response.data.errors) {
                        // Validation errors from Laravel
                        this.errors.editDormData = error.response.data.errors;

                        console.log("Validation errors:", this.errors);
                    } else if (error.response && error.response.data.message) {
                        this.errors = { general: error.response.data.message };
                        this.$refs.toast.showToast(
                            error.response.data.message,
                            "error",
                        );
                    } else {
                        console.error("Error updating dorm:", error);
                        alert(
                            "An unexpected error occurred. Please try again.",
                        );
                    }
                } else {
                    console.error("Error updating dorm:", error);
                    alert(
                        "An error occurred while updating the dorm. Please try again.",
                    );
                }
            }
        },
        updateincreamentRooms() {
            this.editDormData.totalRooms++;
        },
        updatedecreamnentRooms() {
            if (this.editDormData.totalRooms <= 1) {
            } else {
                this.editDormData.totalRooms--;
            }
        },
        async editUploadImages1() {
            try {
                this.$refs.loader.loading = true;

                const formData = new FormData();

                if (this.editDormData.roomImage1File) {
                    formData.append(
                        "roomImage1File",
                        this.editDormData.roomImage1File,
                    );
                    formData.append("isNewImage", "true");
                } else if (this.editDormData.roomImage1Preview) {
                    formData.append(
                        "existingImage",
                        this.editDormData.roomImage1Preview,
                    );
                    formData.append("isNewImage", "false");
                } else {
                    formData.append("existingImage", "");
                    formData.append("isNewImage", "false");
                }

                const response = await axios.post(
                    "/edit-main-image",
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
                    this.editcurrentStep = 1;
                    this.$refs.loader.loading = false;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    const validationErrors = error.response.data.message;
                    let messages = Object.values(validationErrors)
                        .flat()
                        .join("\n");
                    this.$refs.toast.showToast(messages, "danger");
                } else {
                    const msg =
                        error.response?.data?.message ||
                        error.message ||
                        "An error occurred";
                    this.$refs.toast.showToast(msg, "danger");
                    console.error(error);
                }
            }
        },

        async editUploadImages2() {
            try {
                this.$refs.loader.loading = true;

                const formData = new FormData();
                if (this.editDormData.roomImage2File) {
                    formData.append(
                        "roomImage2File",
                        this.editDormData.roomImage2File,
                    );
                    formData.append("isNewImage", "true"); // Optional flag to indicate new image uploaded
                } else if (this.editDormData.roomImage2Preview) {
                    formData.append(
                        "existingImage",
                        this.editDormData.roomImage2Preview,
                    );
                    formData.append("isNewImage", "false"); // Optional flag
                } else {
                    formData.append("existingImage", "");
                    formData.append("isNewImage", "false");
                }
                const response = await axios.post(
                    "/edit-secondary-image",
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
                    this.editcurrentStep = 2;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response && error.response.status === 422) {
                    const validationErrors = error.response.data.message;
                    let messages = Object.values(validationErrors)
                        .flat()
                        .join("\n");
                    this.$refs.toast.showToast(messages, "danger");
                } else {
                    this.$refs.toast.showToast(
                        response.data.messages,
                        "danger",
                    );
                    console.error(error);
                }
            }
        },
        updateprevStep() {
            if (this.editcurrentStep > 0) {
                this.editcurrentStep--;
            }
        },
        updategoToStep(index) {
            if (index <= this.editcurrentStep) {
                this.editcurrentStep = index;
            }
        },
        updatenextStep() {
            if (this.editcurrentStep < this.steps.length - 1) {
                let isValid = true;

                if (this.editcurrentStep === 0) {
                    isValid = this.editUploadImages1();
                } else if (this.editcurrentStep === 1) {
                    isValid = this.editUploadImages2();
                }
            }
        },
        edithandleroomImage1(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.editDormData.roomImage1Preview) {
                    URL.revokeObjectURL(this.editDormData.roomImage1Preview);
                }
                this.editDormData.roomImage1File = file;

                this.editDormData.roomImage1Preview = URL.createObjectURL(file);
            }
        },
        edittriggerRoomImage1() {
            if (this.$refs.editRoomsImages1Input) {
                this.$refs.editRoomsImages1Input.click();
            }
        },

        editremoveRoomImages1() {
            if (this.editDormData.roomImage1Preview) {
                URL.revokeObjectURL(this.editDormData.roomImage1Preview);
            }
            this.editDormData.roomImage1Preview = null;
            this.editDormData.roomImage1File = "";

            // Add null check for safety
            if (this.$refs.editRoomsImages1Input) {
                this.$refs.editRoomsImages1Input.value = ""; // Reset file input
            }
        },
        //image 2
        edithandleroomImage2(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.editDormData.roomImage2Preview) {
                    URL.revokeObjectURL(this.editDormData.roomImage2Preview);
                }
                this.editDormData.roomImage2File = file;

                this.editDormData.roomImage2Preview = URL.createObjectURL(file);
            }
        },
        edittriggerRoomImage2() {
            if (this.$refs.editRoomsImages2Input) {
                this.$refs.editRoomsImages2Input.click();
            }
        },

        editremoveRoomImages2() {
            if (this.editDormData.roomImage2Preview) {
                URL.revokeObjectURL(this.editDormData.roomImage2Preview);
            }
            this.editDormData.roomImage2Preview = null;
            this.editDormData.roomImage2File = "";

            // Add null check for safety
            if (this.$refs.editRoomsImages2Input) {
                this.$refs.editRoomsImages2Input.value = ""; // Reset file input
            }
        },
        //image 3
        edithandleroomImage3(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.editDormData.roomImage3Preview) {
                    URL.revokeObjectURL(this.editDormData.roomImage3Preview);
                }
                this.editDormData.roomImage3File = file;
                this.editDormData.roomImage3Preview = URL.createObjectURL(file);
            }
        },
        edittriggerRoomImage3() {
            if (this.$refs.editRoomsImages3Input) {
                this.$refs.editRoomsImages3Input.click();
            }
        },
        editremoveRoomImages3() {
            if (this.editDormData.roomImage3Preview) {
                URL.revokeObjectURL(this.editDormData.roomImage3Preview);
            }
            this.editDormData.roomImage3Preview = null;
            this.editDormData.roomImage3File = "";

            // Add null check for safety
            if (this.$refs.editRoomsImages2Input) {
                this.$refs.editRoomsImages2Input.value = ""; // Reset file input
            }
        },
        async editImages() {
            const formData = new FormData();

            if (this.editDormData.roomImage1File instanceof File) {
                formData.append(
                    "roomImage1File",
                    this.editDormData.roomImage1File,
                );
            }
            if (this.editDormData.roomImage2File instanceof File) {
                formData.append(
                    "roomImage2File",
                    this.editDormData.roomImage2File,
                );
            }
            if (this.editDormData.roomImage3File instanceof File) {
                formData.append(
                    "roomImage3File",
                    this.editDormData.roomImage3File,
                );
            }
            formData.append("dorm_id", this.editDormData.dorm_id);
            const confirmed = await this.$refs.modal.show({
                title: "Update Dorm",
                message: `Confirm update to this dorm’s Images?`,
                functionName: "Update Dormitories Image",
            });
            if (confirmed === false) {
                return;
            }
            this.$refs.loader.loading = true;

            try {
                const response = await axios.post(
                    `/edit-images/${this.editDormData.image_id}`,
                    formData,
                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                if (response.data.status === "success") {
                    this.$refs.loader.loading = false;
                    this.VisibleUpdateImagePostModal = false;
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.editDormData.roomImage1File = null;
                    this.editDormData.roomImage2File = null;
                    this.editDormData.roomImage3File = null;
                    this.editcurrentStep = 0;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
            }
        },

        //end updating dorms functions
        //deleting dorm functions

        async deleteAmenity(id) {
            this.amenitiesId = id;
            const confirmed = await this.$refs.modal.show({
                title: "Delete Aminity",
                message: "Are you sure you want to delete this Aminity?",
                functionName: "Delete Aminity",
            });

            if (!confirmed) return;

            this.$refs.loader.loading = true;
            if (this.amenitiesId) {
                try {
                    const response = await axios.delete(
                        `/delete-amenities/${this.amenitiesId}`,
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
                        this.fetchDorms();
                        this.VisibleUpdateModal = false;
                    } else {
                        this.$refs.toast.showToast(
                            "Failed to delete amenities. Please try again.",
                            "error",
                        );
                    }
                } catch (error) {
                    this.$refs.loader.loading = false;
                    console.error("Error deleting amenities:", error);
                    console.log("Deleting amenity with ID:", this.amenitiesId);
                }
            } else {
                console.warn("No dorm ID provided for deleting amenities.");
            }
        },
        async deleteDorm(dormId) {
            this.currentDormId = dormId;

            try {
                // Show confirmation modal and wait for user to confirm
                const confirmed = await this.$refs.modal.show({
                    title: "Delete Dorm",
                    message: "Are you sure you want to delete this dorm?",
                    functionName: "Confirm Delete",
                });

                if (!confirmed) return;

                this.$refs.loader.loading = true;

                const response = await axios.delete(
                    `/DeleteDorm/${this.currentDormId}`,
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
                    this.fetchDorms(); // Refresh dorm list
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                } else {
                    this.$refs.toast.showToast(
                        "Failed to delete dorm. Please try again.",
                        "danger",
                    );
                }
            } catch (err) {
                this.$refs.loader.loading = false;
                if (err !== false) {
                    console.error("Error deleting dorm:", err);
                    this.$refs.toast.showToast(
                        "Server error while deleting.",
                        "danger",
                    );
                }
                // else: user cancelled, no need to show error
            }
        },

        // end deleting functions
        //google map api function
        getAssetPath(path) {
            return `/` + path;
        },
        initMap() {
            // 1. Update Center Point to Surigao City Hall area
            const centerPoint = { lat: 9.7894, lng: 125.492 };
            const defaultZoom = 14;

            // 2. Define Surigao City Boundary Polygon (Mainland focus)
            const surigaoPolygon = new google.maps.Polygon({
                paths: [
                    { lat: 9.815, lng: 125.475 },
                    { lat: 9.815, lng: 125.52 },
                    { lat: 9.76, lng: 125.52 },
                    { lat: 9.76, lng: 125.475 },
                ],
                visible: false, // Keep invisible for the premium UI feel
            });

            const mapStyle = [
                {
                    featureType: "all",
                    elementType: "all",
                    stylers: [{ saturation: -20 }, { lightness: 20 }],
                },
                { featureType: "poi", stylers: [{ visibility: "off" }] },
                { featureType: "transit", stylers: [{ visibility: "off" }] },
            ];

            const initializeMap = (
                elementId,
                initialPosition,
                isUpdate = false,
            ) => {
                const mapElement = document.getElementById(elementId);
                if (!mapElement || mapElement._map) return;

                const map = new google.maps.Map(mapElement, {
                    center: initialPosition,
                    zoom: defaultZoom,
                    styles: mapStyle,
                });

                // Add the new polygon to the map
                surigaoPolygon.setMap(map);

                const geocoder = new google.maps.Geocoder();

                const updateLocation = (latLng) => {
                    // Check if inside the Surigao City bounds
                    const insideSurigao =
                        google.maps.geometry.poly.containsLocation(
                            latLng,
                            surigaoPolygon,
                        );

                    if (!insideSurigao) {
                        this.$refs.toast.showToast(
                            "Selected location is outside Surigao City boundaries.",
                            "danger",
                        );
                        return false;
                    }

                    geocoder.geocode(
                        { location: latLng },
                        (results, status) => {
                            if (status === "OK" && results[0]) {
                                const address = results[0].formatted_address;
                                const latitude = latLng.lat();
                                const longitude = latLng.lng();

                                if (isUpdate) {
                                    this.editDormData.address = address;
                                    this.editDormData.latitude = latitude;
                                    this.editDormData.longitude = longitude;
                                } else {
                                    this.address = address;
                                    this.latitude = latitude;
                                    this.longitude = longitude;
                                }
                                this.$refs.toast.showToast(address, "success");
                            }
                        },
                    );
                };

                const draggableMarker = new google.maps.Marker({
                    position: initialPosition,
                    map: map,
                    draggable: true,
                    title: "Drag to select location",
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png", // Use a standard or custom icon
                        scaledSize: new google.maps.Size(40, 40),
                    },
                });

                draggableMarker.addListener("dragend", (event) => {
                    const latLng = event.latLng;
                    const inside = google.maps.geometry.poly.containsLocation(
                        latLng,
                        surigaoPolygon,
                    );

                    if (!inside) {
                        // Snap back to the center or previous valid data if outside
                        const lastLat = isUpdate
                            ? parseFloat(this.editDormData.latitude)
                            : this.latitude;
                        const lastLng = isUpdate
                            ? parseFloat(this.editDormData.longitude)
                            : this.longitude;

                        draggableMarker.setPosition(
                            new google.maps.LatLng(
                                lastLat || centerPoint.lat,
                                lastLng || centerPoint.lng,
                            ),
                        );

                        this.$refs.toast.showToast(
                            "Marker cannot be placed outside Surigao City.",
                            "danger",
                        );
                    } else {
                        updateLocation(latLng);
                    }
                });

                mapElement._map = map;
                mapElement._draggableMarker = draggableMarker;
            };

            // Logic to open Add or Update Map
            if (this.VisibleMap) {
                initializeMap("AddMap", centerPoint, false);
            }

            if (this.UpdateVisibleMap) {
                const initialPosition =
                    this.editDormData.latitude && this.editDormData.longitude
                        ? {
                              lat: parseFloat(this.editDormData.latitude),
                              lng: parseFloat(this.editDormData.longitude),
                          }
                        : centerPoint;
                initializeMap("map", initialPosition, true);
            }
        },

        formatDate(dateStr) {
            const options = { year: "numeric", month: "long", day: "numeric" };
            return new Date(dateStr).toLocaleDateString("en-US", options);
        },
    },
    // computed: {
    //     this.fetchDorms();
    // },
    mounted() {
        if (!window.google) {
            const script = document.createElement("script");
            script.src =
                "https://maps.googleapis.com/maps/api/js?key=AIzaSyCbVSKsv35IGFWYg9C96B5swf6UaVj9IGQ&callback=initMap";
            script.async = true;
            script.defer = true;
            window.initMap = this.initMap;
            script.onerror = () => {
                console.error("Google Maps failed to load.");
            };
            document.head.appendChild(script);
        } else {
            this.initMap(); // If already loaded
        }
        const element = document.getElementById("landlorddormManagement");
        this.landlord_id = element.dataset.landlordId;

        this.fetchDorms();
        this.subscribeToNotifications();
        this.getlandlordVerifiedStatus();
        window.vueInstance = this; // after mounting
    },

    watch: {
        selectedLocation(newVal) {
            if (newVal !== "") {
                this.selectedAvailability = ""; // Reset availability only when location is changed
                this.filterMode = "location";
                this.handlePagination(1);
            }
        },
        selectedAvailability(newVal) {
            if (newVal !== "") {
                this.selectedLocation = ""; // Reset location only when availability is changed
                this.filterMode = "availability";
                this.handlePagination(1);
            }
        },
        searchTerm: {
            handler: debounce(function (newVal) {
                this.filterMode = "search";
                this.handlePagination(1);
            }, 300),
            immediate: false,
        },

        VisibleMap(val) {
            if (val) {
                this.$nextTick(() => {
                    this.initMap();
                });
            }
        },
        UpdateVisibleMap(val) {
            if (val) {
                this.$nextTick(() => {
                    this.initMap();
                });
            }
        },
    },
};
</script>

<style scoped src="../../../../css/landlord/dormManagement.css"></style>
<style scoped src="../../../../css/partials/pagination.css"></style>
