<template>
    <Loader ref="loader" />

    <div class="container-fluid py-4 px-3 px-md-4">
        <!-- Header Section -->
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 p-4 shadow-sm rounded-4 custom-header-bg"
        >
            <div class="mb-3 mb-md-0">
                <h1
                    class="fw-bold mb-1"
                    style="color: var(--primary-blue); font-size: 1.8rem"
                >
                    Admin Dashboard
                </h1>
                <p class="text-muted mb-0 small">
                    Overview of platform metrics, collection, and subscribers.
                </p>
            </div>
            <button
                class="btn btn-lg d-inline-flex align-items-center px-4 py-2 text-white fw-semibold shadow-sm custom-btn-orange"
                @click="downloadReport"
            >
                <i class="bi bi-file-earmark-arrow-down me-2 fs-5"></i>
                Download Subscription Report
            </button>
        </div>

        <!-- Metrics Cards Row -->
        <div class="row g-4">
            <!-- Total Tenants -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 p-4 h-100 metric-card">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3"
                    >
                        <div
                            class="icon-box d-flex align-items-center justify-content-center"
                            style="
                                width: 55px;
                                height: 55px;
                                border-radius: 16px;
                                background: rgba(0, 60, 135, 0.08);
                                color: var(--primary-blue);
                            "
                        >
                            <i class="bi bi-people-fill fs-3"></i>
                        </div>
                        <span
                            class="badge bg-light text-primary fw-semibold px-2 py-1"
                            >Tenants</span
                        >
                    </div>
                    <h6
                        class="text-muted mb-1 fw-semibold text-uppercase small tracking-wider"
                    >
                        Total Tenants
                    </h6>
                    <p
                        class="display-6 fw-bold mb-0"
                        style="color: var(--primary-blue)"
                    >
                        {{ totalTenants }}
                    </p>
                </div>
            </div>

            <!-- Total Landlords -->
            <div class="col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 p-4 h-100 metric-card">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3"
                    >
                        <div
                            class="icon-box d-flex align-items-center justify-content-center"
                            style="
                                width: 55px;
                                height: 55px;
                                border-radius: 16px;
                                background: rgba(252, 125, 7, 0.1);
                                color: var(--accent-orange);
                            "
                        >
                            <i class="bi bi-building fs-3"></i>
                        </div>
                        <span
                            class="badge bg-light text-warning text-dark fw-semibold px-2 py-1"
                            >Landlords</span
                        >
                    </div>
                    <h6
                        class="text-muted mb-1 fw-semibold text-uppercase small tracking-wider"
                    >
                        Total Landlords
                    </h6>
                    <p
                        class="display-6 fw-bold mb-0"
                        style="color: var(--accent-orange)"
                    >
                        {{ totalLandlords }}
                    </p>
                </div>
            </div>

            <!-- Total Collection -->
            <div class="col-lg-4 col-md-12">
                <div class="card shadow-sm border-0 p-4 h-100 metric-card">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3"
                    >
                        <div
                            class="icon-box d-flex align-items-center justify-content-center"
                            style="
                                width: 55px;
                                height: 55px;
                                border-radius: 16px;
                                background: rgba(16, 185, 129, 0.1);
                                color: #10b981;
                            "
                        >
                            <i class="bi bi-cash-coin fs-3"></i>
                        </div>
                        <span
                            class="badge bg-light text-success fw-semibold px-2 py-1"
                            >Revenue</span
                        >
                    </div>
                    <h6
                        class="text-muted mb-1 fw-semibold text-uppercase small tracking-wider"
                    >
                        Total Collection
                    </h6>
                    <p class="display-6 fw-bold mb-0 text-success">
                        ₱{{ totalCollection }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Subscribers Graph Section -->
        <div
            class="card shadow-sm border-0 p-4 mt-4 rounded-4"
            v-if="chartData.labels.length"
        >
            <div class="d-flex align-items-center mb-4">
                <div class="p-2 rounded-3 bg-light text-primary me-2">
                    <i class="bi bi-graph-up-arrow fs-5"></i>
                </div>
                <h5 class="fw-bold mb-0" style="color: var(--primary-blue)">
                    Subscribers Analytics
                </h5>
            </div>
            <div
                class="chart-container"
                style="position: relative; height: 350px"
            >
                <Bar :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </div>
</template>

<script>
import { Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from "chart.js";
ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
);

import axios from "axios";
import Loader from "@/components/loader.vue";

export default {
    name: "AdminDashboard",
    components: { Bar, Loader },
    data() {
        return {
            totalTenants: 0,
            totalLandlords: 0,
            totalCollection: 0,
            totalSubscribers: 0,
            chartData: {
                labels: [],
                datasets: [
                    {
                        label: "Subscribers",
                        data: [],
                        backgroundColor: "#003C87",
                        borderRadius: 8,
                    },
                ],
            },
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: "#f1f5f9" },
                    },
                    x: {
                        grid: { display: false },
                    },
                },
            },
        };
    },
    methods: {
        async getUsersTotal() {
            try {
                this.$refs.loader.loading = true;
                const response = await axios.get("/admin/get/total");
                this.totalTenants = response.data.totalTenants;
                this.totalLandlords = response.data.totalLandlords;
                this.totalCollection = response.data.totalCollection;
            } catch (error) {
                console.error("Error fetching user totals:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async getTotals() {
            try {
                this.$refs.loader.loading = true;
                const response = await axios.get(
                    "/admin/get-subscribers-total",
                );
                this.totalSubscribers = response.data.totalSubscribers;

                // Update chart with single total
                this.chartData.labels = ["Total Subscribers"];
                this.chartData.datasets[0].data = [this.totalSubscribers];
            } catch (error) {
                console.error("Error fetching totals:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        downloadReport() {
            try {
                this.$refs.loader.loading = true;
                window.open("/generate-subscription-report", "_blank");
            } catch (error) {
                console.error(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
    },
    mounted() {
        this.getTotals();
        this.getUsersTotal();
    },
};
</script>

<style scoped>
/* Color Variables */
  :root {
      --primary-blue: #003c87;
      --accent-orange: #fc7d07;
      --text-muted: #94a3b8;
      --border-soft: #e2e8f0;
  }

/* Custom Header Background */
.custom-header-bg {
    background: linear-gradient(135deg, #f8fafc, #edf2f7);
    border: 1px solid #e2e8f0;
}

/* Metric Card Hover Animation */
.metric-card {
    border-radius: 20px;
    background: #ffffff;
    transition: all 0.3s ease;
    border: 1px solid #f1f5f9 !important;
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 60, 135, 0.08) !important;
}

/* Orange Action Button */
.custom-btn-orange {
    background: linear-gradient(135deg, #fc7d07, #e06c00);
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.custom-btn-orange:hover {
    background: linear-gradient(135deg, #e06c00, #c55d00);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(252, 125, 7, 0.3) !important;
}

@media (max-width: 768px) {
    .metric-card {
        padding: 1.5rem !important;
    }
}
</style>
