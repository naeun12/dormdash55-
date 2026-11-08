<template>
    <Loader ref="loader" />
    <NotificationList ref="toastRef" />

    <div class="mt-5 py-3 px-5 d-flex justify-content-end align-items-center">
        <button class="custom-btn" @click="viewPayment">
            <span>View Payments History</span>
            <i class="bi bi-arrow-right-short fs-5"></i>
        </button>
    </div>
    <div v-if="rooms.length === 0" class="empty-state-container text-center p-5 mx-3 mt-4">
        <div class="py-4">
            <div class="empty-icon-wrapper shadow-sm">
                <i class="bi bi-house-exclamation fs-1"></i>
            </div>

            <h4 class="empty-title mb-2">No Room Found</h4>
            <p class="empty-text mb-4">
                It looks like you haven't booked a space yet.
                Explore our curated dormitories to find your next home!
            </p>

            <button class="btn-explore shadow-sm" @click="$router.push('/explore')">
                Explore Dormitories
            </button>
        </div>
    </div>
    <div class="container-fluid py-4" :class="{ 'card-slide': animate }"
        v-for="tenant in rooms.slice(currentIndex, currentIndex + 1)" :key="tenant.roomID">
        <div class="container-fluid">

            <div class="row row-cols-1 row-cols-md-3 g-4">
                <!-- Left Card -->
                <div class="col d-flex">
                    <div class="card modern-profile-card h-100 w-100 p-3">
                        <div class="card-body text-center p-4">

                            <div class="profile-avatar-wrapper mb-3">
                                <img :src="tenant.pictureID" class="profile-avatar-img" alt="User Image" />
                                <div class="status-dot shadow-sm" :class="{
                                    'bg-success': tenant.status === 'active',
                                    'bg-secondary': tenant.status === 'moved_out',
                                    'bg-warning': tenant.status === 'pending_moveout',
                                    'bg-info': tenant.status === 'transferring',
                                }"></div>
                            </div>

                            <h5 class="tenant-name mb-1">{{ tenant.firstname }} {{ tenant.lastname }}</h5>
                            <p class="text-muted small mb-3">ID: #{{ tenant.approvedID }}</p>

                            <div class="mb-4">
                                <span v-if="tenant.status != 'pending'" class="status-pill" :class="{
                                    'bg-success-subtle text-success border border-success-subtle': tenant.status === 'active',
                                    'bg-secondary-subtle text-secondary border border-secondary-subtle': tenant.status === 'moved_out',
                                    'bg-warning-subtle text-warning-emphasis border border-warning-subtle': tenant.status === 'pending_moveout',
                                    'bg-info-subtle text-info-emphasis border border-info-subtle': tenant.status === 'transferring',
                                }">
                                    {{ tenant.status?.replace('_', ' ').toUpperCase() }}
                                </span>
                            </div>

                            <div v-if="tenant.status === 'pending'"
                                class="alert alert-warning border-0 small rounded-4 p-3 mb-4 shadow-sm text-start">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-info-circle-fill fs-5 me-2 text-warning-emphasis"></i>
                                    <div>
                                        <strong>Reservation pending.</strong><br>
                                        Present receipt to landlord on move-in.
                                    </div>
                                </div>
                            </div>

                            <div class="modern-list-group text-start mb-4">
                                <div class="modern-list-item">
                                    <span class="modern-list-label"><i
                                            class="bi bi-gender-ambiguous me-2 text-primary"></i>Gender</span>
                                    <span class="modern-list-value">{{ tenant.gender }}</span>
                                </div>
                                <div class="modern-list-item">
                                    <span class="modern-list-label"><i
                                            class="bi bi-person-fill me-2 text-secondary"></i>Age</span>
                                    <span class="modern-list-value">{{ tenant.age }} yrs</span>
                                </div>
                                <div class="modern-list-item border-0">
                                    <span class="modern-list-label"><i
                                            class="bi bi-envelope-fill me-2 text-danger"></i>Email</span>
                                    <span class="modern-list-value text-truncate ms-3">{{ tenant.contactEmail }}</span>
                                </div>
                                <div class="modern-list-item d-none">
                                    <span class="modern-list-label"><i
                                            class="bi bi-telephone-fill me-2 text-success"></i>Contact</span>
                                    <span class="modern-list-value">{{ tenant.contactNumber }}</span>
                                </div>
                            </div>

                            <div v-if="tenant.status === 'pending'" class="mt-4">
                                <button class="btn btn-dormdash-blue w-100 shadow-sm"
                                    @click="viewReceipt(tenant.approvedID)">
                                    <i class="bi bi-file-earmark-pdf-fill me-2"></i> View Receipt
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Middle Card -->
                <div class="col d-flex">
                    <div class="card modern-room-card w-100 border-0">
                        <div class="room-image-container">
                            <img :src="tenant.room?.roomImages" class="card-img-top"
                                style="height: 220px; object-fit: cover;" alt="Room Image" />
                            <div class="room-price-float">
                                ₱{{ tenant.room?.price }}<span class="small fw-normal text-muted">/mo</span>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-extrabold mb-0" style="color: #1e293b;">Room #{{ tenant.room?.roomNumber
                                    }}</h5>
                                <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                                    {{ tenant.room?.roomType }}
                                </span>
                            </div>

                            <div class="room-info-grid mb-4">
                                <div class="info-item">
                                    <span class="info-label">Furnishing</span>
                                    <span class="info-value"><i class="bi bi-lamp me-1"></i> {{
                                        tenant.room?.furnishing_status }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Area</span>
                                    <span class="info-value"><i class="bi bi-aspect-ratio me-1"></i> {{
                                        tenant.room?.areaSqm }} sqm</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Listing</span>
                                    <span class="info-value"><i class="bi bi-tag me-1"></i> {{ tenant.room?.listingType
                                        }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Gender Pref</span>
                                    <span class="info-value"><i class="bi bi-people me-1"></i> {{
                                        tenant.room?.genderPreference }}</span>
                                </div>
                            </div>

                            <div >
                                <div v-if="alreadyReviewed === tenant.has_rated"
                                    class="star-rating-container text-center shadow-sm">
                                    <h6 class="fw-bold mb-3">How's your stay?</h6>

                                    <div class="rating-stars mb-2">
                                        <i v-for="star in 5" :key="star" class="bi interactive-star mx-1"
                                            :class="star <= currentRating ? 'bi-star-fill text-warning' : 'bi-star text-muted opacity-50'"
                                            style="font-size: 1.8rem; cursor: pointer;" @click="setRating(star)">
                                        </i>
                                    </div>

                                    <p class="small text-muted mb-3" v-if="currentRating > 0">You're giving it
                                        <strong>{{ currentRating }} stars</strong></p>

                                    <textarea class="form-control modern-textarea mb-3" v-model="currentReview"
                                        placeholder="Write a quick review about the room..."></textarea>

                                    <button class="btn btn-primary w-100 rounded-3 py-2 fw-bold"
                                        :disabled="currentRating === 0" @click="reviewandrating(tenant)">
                                        Submit Feedback
                                    </button>
                                </div>

                                <div v-else
                                    class="alert bg-success-subtle text-success border-0 rounded-4 text-center p-3">
                                    <i class="bi bi-check-circle-fill me-2"></i> Feedback submitted!
                                </div>
                            </div>
                        
                            <div v-if="tenant.status != 'pending'" class="mt-3">
                                <button class="btn btn-outline-danger w-100 border-2 rounded-3 py-2 small fw-bold"
                                    @click="messageMaintenance()">
                                    <i class="bi bi-tools me-2"></i> Report Issue
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Card -->
                <div class="col d-flex">
                    <div class="card modern-lease-card shadow-sm w-100 border-0">
                        <div class="card-body p-4">
                            <h6 class="fw-extrabold text-uppercase letter-spacing-1 mb-4"
                                style="color: #64748b; font-size: 0.75rem;">
                                <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i> Lease Summary
                            </h6>

                            <div class="lease-timeline px-2">
                                <div class="timeline-line"
                                    style="position: absolute; top: 32px; left: 10%; right: 10%; height: 4px; background: #e2e8f0; border-radius: 2px;">
                                </div>

                                <div class="timeline-point text-start">
                                    <div class="point-label">Start</div>
                                    <div class="point-date">{{ formatDate(tenant.moveInDate) }}</div>
                                </div>

                                <div class="timeline-point text-end">
                                    <div class="point-label">End</div>
                                    <div class="point-date text-danger">{{ formatDate(tenant.moveOutDate) }}</div>
                                </div>
                            </div>

                            <div v-if="tenant.status != 'pending'" class="text-center mb-4 p-3 rounded-4"
                                style="background: #f8fafc; border: 1px solid #f1f5f9;">
                                <div class="text-muted small fw-bold mb-1">Time Remaining</div>
                                <h3 class="fw-black mb-0" style="color: #0d6efd;">
                                    {{ getRemainingLeaseDays(tenant.moveInDate, tenant.moveOutDate) }}
                                </h3>
                            </div>

                            <div v-if="tenant.notifyRent == 1" class="choice-container text-center mb-4">
                                <h6 class="fw-bold mb-3">Your lease is expiring. Extend?</h6>
                                <div class="d-flex justify-content-center gap-3">
                                    <button class="btn-choice-extend shadow-sm"
                                        @click="updateRentStatus(tenant, 'extend')">
                                        <i class="bi bi-check-circle-fill me-2"></i>Yes, Extend
                                    </button>
                                    <button class="btn-choice-decline shadow-sm"
                                        @click="updateRentStatus(tenant, 'not_extending')">
                                        <i class="bi bi-x-circle-fill me-2"></i>No
                                    </button>
                                </div>
                            </div>

                            <div v-if="tenant.extension_decision === 'not_extending' && tenant.status !== 'moved_out'"
                                class="alert alert-danger border-0 rounded-4 p-3 mb-4">
                                <div class="d-flex">
                                    <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
                                    <div class="small">
                                        <strong class="d-block">Move-out Confirmed</strong>
                                        Please clear the room by <strong>{{ formatDate(tenant.moveOutDate) }}</strong>.
                                        Coordinate with the landlord for clearance.
                                    </div>
                                </div>
                            </div>

                            <div v-if="tenant.extension_decision === 'extend' && tenant.status !== 'moved_out'"
                                class="extension-info-box p-3 mb-4 shadow-sm">
                                <h6 class="fw-bold text-primary mb-3 small text-uppercase">Extension Payment</h6>

                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Monthly Rate:</span>
                                    <span class="fw-bold text-dark">₱{{ tenant.room.price }}</span>
                                </div>

                                <div v-if="tenant.payments[0]?.status === 'Approved'"
                                    class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Amount Paid:</span>
                                    <span class="fw-bold text-success">₱{{ Number(tenant.payments[0]?.amount ||
                                        0).toLocaleString() }}</span>
                                </div>

                                <div
                                    class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-primary border-opacity-10">
                                    <span class="badge rounded-pill" :class="{
                                        'bg-success': tenant.payments[0]?.status === 'approved',
                                        'bg-warning text-dark': tenant.payments[0]?.status === 'pending',
                                        'bg-danger': tenant.payments[0]?.status === 'rejected'
                                    }">
                                        {{ tenant.payments[0]?.status || 'No Payment' }}
                                    </span>

                                    <button class="btn btn-link btn-sm fw-bold text-decoration-none"
                                        @click="extendrentModal(tenant)">
                                        Pay Extension <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div>

                            <div v-if="tenant.extension_payment_status === 'done'"
                                class="alert bg-info-subtle text-info-emphasis border-0 rounded-4 text-center p-3 mb-0">
                                <i class="bi bi-stars me-2"></i> Rent extension paid & approved!
                            </div>

                            <div v-if="tenant.status === 'moved_out'" class="text-center py-3">
                                <span class="badge bg-secondary-subtle text-secondary px-4 py-2 rounded-pill">
                                    <i class="bi bi-archive me-2"></i> Archived Lease
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div v-if="extendRateModal" class="modal fade show d-block" tabindex="-1"
                style="background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);"
                @click.self="extendRateModal = false">

                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                        <div class="modal-header border-0 p-4 d-flex align-items-center"
                            style="background-color: #0d6efd;">
                            <h5 class="modal-title text-white fw-bold">
                                <i class="bi bi-calendar-check me-2"></i>Extend Payment
                            </h5>
                            <button type="button" class="btn-close btn-close-white"
                                @click="extendRateModal = false"></button>
                        </div>

                        <div class="modal-body p-4">

                            <div class="card shadow-sm border-0 rounded-4 mb-4" style="background: #f8fafc;">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3" style="color: #0d6efd;">
                                        <i class="bi bi-wallet2 me-2"></i> Choose Payment Option
                                    </h6>
                                    <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mt-2">

                                        <div class="text-center p-3 border rounded-4 d-flex flex-column align-items-center justify-content-center transition-all"
                                            :class="payment_option === 'online' ? 'border-primary bg-white shadow-sm' : 'bg-transparent text-muted'"
                                            style="cursor: pointer; width: 120px; height: 120px; border-width: 2px !important;"
                                            @click="paymentOption('online')">
                                            <i class="bi bi-globe2 fs-1 mb-1"
                                                :class="payment_option === 'online' ? 'text-primary' : 'text-secondary'"></i>
                                            <small class="fw-bold text-capitalize">Online</small>
                                        </div>

                                        <div class="text-center p-3 border rounded-4 d-flex flex-column align-items-center justify-content-center transition-all"
                                            :class="payment_option === 'onsite' ? 'border-primary bg-white shadow-sm' : 'bg-transparent text-muted'"
                                            style="cursor: pointer; width: 120px; height: 120px; border-width: 2px !important;"
                                            @click="paymentOption('onsite')">
                                            <i class="bi bi-house-door fs-1 mb-1"
                                                :class="payment_option === 'onsite' ? 'text-primary' : 'text-secondary'"></i>
                                            <small class="fw-bold text-capitalize">On-site</small>
                                        </div>
                                    </div>

                                    <div class="justify-content-center d-flex mt-3">
                                        <span v-if="errors.payment_option" class="text-danger small fw-bold">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                                            {{ errors.payment_option[0] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="payment_option === 'online'" class="fade-in">

                                <div class="card border-0 rounded-4 mb-4"
                                    style="background: #fff7ed; border: 1px solid #ffedd5 !important;">
                                    <div class="card-body text-center">
                                        <h6 class="fw-bold text-dark small text-uppercase mb-2">Landlord GCash Number
                                        </h6>
                                        <div class="p-3 bg-white rounded-3 shadow-sm d-inline-block px-4">
                                            <span class="fw-bold fs-4" style="color: #fd7e14;">
                                                {{ tenant.room?.dorm.gcashNumber }}
                                            </span>
                                        </div>
                                        <p class="text-muted mt-2 mb-0 small">Use this number when sending your payment
                                            via GCash.</p>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mt-3 mb-4">
                                    <div v-for="(src, name) in payment" :key="name"
                                        class="text-center p-3 border rounded-4 shadow-sm d-flex flex-column align-items-center justify-content-between transition-all"
                                        :class="payment_type === name ? 'border-primary bg-white' : 'bg-light border-0 text-muted'"
                                        style="cursor: pointer; width: 110px; height: 110px;"
                                        @click="paymentTypeSelection(name)">
                                        <img :src="src" :alt="name" class="img-fluid mb-2"
                                            style="width: 45px; height: 45px; object-fit: contain;" />
                                        <small class="fw-bold text-capitalize text-center">
                                            {{ name.replace('_', ' ') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="justify-content-center d-flex mt-2 mb-3">
                                    <span v-if="errors.paymentType" class="text-danger small fw-bold">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>{{ errors.paymentType[0] }}
                                    </span>
                                </div>

                                <div class="border-2 border-dashed rounded-4 p-4 mb-3 text-center transition-all"
                                    style="cursor: pointer; border-color: #cbd5e1; background: #f8fafc;"
                                    v-if="isPaymentImage" @click="triggerPaymentImage">
                                    <input ref="PaymentPicturesInput" class="d-none" type="file" accept="image/*"
                                        @change="handlePaymentPicture" />
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bi bi-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                        <h6 class="text-dark fw-bold mb-1">Upload Payment Image</h6>
                                        <small class="text-muted">Click to browse and select your screenshot</small>
                                    </div>
                                </div>

                                <div class="justify-content-center d-flex mb-2">
                                    <span v-if="errors.PaymentPictureFile" class="text-danger small fw-bold">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>{{
                                        errors.PaymentPictureFile[0] }}
                                    </span>
                                </div>

                                <div v-if="PaymentPicturePreview" class="text-center mb-3">
                                    <img :src="PaymentPicturePreview" alt="Uploaded Payment Image"
                                        class="img-fluid rounded-4 mb-2 shadow-sm" style="max-height: 250px;" />
                                    <div>
                                        <button type="button" @click="removePaymentPicture"
                                            class="btn btn-danger btn-sm rounded-pill px-3 fw-bold">
                                            <i class="bi bi-trash me-1"></i> Remove Image
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="payment_option === 'onsite'"
                                class="alert border-0 rounded-4 p-3 d-flex align-items-center"
                                style="background: #e0f2fe; color: #0369a1;">
                                <i class="bi bi-cash-stack fs-4 me-3"></i>
                                <div class="small fw-semibold">
                                    You chose <strong>On-Site Payment</strong>. Kindly meet your landlord to complete
                                    the payment process.
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 p-4 pt-0">
                            <button class="btn w-100 py-3 text-white fw-bold shadow-sm"
                                style="background-color: #0d6efd; border-radius: 14px; border: none; transition: 0.3s;"
                                @click="submitRent(tenant)">
                                Submit Extension Rent <i class="bi bi-check2-circle ms-2"></i>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <Toastcomponents ref="toast" />
         <div v-if="messageModal" class="modal fade show d-block" tabindex="-1"
    style="background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);" @click.self="messageModal = false">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content shadow-lg rounded-4 border-0">

            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-black text-dark mb-0">
                    <i class="bi bi-megaphone-fill text-primary me-2"></i> Report to Landlord
                </h5>
                <button type="button" class="btn-close" @click="messageModal = false"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted small mb-4">Select the issue you want to report to your landlord:</p>

                <div class="list-group border-0">
                    <button v-for="(label, key) in issues" :key="key"
                        class="list-group-item list-group-item-action rounded-4 mb-2 border-0 p-3 d-flex align-items-center justify-content-between shadow-sm transition-all"
                        :class="selectedIssue === label ? 'selected-issue-item' : 'bg-light'"
                        @click="selectIssue(label)">
                        <span class="fw-bold" :class="selectedIssue === label ? 'text-white' : 'text-dark'">{{ label }}</span>
                        <i v-if="selectedIssue === label" class="bi bi-check-circle-fill text-white"></i>
                        <i v-else class="bi bi-circle text-muted"></i>
                    </button>
                </div>

                <div v-if="selectedIssue" class="mt-4 p-3 rounded-4 border-0 d-flex align-items-center" 
                     style="background: #fff7ed; border: 1px solid #ffedd5 !important;">
                    <i class="bi bi-info-circle-fill text-orange me-2 fs-5"></i>
                    <span class="small fw-semibold text-dark">
                        Reporting: <span class="text-orange fw-bold">{{ selectedIssue }}</span>
                    </span>
                </div>
            </div>

            <div class="modal-footer border-0 p-4 pt-0 gap-2">
                <button class="btn btn-light rounded-pill px-4 fw-bold text-muted border-0" @click="messageModal = false">
                    Cancel
                </button>
                <button class="custom-btn" :disabled="!selectedIssue" @click="sendIssue(tenant)"
                        style="border-radius: 50px; padding: 10px 30px;">
                    Send Report <i class="bi bi-send-fill ms-2"></i>
                </button>
            </div>

        </div>
    </div>
</div>


        </div>
        <div class="mt-4 d-flex justify-content-center gap-3">
            <button class="btn text-white fw-bold px-4"
                style="background-color: #fd7e14; border-radius: 14px; border: none; transition: 0.3s;"
                @click="prevCard(tenant.approvedID)" :disabled="currentIndex === 0">
                <i class="bi bi-arrow-left me-1"></i> Prev
            </button>

            <button class="btn text-white fw-bold px-4"
                style="background-color: #0d6efd; border-radius: 14px; border: none; transition: 0.3s;"
                @click="nextCard(tenant.approvedID)" :disabled="currentIndex >= rooms.length - 1">
                Next <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>


    </div>

    <Toastcomponents ref="toast" />
    <Modalconfirmation ref="modal" />


</template>
<script>
import axios from 'axios';
import { nextTick } from 'vue';
import Toastcomponents from '@/components/Toastcomponents.vue';
import Loader from '@/components/loader.vue';
import Modalconfirmation from '@/components/modalconfirmation.vue';
import NotificationList from '@/components/notifications.vue';


export default {
    components: {
        Toastcomponents,
        Loader,
        Modalconfirmation,
        NotificationList,

    },
    data() {
        return {
            tenantid: '',
            rooms: [],
            extendRateModal: false,
            messageModal: false,
            selectedIssue: "",
            comments: '',
            issues: {
                water_leak: "💧 Water Leak",
                broken_light: "💡 Broken Light",
                wifi_problem: "📶 Wi‑Fi Problem",
                appliance_damage: "🔌 Appliance Damage",
                other: "🛠 Other"
            },
            currentIndex: 0,
            errors: {},
            payment_type: 'online',
            payment: '',
            animate: false,
            isPaymentImage: true,
            currentRating: 0,
            currentReview: '',
            reviews: [],
            alreadyReviewed: 0,
            payment: {
                gcash: '/images/tenant/allimagesResouces/GCash-Logo.png',

            },
            paymentIcon: '/images/tenant/allimagesResouces/paymentIcon.jpg',
            PaymentPicturePreview: '',
            PaymentPictureFile: null,
            notifications: [],
            receiverID: '',
            approvedID: '',
            payment_option: '',

        }
    },
    methods: {
        subscribeToNotifications() {
            if (this.hasSubscribed) return;
            this.hasSubscribed = true;

            this.receiverID = this.tenantid;
            Echo.private(`notifications.${this.tenantid}`)
                .subscribed(() => {
                    console.log('✔ Subscribed!');
                })
                .listen('.NewNotificationEvent', (e) => {
                    this.notifications.unshift(e); // save for list
                    this.$refs.toastRef.pushNotification({
                        title: e.title || 'New Notification',
                        message: e.message,
                        color: 'success',
                    });
                });
        },

        async roomsList() {
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get(`/tenant/room-list/${this.tenantid}`);
                this.rooms = response.data.rooms;
            } catch (error) {
                console.error('Failed to fetch room list:', error);
            }
            finally {
                this.$refs.loader.loading = false;

            }
        },
        getRemainingLeaseDays(moveIn, moveOut) {
            if (!moveIn || !moveOut) return 'Incomplete dates';

            // Parse properly
            const today = new Date();
            const moveOutDate = new Date(moveOut);

            // Zero out time to avoid timezone issues
            today.setHours(0, 0, 0, 0);
            moveOutDate.setHours(0, 0, 0, 0);

            // Calculate the difference in milliseconds then convert to days
            const diffInMs = moveOutDate - today;
            const remainingDays = Math.ceil(diffInMs / (1000 * 60 * 60 * 24));

            if (remainingDays < 0) return 'Lease ended';
            return `${remainingDays} day(s) remaining`;
        },

        nextCard(approvedID) {
            this.approvedID = approvedID;
            if (this.currentIndex < this.rooms.length - 1) {
                this.currentIndex++;
                this.triggerAnimation();
            }
        },
        prevCard(approvedID) {
            this.approvedID = approvedID;

            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.triggerAnimation();
            }
        },
        triggerAnimation() {
            this.animate = false;
            this.$nextTick(() => {
                this.animate = true;
            });
        },
        viewPayment() {
            window.location.href = `/view/payment/${this.tenantid}`;

        },
        viewReceipt(id) {
            window.open(`/tenant/${id}/receipt`, '_blank');
        },
        handlePaymentPicture(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.PaymentPicturePreview) {
                    URL.revokeObjectURL(this.PaymentPicturePreview);
                }
                this.PaymentPictureFile = file;
                this.isPaymentImage = false;

                this.PaymentPicturePreview = URL.createObjectURL(file);
            }
        },
        paymentTypeSelection(name) {
            this.payment_type = name;
        },
        triggerPaymentImage() {
            if (this.$refs.PaymentPicturesInput) {
                this.$refs.PaymentPicturesInput[0].click();
            }
        },
        removePaymentPicture() {
            if (this.PaymentPicturePreview) {
                URL.revokeObjectURL(this.PaymentPicturePreview);
            }
            this.PaymentPicturePreview = null;
            // Add null check for safety
            if (this.$refs.PaymentPicturePreview) {
                this.$refs.PaymentPicturePreview.value = ''; // Reset file input
            }
            this.isPaymentImage = true;
        },
        selectIssue(label) {
            this.selectedIssue = label;
        },
        messageMaintenance() {
            this.messageModal = true;
        },
        sendIssue(tenant) {
            // Example: redirect to chat with pre-filled message
            const message =
                `Hello, I would like to report this issue: ${this.selectedIssue}`;
            const trimmedMessage = message.trim();

            if (!trimmedMessage) return;
            this.$refs.loader.loading = true;

            axios.post('/send/issue', {
                message: trimmedMessage,
                senderID: this.tenantid,
                receiverID: tenant.room?.dorm?.fklandlordID,
                senderRole: 'tenant',
            })
                .then(() => {
                    window.location.href = `/tenant-message-nav/${this.tenantid}`;
                    this.message = '';
                    this.selectedIssue = '';
                    this.$refs.loader.loading = false;

                })
                .catch(err => {
                    console.error("❌ Failed to send message:", err);
                    alert("Failed to send message. Please try again.");
                    this.$refs.loader.loading = false;
                });


            // Close modal
            this.showIssueModal = false;
        },
        setRating(star) {
            this.currentRating = star;
        },
        getDaysStayed(moveInDate) {
            if (!moveInDate) return 0;
            const today = new Date();
            const start = new Date(moveInDate);

            // Zero out time values para dili maapektuhan sa timezone
            today.setHours(0, 0, 0, 0);
            start.setHours(0, 0, 0, 0);
            const diffInMs = today - start;
            return Math.floor(diffInMs / (1000 * 60 * 60 * 24)); // convert to days
        },
        extendrentModal(tenant) {
            this.extendRateModal = true
        },
        async submitRent(tenant) {
            const confirmed = await this.$refs.modal.show({
                title: `Extension Payment`,
                message: `Are you sure you want to proceed with this extension payment?`,
                functionName: 'Confirm Extension Payment'
            });

            if (!confirmed) {
                return;
            }
            const formdata = new FormData();
            formdata.append('paymentType', this.payment_type);
            formdata.append('approveID', tenant.approvedID);
            formdata.append('amount', tenant.room?.price);
            formdata.append('paymentImage', this.PaymentPictureFile);
            formdata.append('paymentOption', this.payment_option);
            try {
                const response = await axios.post('/extend-rent', formdata,
                    {
                        headers: { 'Content-Type': 'multipart/form-data' }

                    }
                );
                if (response.data.status === 'success') {
                    this.extendRateModal = false;
                    this.PaymentPictureFile = null;
                    this.PaymentPicturePreview = null;
                    this.isPaymentImage = true;
                    this.roomsList();
                    this.$refs.toast.showToast(response.data.message, 'success');
                }
            }
            catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                    this.$refs.toast.showToast('Double Check your Payment', 'danger');

                }
            }
            finally {

            }
        },
        async reviewandrating(tenant) {

            const formdata = new FormData();
            formdata.append('roomID', tenant.room?.roomID);
            formdata.append('approvedID', tenant.approvedID);
            formdata.append('rating', this.currentRating);
            formdata.append('review', this.currentReview);

            try {
                this.$refs.loader.loading = true;

                let res = await axios.post('/reviewandrating', formdata);

                if (res.data.success) {
                    this.$refs.loader.loading = false;
                    this.$refs.toast.showToast(res.data.message, 'success');
                    this.roomsList();
                    this.currentRating = 0;
                    this.currentReview = '';
                }
            } catch (err) {
                if (err.response?.status === 409) {
                    this.$refs.toast.showToast('You have already reviewed this dorm.', 'warning');
                } else {
                    console.error(err);
                    this.$refs.toast.showToast('Error submitting review', 'error');
                }
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async updateRentStatus(tenant, decision) {
            try {
                let actionText = '';
                if (decision === 'extend') {
                    actionText = 'approve this extension request';
                } else if (decision === 'not_extending') {
                    actionText = 'reject this extension request';
                } else {
                    actionText = 'set as pending';
                }

                const confirmed = await this.$refs.modal.show({
                    title: `Confirm Action`,
                    message: `Are you sure you want to ${actionText}?`,
                    functionName: 'Confirm'
                });

                if (!confirmed) {
                    return;
                }
                this.$refs.loader.loading = true;

                const formdata = new FormData();
                formdata.append('approveID', tenant.approvedID);
                formdata.append('decision', decision);

                const response = await axios.post('/update/rentstatus', formdata);

                if (response.data.status === 'success') {
                    this.$refs.toast.showToast(
                        `The request has been successfully ${decision}.`,
                        'success'
                    );
                    this.roomsList();

                } else {
                    this.$refs.toast.showToast('Something went wrong.', 'error');
                }
            }
            catch (error) {
                console.error(error);
                this.$refs.toast.showToast('Server error, please try again later.', 'error');
            }
            finally {
                this.$refs.loader.loading = false;
            }
        },
        paymentOption(payment_option) {
            this.payment_option = payment_option;
        },
        formatDate(date) {
            if (!date) return "N/A";
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },


    },
    mounted() {
        nextTick(() => {
            const el = document.getElementById('myRooms');
            if (el) {
                this.tenantid = el.getAttribute('tenant_id')?.trim();
                this.roomsList();
                this.subscribeToNotifications();

            } else {
                console.warn('nextPayment div not found');
            }
        });
    }

}
</script>
<style scoped src="/resources/css/tenant/myrooms.css"></style>
