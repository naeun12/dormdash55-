<template>
    <NotificationList ref="toastRef" />
    <button class="btn btn-primary m-2" @click="goBack">
        <i class="bi bi-arrow-left"></i> Back
    </button>
    <div class="container my-5">
        <h3 class="fw-bold text-primary mb-4 d-flex align-items-center">
            <i class="bi bi-star-fill text-warning me-2"></i>
            Reviews & Ratings
        </h3>
        <!-- Rating Summary -->
        <div class="row align-items-center mb-5 p-4 rounded shadow-sm bg-white">
            <!-- Overall Rating -->
            <div class="col-md-3 text-center border-end">
                <h1 class="display-1 fw-bold text-warning mb-0">
                    {{ averageRating }}
                </h1>
                <p class="text-muted small mt-2">
                    ({{ totalReviewers }} Reviews)
                </p>
                <div class="text-warning fs-3">
                    <i
                        class="bi bi-star-fill"
                        v-for="n in Math.round(averageRating)"
                        :key="n"
                    ></i>
                </div>
            </div>

            <!-- Star Distribution -->
            <div class="col-md-9 ps-4">
                <div
                    v-for="rating in starRatings"
                    :key="rating.stars"
                    class="d-flex align-items-center mb-3"
                >
                    <div style="width: 60px">
                        <strong class="text-secondary"
                            >{{ rating.stars }} ★</strong
                        >
                    </div>
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
                    <div
                        style="width: 30px"
                        class="text-end fw-semibold text-dark"
                    >
                        {{ rating.count }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <h3 class="mb-4 fw-bold text-primary">Latest Reviews</h3>
        <div class="row g-4">
            <div
                v-for="review in reviews"
                :key="review.reviewID"
                class="col-md-6"
            >
                <div
                    class="card shadow-sm h-100 border-0 rounded-4 review-card"
                >
                    <div class="card-body d-flex gap-3 p-4">
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
                            <h5 class="card-title mb-1 fw-bold">
                                {{ review.tenant?.firstname }}
                                {{ review.tenant?.lastname }}
                            </h5>
                            <div class="mb-2 text-warning fs-6">
                                <i
                                    class="bi bi-star-fill"
                                    v-for="i in review.rating"
                                    :key="i"
                                ></i>
                            </div>
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
        async fetchReviews() {
            if (!this.dormitory_id) {
                console.error("Dormitory ID is missing.");
                return;
            }

            this.loading = true;
            this.errorMessage = "";

            try {
                const response = await axios.get(
                    `/fetch/reviewsandrating/${this.dormitory_id}`,
                );

                console.log("Reviews API Response:", response.data);

                if (response.data.success === false) {
                    throw new Error(
                        response.data.message || "Failed to fetch reviews.",
                    );
                }

                this.starRatings = response.data.starRatings || [];
                this.reviews = response.data.reviews || [];
                this.averageRating = response.data.averageRating || 0;
                this.totalReviewers = response.data.totalReviewers || 0;
            } catch (error) {
                console.error(
                    "Fetch Reviews Error:",
                    error.response?.data || error.message,
                );

                this.errorMessage =
                    error.response?.data?.message || "Unable to load reviews.";
            } finally {
                this.loading = false;
            }
        },

        goBack() {
            window.location.href = `/room-details/${this.dormitory_id}/${this.tenant_id}`;
        },
    },

    mounted() {
        const element = document.getElementById("reviewandrating");

        if (!element) {
            console.error("Element #reviewandrating not found.");
            return;
        }

        this.dormitory_id = element.dataset.dormId;
        this.tenant_id = element.dataset.tenantId;

        console.log("Dormitory ID:", this.dormitory_id);
        console.log("Tenant ID:", this.tenant_id);

        this.fetchReviews();
    },
};
</script>

<style scoped>
.review-card {
    transition: all 0.3s ease-in-out;
}

.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}
</style>
