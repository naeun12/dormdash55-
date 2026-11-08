<template>
    <!-- Component para sa notifications -->
    <NotificationList ref="toastRef" />

    <!-- Button para mobalik sa room details page -->
    <button class="btn btn-primary m-2" @click="goBack">
        <i class="bi bi-arrow-left"></i> Back
    </button>

    <div class="container my-5">
        <!-- Main heading sa Reviews and Ratings page -->
        <h3 class="fw-bold text-primary mb-4 d-flex align-items-center">
            <i class="bi bi-star-fill text-warning me-2"></i>
            Reviews & Ratings
        </h3>

        <!-- Rating Summary -->
        <div class="row align-items-center mb-5 p-4 rounded shadow-sm bg-white">
            <!-- Overall Rating: average stars ug total number of reviews -->
            <div class="col-md-3 text-center border-end">
                <h1 class="display-1 fw-bold text-warning mb-0">
                    {{ averageRating }}
                </h1>

                <p class="text-muted small mt-2">
                    ({{ totalReviewers }} Reviews)
                </p>

                <!-- I-display ang stars base sa average rating -->
                <div class="text-warning fs-3">
                    <i
                        class="bi bi-star-fill"
                        v-for="n in Math.round(averageRating)"
                        :key="n"
                    ></i>
                </div>
            </div>

            <!-- Star Distribution: ipakita ang total count ug percentage sa matag rating -->
            <div class="col-md-9 ps-4">
                <div
                    v-for="rating in starRatings"
                    :key="rating.stars"
                    class="d-flex align-items-center mb-3"
                >
                    <!-- Number of stars -->
                    <div style="width: 60px">
                        <strong class="text-secondary">
                            {{ rating.stars }} ★
                        </strong>
                    </div>

                    <!-- Progress bar nga nagpakita sa percentage sa rating -->
                    <div
                        class="progress flex-grow-1 mx-3"
                        style="
                            height: 12px;
                            border-radius: 6px;
                            background-color: #f1f1f1;
                        "
                    >
                        <div
                            class="progress-bar bg-warning"
                            role="progressbar"
                            :style="{ width: `${rating.percentage}%` }"
                            :aria-valuenow="rating.percentage"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>
                    </div>

                    <!-- Total count sa matag star rating -->
                    <div
                        style="width: 30px"
                        class="text-end fw-semibold text-dark"
                    >
                        {{ rating.count }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews List: ipakita ang tanang reviews sa dormitory -->
        <h3 class="mb-4 fw-bold text-primary">Latest Reviews</h3>

        <div class="row g-4">
            <!-- Balikon ang display para sa matag review nga nakuha gikan sa API -->
            <div
                v-for="review in reviews"
                :key="review.reviewID"
                class="col-md-6"
            >
                <div
                    class="card shadow-sm h-100 border-0 rounded-4 review-card"
                >
                    <div class="card-body d-flex gap-3 p-4">
                        <!-- Tenant profile picture; placeholder kung walay picture -->
                        <img
                            :src="
                                review.tenant?.pictureID ||
                                'https://via.placeholder.com/60'
                            "
                            alt="User Avatar"
                            class="rounded-circle border border-2 border-warning"
                            style="width: 60px; height: 60px; object-fit: cover"
                        />

                        <div class="flex-grow-1">
                            <!-- Ipakita ang pangalan sa tenant nga nag-submit sa review -->
                            <h5 class="card-title mb-1 fw-bold">
                                {{ review.tenant?.firstname }}
                                {{ review.tenant?.lastname }}
                            </h5>

                            <!-- I-display ang star rating nga gihatag sa tenant -->
                            <div class="mb-2 text-warning fs-6">
                                <i
                                    class="bi bi-star-fill"
                                    v-for="i in review.rating"
                                    :key="i"
                                ></i>
                            </div>

                            <!-- Ipakita ang written feedback sa tenant -->
                            <p class="card-text text-secondary">
                                {{ review.review }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    name: "ReviewsComponent",

    // Initial data nga gamiton ug i-display sa component.
    data() {
        return {
            dormitory_id: "",
            tenant_id: "",
            starRatings: [],
            reviews: [],
            averageRating: 0,
            totalReviewers: 0,
            loading: false,
            errorMessage: "",
        };
    },

    methods: {
        // Kuhaon ang reviews ug ratings gikan sa Laravel API.
        async fetchReviews() {
            // I-check kung naa bay dormitory ID sa dili pa mag-request.
            if (!this.dormitory_id) {
                console.error("Dormitory ID is missing.");
                return;
            }

            // I-set ang loading state ug i-clear ang previous error.
            this.loading = true;
            this.errorMessage = "";

            try {
                // Mag-request sa Laravel API gamit ang dormitory ID.
                const response = await axios.get(
                    `/fetch/reviewsandrating/${this.dormitory_id}`,
                );

                // Ipakita sa browser console ang API response para sa debugging.
                console.log("Reviews API Response:", response.data);

                // I-check kung unsuccessful ang API response.
                if (response.data.success === false) {
                    throw new Error(
                        response.data.message || "Failed to fetch reviews.",
                    );
                }

                // I-save ang star distribution gikan sa API.
                this.starRatings = response.data.starRatings || [];

                // I-save ang list sa reviews sa dormitory.
                this.reviews = response.data.reviews || [];

                // I-save ang average rating.
                this.averageRating = response.data.averageRating || 0;

                // I-save ang total number sa reviewers.
                this.totalReviewers = response.data.totalReviewers || 0;
            } catch (error) {
                // Ipakita ang error details kung napakyas ang API request.
                console.error(
                    "Fetch Reviews Error:",
                    error.response?.data || error.message,
                );

                // I-save ang error message aron magamit sa component.
                this.errorMessage =
                    error.response?.data?.message || "Unable to load reviews.";
            } finally {
                // I-reset ang loading state human mahuman ang request.
                this.loading = false;
            }
        },

        // Mobalik sa room details page sa current dormitory ug tenant.
        goBack() {
            window.location.href = `/room-details/${this.dormitory_id}/${this.tenant_id}`;
        },
    },

    // Modagan kini human ma-load ang Vue component.
    mounted() {
        // Pangitaon ang HTML element nga adunay dormitory ug tenant IDs.
        const element = document.getElementById("reviewandrating");

        // I-check kung naa ba ang required HTML element.
        if (!element) {
            console.error("Element #reviewandrating not found.");
            return;
        }

        // Kuhaon ang dormitory ID ug tenant ID gikan sa HTML data attributes.
        this.dormitory_id = element.dataset.dormId;
        this.tenant_id = element.dataset.tenantId;

        // I-display ang IDs sa console para dali ma-check kung sakto ang values.
        console.log("Dormitory ID:", this.dormitory_id);
        console.log("Tenant ID:", this.tenant_id);

        // Tawagon ang method para kuhaon ang reviews pag-load sa page.
        this.fetchReviews();
    },
};
</script>

<style scoped>
/* Smooth animation kung i-hover ang review card. */
.review-card {
    transition: all 0.3s ease-in-out;
}

/* I-angat gamay ang review card ug butangan og shadow kung i-hover. */
.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}
</style>
