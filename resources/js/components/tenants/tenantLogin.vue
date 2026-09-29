<template>
    <div
        class="card border-0 shadow-lg mt-5 py-5 px-4 mx-auto w-100 animate__animated animate__fadeIn"
        style="max-width: 550px; border-radius: 30px; background: #ffffff"
    >
        <div class="text-center mb-5 px-2">
            <div
                class="d-inline-block p-3 rounded-circle mb-3"
                style="background-color: rgba(0, 60, 135, 0.1)"
            >
                <i
                    class="bi bi-person-badge-fill fs-1"
                    style="color: #003c87"
                ></i>
            </div>
            <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -1.5px">
                Hello <span style="color: #fc7d07">Tenant!</span>
            </h1>
            <p class="text-muted mb-0 px-md-4" style="font-size: 15px">
                Welcome back to <strong>DormDash</strong>. Please log in to
                explore available dorms and manage your stay.
            </p>
        </div>

        <form @submit.prevent="TenantLogin" class="px-md-3">
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
                            border-color: #edf2f7;
                            border-radius: 12px 0 0 12px;
                        "
                    >
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input
                        type="email"
                        id="email"
                        v-model="email"
                        class="form-control p-3 border-2 border-start-0 custom-input"
                        placeholder="name@example.com"
                        style="
                            border-color: #edf2f7;
                            border-radius: 0 12px 12px 0;
                        "
                    />
                </div>
                <span
                    v-if="errors.email"
                    class="text-danger small mt-2 d-block animate__animated animate__headShake"
                >
                    <i class="bi bi-exclamation-circle me-1"></i
                    >{{ errors.email[0] }}
                </span>
            </div>

            <div class="mb-3">
                <label
                    for="password"
                    class="form-label small fw-bold text-muted text-uppercase ms-1"
                    >Password</label
                >
                <div class="input-group">
                    <span
                        class="input-group-text bg-light border-2 border-end-0"
                        style="
                            border-color: #edf2f7;
                            border-radius: 12px 0 0 12px;
                        "
                    >
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        v-model="password"
                        class="form-control border-2 border-start-0 p-3 custom-input"
                        placeholder="••••••••"
                        style="
                            border-color: #edf2f7;
                            border-radius: 0 12px 12px 0;
                        "
                    />
                </div>
                <span
                    v-if="errors.password"
                    class="text-danger small mt-2 d-block animate__animated animate__headShake"
                >
                    <i class="bi bi-exclamation-circle me-1"></i
                    >{{ errors.password[0] }}
                </span>
            </div>

            <div
                class="d-flex justify-content-between align-items-center mb-4 ms-1"
            >
                <div class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input shadow-none"
                        id="show-password"
                        @click="toggleShowPassword"
                        style="cursor: pointer; border-color: #cbd5e0"
                    />
                    <label
                        class="form-check-label small text-muted"
                        for="show-password"
                        style="cursor: pointer"
                    >
                        Show Password
                    </label>
                </div>
            </div>

            <div class="d-grid pt-2">
                <button
                    type="submit"
                    class="btn rounded-pill py-3 shadow border-0 fw-bold transition-all text-white login-btn"
                    style="background: #003c87; font-size: 1.1rem"
                >
                    Sign In <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </form>

        <div class="text-center mt-5">
            <p class="mb-0 text-muted">
                Don’t have an account yet?
                <a
                    @click="clickSignupLink"
                    class="text-decoration-none fw-bold ms-1"
                    style="cursor: pointer; color: #fc7d07"
                >
                    Create Account
                </a>
            </p>
        </div>

        <div
            v-show="toaster"
            :class="[
                'container-toast position-fixed bottom-0 start-50 translate-middle-x mb-4 z-3 animate__animated',
                toaster ? 'animate__backInUp' : 'animate__fadeOutDown',
            ]"
        >
            <div
                :class="['shadow-lg border-0']"
                :style="{
                    borderRadius: '15px',
                    minWidth: '300px',
                    backgroundColor:
                        toastColor === 'danger' ? '#e53e3e' : '#003C87',
                }"
            >
                <div class="d-flex align-items-center text-white py-3 px-4">
                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                    <div class="flex-grow-1 fw-medium">
                        {{ messageToaster }}
                    </div>
                    <button
                        type="button"
                        class="btn-close btn-close-white shadow-none ms-3"
                        @click="ExitToaster"
                    ></button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";
export default {
    data() {
        return {
            email: "",
            password: "",
            messageToaster: "",
            errors: {},
            toaster: false,
            toastColor: "",
        };
    },
    methods: {
        async TenantLogin() {
            if (this.loginValidation()) {
                const formData = new FormData();
                formData.append("email", this.email);
                formData.append("password", this.password);

                try {
                    const response = await axios.post(
                        "/tenant-login",
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
                        const userId = response.data.tenant.id;

                        const userName = response.data.tenant.firstname;
                        localStorage.setItem(
                            "tenant",
                            JSON.stringify(response.data.tenant),
                        );
                        window.location.href = `/homepage/${userId}`;
                        return true;
                    }
                } catch (error) {
                    this.toaster = true;

                    if (error.response) {
                        if (error.response.status === 422) {
                            // Validation errors
                            this.toastColor = "danger";
                            this.messageToaster =
                                "Please check your email and password.";
                        } else if (error.response.status === 401) {
                            // Wrong credentials
                            this.toastColor = "danger";
                            this.messageToaster =
                                error.response.data.message ||
                                "Invalid login credentials.";
                        } else if (error.response.status === 403) {
                            // Deactivated account
                            this.toastColor = "warning";
                            this.messageToaster =
                                error.response.data.message ||
                                "Your account is deactivated.";
                        } else {
                            // Other errors
                            this.toastColor = "danger";
                            this.messageToaster =
                                error.response.data.message ||
                                "An error occurred during login.";
                        }
                    } else {
                        this.toastColor = "danger";
                        this.messageToaster =
                            "Network error. Please try again.";
                    }

                    this.toasterTimeOut();
                }
            } else {
                this.toaster = true;
                this.toastColor = "danger";
                this.messageToaster = "Check Your Fields";
                this.toasterTimeOut();
            }
        },
        clickSignupLink() {
            window.location.href = `/tenantRegister`;
        },

        fill() {
            this.email = "";
            this.password = "";
            this.messageToaste = "";
        },
        loginValidation() {
            this.errors = {};
            if (!this.email)
                this.errors.email = ["Please enter your email address."];
            if (!this.password)
                this.errors.password = ["Please enter your password."];

            return Object.keys(this.errors).length === 0;
        },
        toggleShowPassword() {
            const passwordField = document.getElementById("password");
            const type =
                passwordField.type === "password" ? "text" : "password";
            passwordField.type = type;
        },
        ExitToaster() {
            this.toaster = false;
            this.messageToaster = "";
        },
        toasterTimeOut() {
            setTimeout(() => {
                this.toaster = false;
                this.toastColor = "";
                this.messageToaster = "";
            }, 5000);
        },
    },
};
</script>
<style scoped src="./../../../css/accountprocess/tenantLogin.css"></style>
