<template>
    <Toastcomponents ref="toast" />

    <div class="container min-vh-100 d-flex align-items-center">
        <div
            class="card border-0 shadow-lg overflow-hidden w-100 animate__animated animate__fadeIn"
            style="max-width: 500px; border-radius: 30px; background: #ffffff"
        >
            <div
                style="
                    height: 6px;
                    background: linear-gradient(90deg, #003c87, #fc7d07);
                "
            ></div>

            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-5">
                    <div
                        class="d-inline-block p-3 rounded-circle mb-3"
                        style="background: rgba(0, 60, 135, 0.08)"
                    >
                        <i
                            class="bi bi-house-heart-fill fs-1"
                            style="color: #003c87"
                        ></i>
                    </div>
                    <h2 class="fw-bold text-dark" style="letter-spacing: -1px">
                        Welcome Back
                    </h2>
                    <p class="text-muted small">
                        Manage your properties and tenants with ease.
                    </p>
                </div>

                <form @submit.prevent="LandlordLogin" novalidate>
                    <div class="mb-4">
                        <label
                            for="email"
                            class="form-label small fw-bold text-muted text-uppercase ms-1"
                            >Email Address</label
                        >
                        <div class="input-group">
                            <span
                                class="input-group-text bg-light border-2 border-end-0"
                                style="
                                    border-color: #f1f3f5;
                                    border-radius: 12px 0 0 12px;
                                "
                            >
                                <i class="bi bi-envelope text-muted"></i>
                            </span>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control border-2 border-start-0 p-3 shadow-none login-input"
                                v-model="email"
                                placeholder="name@example.com"
                                style="
                                    border-color: #f1f3f5;
                                    border-radius: 0 12px 12px 0;
                                    font-size: 0.95rem;
                                "
                            />
                        </div>
                        <span
                            v-if="errors.email"
                            class="text-danger x-small mt-1 d-block"
                        >
                            <i class="bi bi-exclamation-circle me-1"></i>
                            {{ errors.email[0] }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label
                                for="password"
                                class="form-label small fw-bold text-muted text-uppercase ms-1"
                                >Password</label
                            >
                        </div>
                        <div class="input-group">
                            <span
                                class="input-group-text bg-light border-2 border-end-0"
                                style="
                                    border-color: #f1f3f5;
                                    border-radius: 12px 0 0 12px;
                                "
                            >
                                <i class="bi bi-shield-lock text-muted"></i>
                            </span>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                name="password"
                                v-model="password"
                                class="form-control border-2 border-start-0 p-3 shadow-none login-input"
                                placeholder="Enter password"
                                style="
                                    border-color: #f1f3f5;
                                    border-radius: 0 12px 12px 0;
                                    font-size: 0.95rem;
                                "
                            />
                        </div>
                        <span
                            v-if="errors.password"
                            class="text-danger x-small mt-1 d-block"
                        >
                            <i class="bi bi-exclamation-circle me-1"></i>
                            {{ errors.password[0] }}
                        </span>
                    </div>

                    <div
                        class="d-flex align-items-center justify-content-between mb-4 mt-2"
                    >
                        <div class="form-check ms-1">
                            <input
                                type="checkbox"
                                id="show-password"
                                class="form-check-input shadow-none"
                                @click="toggleShowPassword"
                                style="cursor: pointer"
                            />
                            <label
                                for="show-password"
                                class="form-check-label small text-muted"
                                style="cursor: pointer"
                            >
                                Show Password
                            </label>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn w-100 py-3 rounded-3 shadow-sm fw-bold border-0 transition-all landlord-login-btn text-white"
                        style="
                            background: #003c87;
                            letter-spacing: 0.5px;
                            border-radius: 12px !important;
                        "
                    >
                        LOG IN TO DASHBOARD
                    </button>
                </form>

                <div class="text-center mt-5">
                    <p class="text-muted small mb-0">
                        New to
                        <span class="fw-bold" style="color: #003c87"
                            >DormDash</span
                        >?
                        <a
                            @click="clickSignupLink"
                            style="cursor: pointer; color: #fc7d07 !important"
                            class="fw-bold text-decoration-none border-bottom border-warning border-2 pb-1 ms-1"
                        >
                            Create Landlord Account
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";

export default {
    components: {
        Toastcomponents,
    },
    data() {
        return {
            email: "",
            showPassword: false, // ✅ define it here
            password: "",
            errors: {},
            linkSignup: "",
        };
    },
    methods: {
        async LandlordLogin() {
            const formData = new FormData();
            formData.append("email", this.email);
            formData.append("password", this.password);
            try {
                const response = await axios.post("/loginLandlord", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                if (response.data.status === "success") {
                    const userId = response.data.landlord.id;
                    const userName = response.data.landlord.firstname;
                    localStorage.setItem("landlord_id", userId);
                    localStorage.setItem("landlord_name", userName);
                    window.location.href = `/landlordDashboard/${userId}`;
                    this.errors = {};
                    this.fill();
                    this.$refs.loader.loading = false;

                    return true;
                }
            } catch (error) {
                this.errors = {}; // clear previous errors
                if (error.response) {
                    if (error.response.status === 422) {
                        // Validation errors
                        this.errors = error.response.data.errors || {};
                    } else if (error.response.status === 401) {
                        this.$refs.toast.showToast(
                            error.response.data.message,
                            "danger",
                        );
                    } else if (error.response.status === 403) {
                        this.$refs.toast.showToast(
                            error.response.data.message,
                            "warning",
                        );
                    }
                }
            } finally {
            }
        },

        fill() {
            this.email = "";
            this.password = "";
            this.messageToaste = "";
        },
        toggleShowPassword() {
            const passwordField = document.getElementById("password");
            const type =
                passwordField.type === "password" ? "text" : "password";
            passwordField.type = type;
        },
        clickSignupLink() {
            window.location.href = "/landlordregister";
        },
    },
};
</script>
<style scoped src="./../../../css/accountprocess/landlordLogin.css"></style>
