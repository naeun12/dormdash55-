<template>
    <Loader ref="loader" />
    <Toastcomponents ref="toast" />

    <div
        class="container d-flex align-items-center justify-content-center min-vh-100 py-5"
    >
        <div
            class="card border-0 shadow-lg p-4 p-sm-5 mx-auto w-100"
            style="max-width: 550px; border-radius: 24px; background: #ffffff"
        >
            <!-- Header Section -->
            <div class="card-body text-center p-0 mb-4">
                <div
                    class="icon-box mb-3 mx-auto d-flex align-items-center justify-content-center shadow-sm"
                    style="
                        width: 70px;
                        height: 70px;
                        border-radius: 20px;
                        background: rgba(0, 60, 135, 0.08);
                    "
                >
                    <span class="fs-2">👋</span>
                </div>
                <h1 class="fw-bold mb-2" style="color: var(--primary-blue)">
                    Hello Admin
                </h1>
                <p class="text-muted fs-6 px-lg-4 mb-0">
                    Continue managing the platform, including tenants and
                    landlord accounts.
                </p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="adminLogin" novalidate>
                <div class="row g-3">
                    <!-- Username -->
                    <div class="col-12">
                        <label
                            for="username"
                            class="form-label fw-semibold text-secondary small text-uppercase tracking-wider"
                            >Username</label
                        >
                        <input
                            type="text"
                            id="username"
                            name="username"
                            v-model="username"
                            class="form-control form-control-lg px-3 py-2 fs-6 custom-input"
                            placeholder="Enter your username"
                        />
                        <span
                            v-if="errors.username"
                            class="text-danger small mt-1 d-block"
                        >
                            {{ errors.username[0] }}
                        </span>
                    </div>

                    <!-- Password -->
                    <div class="col-12">
                        <label
                            for="password"
                            class="form-label fw-semibold text-secondary small text-uppercase tracking-wider"
                            >Password</label
                        >
                        <div class="input-group">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                v-model="password"
                                class="form-control form-control-lg px-3 py-2 fs-6 custom-input"
                                placeholder="Enter your password"
                            />
                        </div>
                        <span
                            v-if="errors.password"
                            class="text-danger small mt-1 d-block"
                        >
                            {{ errors.password[0] }}
                        </span>
                    </div>

                    <!-- Show Password & Forgot Password Layout -->
                    <div
                        class="col-12 d-flex align-items-center justify-content-between mt-2"
                    >
                        <div class="form-check d-flex align-items-center m-0">
                            <input
                                type="checkbox"
                                id="show-password"
                                class="form-check-input custom-checkbox me-2"
                                @click="toggleShowPassword"
                            />
                            <label
                                for="show-password"
                                class="form-check-label text-muted small cursor-pointer"
                            >
                                Show Password
                            </label>
                        </div>
                    </div>

                    <!-- Sign In Button -->
                    <div class="col-12 mt-4">
                        <button
                            type="submit"
                            class="btn btn-lg w-100 py-3 shadow-sm text-white fw-semibold custom-btn-orange"
                        >
                            Sign In
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";
import Loader from "@/components/loader.vue";

export default {
    components: {
        Toastcomponents,
        Loader,
    },
    data() {
        return {
            username: "",
            password: "",
            errors: {},
            linkSignup: "",
        };
    },
    methods: {
        async adminLogin() {
            this.$refs.loader.loading = true;

            const formData = new FormData();
            formData.append("username", this.username);
            formData.append("password", this.password);

            try {
                const response = await axios.post("/login-admin", formData, {
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
                if (response.data.status === "success") {
                    const userId = response.data.admin.id;
                    window.location.href = `/admin/dashboard/${userId}`;
                    this.$refs.loader.loading = false;
                    this.errors = {};
                    this.fill();
                    return true;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                if (error.response) {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.message;
                    }
                    if (error.response.status === 401) {
                        this.errors = error.response.data.message;
                        this.$refs.toast.showToast(
                            error.response.data.message,
                            "danger",
                        );
                        this.errors = {};
                    }
                }
            }
        },

        fill() {
            this.username = "";
            this.password = "";
        },
        toggleShowPassword() {
            const passwordField = document.getElementById("password");
            passwordField.type =
                passwordField.type === "password" ? "text" : "password";
        },
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

/* Custom Input Styling */
.custom-input {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.2s ease-in-out;
    background-color: #f8fafc;
}

.custom-input:focus {
    border-color: #003c87;
    box-shadow: 0 0 0 4px rgba(0, 60, 135, 0.1);
    background-color: #ffffff;
}

/* Accent Orange Button */
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

/* Checkbox Accent */
.custom-checkbox {
    border: 2px solid #e2e8f0;
    width: 1.15em;
    height: 1.15em;
    cursor: pointer;
}

.custom-checkbox:checked {
    background-color: #003c87;
    border-color: #003c87;
}

.cursor-pointer {
    cursor: pointer;
}
</style>
