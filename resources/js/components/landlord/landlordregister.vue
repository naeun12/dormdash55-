<template>
    <Loader ref="loader" />
    <Toastcomponents ref="toast" />

    <div
        class="container py-5 d-flex justify-content-center animate__animated animate__fadeIn"
    >
        <div
            class="card border-0 shadow-lg overflow-hidden w-100"
            style="max-width: 800px; border-radius: 30px; background: #ffffff"
        >
            <div
                style="
                    height: 8px;
                    background: linear-gradient(90deg, #003c87, #fc7d07);
                "
            ></div>

            <div class="card-body p-4 p-md-5">
                <div class="mb-5">
                    <div
                        class="d-flex justify-content-between position-relative"
                    >
                        <div
                            class="position-absolute top-50 start-0 end-0 translate-middle-y"
                            style="height: 2px; background: #edf2f7; z-index: 0"
                        ></div>

                        <div
                            class="position-absolute top-50 start-0 translate-middle-y transition-all"
                            style="height: 2px; background: #003c87; z-index: 0"
                            :style="{
                                width:
                                    (currentStep / (steps.length - 1)) * 100 +
                                    '%',
                            }"
                        ></div>

                        <div
                            v-for="(step, index) in steps"
                            :key="index"
                            class="position-relative text-center"
                            style="z-index: 1"
                        >
                            <button
                                class="btn rounded-circle d-flex align-items-center justify-content-center border-2 p-0 mx-auto transition-all step-bubble"
                                :class="
                                    currentStep >= index
                                        ? 'active-step'
                                        : 'inactive-step'
                                "
                                :disabled="index > currentStep"
                                style="
                                    width: 40px;
                                    height: 40px;
                                    font-weight: 700;
                                "
                            >
                                <i
                                    v-if="currentStep > index"
                                    class="bi bi-check-lg"
                                ></i>
                                <span v-else>{{ index + 1 }}</span>
                            </button>
                            <p
                                class="small mt-2 mb-0 d-none d-md-block fw-bold text-uppercase"
                                :style="{
                                    color:
                                        currentStep === index
                                            ? '#003C87'
                                            : '#adb5bd',
                                    fontSize: '10px',
                                    letterSpacing: '1px',
                                }"
                            >
                                {{ step }}
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="nextStep">
                    <div class="tab-content">
                        <div
                            v-if="currentStep === 0"
                            class="animate__animated animate__fadeIn"
                        >
                            <div class="text-center mb-4">
                                <h2 class="fw-bold" style="color: #003c87">
                                    Create
                                    <span style="color: #fc7d07">Landlord</span>
                                    Account
                                </h2>
                                <p class="text-muted">
                                    Start by setting up your professional
                                    profile.
                                </p>
                            </div>

                            <div class="d-flex justify-content-center mb-5">
                                <div class="position-relative">
                                    <div
                                        class="avatar-wrapper rounded-circle border border-4 border-white shadow-sm overflow-hidden"
                                        style="
                                            width: 130px;
                                            height: 130px;
                                            background: #f8fafc;
                                        "
                                    >
                                        <img
                                            class="profile-pic w-100 h-100 object-fit-cover"
                                            :src="previewPic"
                                            alt="Profile Picture"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="btn position-absolute bottom-0 end-0 rounded-circle shadow-sm d-flex align-items-center justify-content-center"
                                        @click="triggerProfileInput"
                                        style="
                                            width: 38px;
                                            height: 38px;
                                            background: #003c87;
                                            color: white;
                                            border: 3px solid white;
                                        "
                                    >
                                        <i class="bi bi-camera-fill"></i>
                                    </button>
                                    <input
                                        ref="fileInput"
                                        class="d-none"
                                        name="profile_pic"
                                        id="profile-pic"
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageUpload"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="errors.profilePic"
                                class="alert alert-danger border-0 rounded-3 small py-2 text-center mb-4"
                            >
                                <i
                                    class="bi bi-exclamation-triangle-fill me-2"
                                ></i
                                >{{ errors.profilePic[0] }}
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >FIRST NAME</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control custom-input"
                                        placeholder="John"
                                        v-model="firstname"
                                    />
                                    <span
                                        v-if="errors.firstname"
                                        class="text-danger small mt-1 d-block"
                                        >{{ errors.firstname[0] }}</span
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >LAST NAME</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control custom-input"
                                        placeholder="Doe"
                                        v-model="lastname"
                                    />
                                    <span
                                        v-if="errors.lastname"
                                        class="text-danger small mt-1 d-block"
                                        >{{ errors.lastname[0] }}</span
                                    >
                                </div>
                                <div class="col-12">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >EMAIL ADDRESS</label
                                    >
                                    <input
                                        type="email"
                                        class="form-control custom-input"
                                        placeholder="example@email.com"
                                        v-model="email"
                                    />
                                    <span
                                        v-if="errors.email"
                                        class="text-danger small mt-1 d-block"
                                        >{{ errors.email[0] }}</span
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >PASSWORD</label
                                    >
                                    <input
                                        id="password"
                                        type="password"
                                        class="form-control custom-input"
                                        placeholder="••••••••"
                                        v-model="password"
                                    />
                                    <span
                                        v-if="errors.password"
                                        class="text-danger small mt-1 d-block"
                                    >
                                        {{ errors.password[0] }}
                                    </span>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >CONFIRM PASSWORD</label
                                    >
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        class="form-control custom-input"
                                        placeholder="••••••••"
                                        v-model="password_confirmation"
                                    />
                                </div>
                                <div class="col-12">
                                    <div
                                        class="form-check form-switch mb-2 ms-1"
                                    >
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="showpassword"
                                            @click="showpassword"
                                            style="cursor: pointer"
                                        />
                                        <label
                                            class="form-check-label small text-muted"
                                            for="showpassword"
                                            >Show Passwords</label
                                        >
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >PHONE NUMBER</label
                                    >
                                    <input
                                        type="tel"
                                        class="form-control custom-input"
                                        placeholder="09XX XXX XXXX"
                                        v-model="phonenumber"
                                    />
                                    <span
                                        v-if="errors.phonenumber"
                                        class="text-danger small mt-1 d-block"
                                        >{{ errors.phonenumber[0] }}</span
                                    >
                                </div>
                                <div class="col-md-4">
                                    <label
                                        class="form-label small fw-bold text-muted"
                                        >GENDER</label
                                    >
                                    <select
                                        class="form-select custom-input"
                                        v-model="gender"
                                    >
                                        <option value="">Select</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="currentStep === 1"
                            class="animate__animated animate__fadeIn text-center py-4"
                        >
                            <h2 class="fw-bold mb-3" style="color: #003c87">
                                Identity Verification
                            </h2>
                            <p class="text-muted mb-5">
                                Please upload a valid government-issued ID.
                            </p>

                            <div
                                class="upload-area p-5 border-2 border-dashed rounded-4 transition-all"
                                @click="triggerGovIdInput"
                                style="
                                    cursor: pointer;
                                    background: #f8fafc;
                                    border-color: #cbd5e0;
                                "
                            >
                                <input
                                    ref="govIdInput"
                                    class="d-none"
                                    type="file"
                                    accept="image/*"
                                    @change="handleGovermentIdUpload"
                                />
                                <div class="mb-3">
                                    <i
                                        class="bi bi-card-heading"
                                        style="
                                            font-size: 3.5rem;
                                            color: #003c87;
                                        "
                                    ></i>
                                </div>
                                <h5 class="fw-bold">Upload Government ID</h5>
                                <p class="text-muted small">
                                    Click to browse or drag and drop
                                </p>
                            </div>

                            <div
                                v-if="govermentIdPicPreview"
                                class="mt-4 animate__animated animate__zoomIn"
                            >
                                <div class="position-relative d-inline-block">
                                    <img
                                        :src="govermentIdPicPreview"
                                        class="img-fluid rounded-3 shadow-sm border"
                                        style="max-height: 200px"
                                    />
                                    <button
                                        type="button"
                                        @click="removeGovermentPermitPic"
                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 rounded-circle shadow"
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="currentStep === 2"
                            class="animate__animated animate__fadeIn text-center py-4"
                        >
                            <h2 class="fw-bold mb-3" style="color: #003c87">
                                Business Accreditation
                            </h2>
                            <p class="text-muted mb-5">
                                Upload your valid Business Permit to start
                                listing.
                            </p>

                            <div
                                class="upload-area p-5 border-2 border-dashed rounded-4 transition-all"
                                @click="triggerBusinessPermitInput"
                                style="
                                    cursor: pointer;
                                    background: #f8fafc;
                                    border-color: #cbd5e0;
                                "
                            >
                                <input
                                    ref="businessPermitInput"
                                    class="d-none"
                                    type="file"
                                    accept="image/*"
                                    @change="handleBusinessPermitUpload"
                                />
                                <div class="mb-3">
                                    <i
                                        class="bi bi-file-earmark-check"
                                        style="
                                            font-size: 3.5rem;
                                            color: #003c87;
                                        "
                                    ></i>
                                </div>
                                <h5 class="fw-bold">Upload Business Permit</h5>
                                <p class="text-muted small">
                                    Click to browse or drag and drop
                                </p>
                            </div>

                            <div
                                v-if="businessIdPicPreview"
                                class="mt-4 animate__animated animate__zoomIn"
                            >
                                <div class="position-relative d-inline-block">
                                    <img
                                        :src="businessIdPicPreview"
                                        class="img-fluid rounded-3 shadow-sm border"
                                        style="max-height: 200px"
                                    />
                                    <button
                                        type="button"
                                        @click="removeBusinessPermitPic"
                                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 rounded-circle shadow"
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="currentStep === 3"
                            class="animate__animated animate__fadeIn text-center py-5"
                        >
                            <div class="mb-4">
                                <div
                                    class="d-inline-block p-4 rounded-circle mb-3"
                                    style="background: rgba(0, 60, 135, 0.05)"
                                >
                                    <i
                                        class="bi bi-shield-lock-fill"
                                        style="
                                            font-size: 2.5rem;
                                            color: #003c87;
                                        "
                                    ></i>
                                </div>
                                <h2 class="fw-bold" style="color: #003c87">
                                    OTP Verification
                                </h2>
                                <p class="text-muted">
                                    We've sent a 6-digit code to your email.
                                </p>
                            </div>

                            <div
                                class="d-flex justify-content-center gap-2 mb-4"
                            >
                                <input
                                    v-for="(digit, index) in otpdigits"
                                    :key="index"
                                    type="text"
                                    :ref="'otpInput' + index"
                                    maxlength="1"
                                    class="form-control text-center fw-bold fs-3 otp-input-box"
                                    v-model="otpdigits[index]"
                                    @input="handleInput(index, $event)"
                                    @keydown.backspace="
                                        handleBackspace(index, $event)
                                    "
                                />
                            </div>

                            <div class="mb-5">
                                <div
                                    class="badge rounded-pill px-3 py-2 fw-bold"
                                    style="background: #fff4e6; color: #fc7d07"
                                    v-if="otpTimer > 0"
                                >
                                    <i class="bi bi-clock-history me-2"></i
                                    >Expires in: {{ formattedTime }}
                                </div>
                            </div>

                            <div
                                class="d-grid gap-3 d-sm-flex justify-content-sm-center"
                            >
                                <button
                                    type="button"
                                    @click="RegisterLandlord"
                                    class="btn px-5 py-3 rounded-pill fw-bold text-white shadow-sm register-submit-btn"
                                    style="background: #003c87; border: none"
                                >
                                    Verify & Register Account
                                </button>
                                <button
                                    type="button"
                                    @click="resendOtp"
                                    class="btn btn-link text-decoration-none fw-bold align-self-center"
                                    :disabled="otpTimer > 0"
                                    :style="{
                                        color:
                                            otpTimer > 0
                                                ? '#cbd5e0'
                                                : '#003C87',
                                    }"
                                >
                                    Resend Code
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="currentStep < 3"
                        class="d-flex justify-content-between mt-5 pt-4 border-top"
                    >
                        <button
                            type="button"
                            class="btn btn-light px-4 py-2 rounded-3 fw-bold text-muted border"
                            @click="prevStep"
                            :disabled="currentStep === 0"
                        >
                            <i class="bi bi-chevron-left me-2"></i>Back
                        </button>
                        <button
                            type="button"
                            class="btn px-5 py-2 rounded-3 fw-bold shadow-sm text-white next-step-btn"
                            @click="nextStep"
                            :disabled="currentStep === steps.length - 1"
                            style="background: #003c87; border: none"
                        >
                            Next Step<i class="bi bi-chevron-right ms-2"></i>
                        </button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <p class="text-muted">
                        Do you already have an account?
                        <a :href="LoginLink" class="text-decoration-none"
                            >Login here</a
                        >
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Loader from "@/components/loader.vue";
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";

export default {
    components: {
        Toastcomponents,
        Loader,
    },

    name: "LandlordRegister",
    data() {
        return {
            //images
            previewPic: "/images/registertenant/Profile-PNG-Photo.png",
            govermentIdPicPreview: "",
            businessIdPicPreview: "",
            //messages, modals and toaster
            errors: {},
            //steps
            steps: [
                "Personal Details",
                "Identity Verification",
                "Business Documentation",
                "Email Verification",
            ],
            currentStep: 0,
            //data
            firstname: "",
            lastname: "",
            email: "",
            password: "",
            password_confirmation: "",
            phonenumber: "",
            gender: "",
            profilePic: "",
            governmentIdFile: "",
            businessPermitFile: "",
            //loader and otp
            otpTimer: 0,
            otpdigits: Array(6).fill(""),
        };
    },
    //timer

    methods: {
        showToast(message, color = "success") {
            this.messageToaster = message;
            this.toastColor = color;
            this.toaster = true;

            // Auto-hide after 3 seconds
            setTimeout(() => {
                this.ExitToaster();
            }, 3000);
        },
        ExitToaster() {
            this.toaster = false;
        },
        async nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                let isValid = true;

                if (this.currentStep === 0) {
                    isValid = this.PersonalDetails();
                } else if (this.currentStep === 1) {
                    isValid = this.IdentityVerification();
                } else if (this.currentStep === 2) {
                    isValid = this.BusinessDocumentation();
                }
            }
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
        // image handler
        //profilePic
        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.profilePic = file;
                this.previewPic = URL.createObjectURL(file);
            } else {
                this.previewPic =
                    "/images/registertenant/Profile-PNG-Photo.png";
            }
        },
        triggerProfileInput() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.click();
            }
        },
        //GovermentId Picture
        handleGovermentIdUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (this.govermentIdPicPreview) {
                    URL.revokeObjectURL(this.govermentIdPicPreview);
                }
                this.governmentIdFile = file;
                this.govermentIdPicPreview = URL.createObjectURL(file);
            }
        },
        removeGovermentPermitPic() {
            if (this.govermentIdPicPreview) {
                URL.revokeObjectURL(this.govermentIdPicPreview);
                this.govermentIdPicPreview = "";
                this.governmentIdFile = "";
            }
            this.govermentIdPicPreview = "";
            this.governmentIdFile = "";

            if (this.$refs.govIdInput) {
                this.$refs.govIdInput.value = ""; // Reset file input
            }
        },
        //Business Permit Picture
        handleBusinessPermitUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Create object URL and revoke previous one if exists
                if (this.businessIdPicPreview) {
                    URL.revokeObjectURL(this.businessIdPicPreview);
                }
                this.businessPermitFile = file;

                this.businessIdPicPreview = URL.createObjectURL(file);
            }
        },
        triggerBusinessPermitInput() {
            if (this.$refs.businessPermitInput) {
                this.$refs.businessPermitInput.click();
            }
        },
        triggerGovIdInput() {
            if (this.$refs.govIdInput) {
                this.$refs.govIdInput.click();
            }
        },
        removeBusinessPermitPic() {
            if (this.businessIdPicPreview) {
                URL.revokeObjectURL(this.businessIdPicPreview);
            }
            this.businessIdPicPreview = null;
            // Add null check for safety
            if (this.$refs.businessIdPicPreview) {
                this.$refs.businessIdPicPreview.value = ""; // Reset file input
            }
        },
        //validations
        //Input Data
        //personal Details
        async PersonalDetails() {
            this.$refs.loader.loading = true;

            const formData = new FormData();
            formData.append("firstname", this.firstname.trim());
            formData.append("lastname", this.lastname.trim());
            formData.append("email", this.email.trim());
            formData.append("phonenumber", this.phonenumber.trim());
            formData.append("password", this.password.trim());
            formData.append(
                "password_confirmation",
                this.password_confirmation.trim(),
            );
            formData.append("profilePic", this.profilePic);
            formData.append("gender", this.gender);
            try {
                const response = await axios.post(
                    "/personalDetails",
                    formData,
                    {
                        headers: {
                            // DON'T set Content-Type when using FormData
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );

                if (response.data.status === "success") {
                    this.currentStep = 1;
                    this.$refs.loader.loading = false;
                    this.errors = {};

                    return true;
                }
            } catch (error) {
                this.$refs.loader.loading = false;
                console.clear();
                if (error.response) {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors || {};
                    } else {
                        this.errorMessage = error.response.data;
                    }
                }
            }
        },
        //Identify Verifaction
        async IdentityVerification() {
            this.$refs.loader.loading = true;

            const formData = new FormData();

            formData.append("governmentIdPic", this.governmentIdFile);
            try {
                const response = await axios.post(
                    "/IdentityVerifaction",
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
                    this.currentStep = 2;
                    this.$refs.loader.loading = false;

                    return true;
                }
            } catch (error) {
                if (error.response) {
                    this.$refs.loader.loading = false;

                    if (error.response.status === 422) {
                        const errorMessages = Object.values(
                            error.response.data.errors,
                        )
                            .flat()
                            .join("\n");
                        console.log("Validation errors:", errorMessages);
                        this.$refs.toast.showToast(errorMessages, "danger");
                    } else {
                        console.error(
                            "Registration error:",
                            error.response.data,
                        );
                    }
                } else {
                    console.error("Network error:", error.message);
                }
            }
            return true;
        },

        //Business Documentation
        async BusinessDocumentation() {
            this.$refs.loader.loading = true;

            const formData = new FormData();
            formData.append("businessPermitPic", this.businessPermitFile);
            formData.append("email", this.email);

            try {
                const response = await axios.post(
                    "/businessPermitValidation",
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
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.startOtpTimer(response.data.timer);
                    this.currentStep = 3;
                    this.$refs.loader.loading = false;
                    return true;
                }
            } catch (error) {
                this.$refs.loader.loading = false;

                if (error.response) {
                    if (error.response.status === 422) {
                        const message = Object.values(
                            error.response.data.message,
                        )
                            .flat()
                            .join("\n");
                        this.$refs.toast.showToast(message, "danger");
                    } else {
                        const message = Object.values(
                            error.response.data.message,
                        )
                            .flat()
                            .join("\n");
                        this.$refs.toast.showToast(message, "danger");
                    }
                } else {
                    const message = Object.values(error.response.data.message)
                        .flat()
                        .join("\n");
                    this.$refs.toast.showToast(message, "danger");
                }
            } finally {
                this.$refs.loader.loading = false;
            }
            return false;
        },
        //Register Landlord
        async RegisterLandlord() {
            this.$refs.loader.loading = true;

            const formData = new FormData();
            formData.append("firstname", this.firstname.trim());
            formData.append("lastname", this.lastname.trim());
            formData.append("email", this.email.trim());
            formData.append("password", this.password.trim());
            formData.append(
                "password_confirmation",
                this.password_confirmation.trim(),
            );
            formData.append("phonenumber", this.phonenumber.trim());
            formData.append("gender", this.gender);
            formData.append("profilePic", this.profilePic);
            formData.append("governmentIdPic", this.governmentIdFile);
            formData.append("businessPermitPic", this.businessPermitFile);
            formData.append("codeotp", this.otpdigits.join(""));
            try {
                const response = await axios.post(
                    "/RegisterLandlord",
                    formData,
                    {
                        headers: {
                            // DON'T set Content-Type when using FormData
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    },
                );
                if (response.data.status === "success") {
                    this.$refs.toast.showToast(
                        response.data.message,
                        "success",
                    );
                    this.$refs.loader.loading = false;
                    this.Emptyfill();
                    this.currentStep = 0;
                    this.errors = {};
                    window.location.href = `/landlordLogin`;
                    return true;
                }
            } catch (error) {
                if (error.response) {
                    const response = error.response;
                    if (response.data.status === "error") {
                        this.$refs.toast.showToast(
                            response.data.message,
                            "danger",
                        );
                        this.$refs.loader.loading = false;
                    }
                } else {
                    this.$refs.toast.showToast(response.data.message, "danger");
                    this.$refs.loader.loading = false;
                }
            }
            return false;
        },

        //Timer and OTP
        startOtpTimer(timerValue) {
            const expirationTime = new Date(timerValue);
            const currentTime = new Date();
            let remainingSeconds = Math.floor(
                (expirationTime - currentTime) / 1000,
            );

            this.otpTimer = remainingSeconds;

            if (this.otpInterval) {
                clearInterval(this.otpInterval);
            }

            this.otpInterval = setInterval(() => {
                if (remainingSeconds <= 0) {
                    clearInterval(this.otpInterval);
                    this.otpTimer = 0;
                } else {
                    this.otpTimer = remainingSeconds;
                    remainingSeconds--;
                }
            }, 1000);
        },
        startTimer() {
            this.intervalId = setInterval(() => {
                if (this.otpTimer > 0) {
                    this.otpTimer--;
                } else {
                    this.stopTimer();
                }
            }, 1000);
        },
        stopTimer() {
            clearInterval(this.intervalId);
        },
        resetTimer() {
            this.otpTimer = 60;
            this.startTimer();
        },
        mounted() {
            this.startTimer();
        },
        beforeUnmount() {
            this.stopTimer();
        },
        handleInput(index, event) {
            const value = event.target.value;

            // Only allow digits (0-9)
            if (!/^\d$/.test(value)) {
                this.otpdigits[index] = "";
                return;
            }

            this.otpdigits[index] = value;

            // Move focus to next input if not last
            if (index < this.otpdigits.length - 1) {
                const nextInput = this.$refs[`otpInput${index + 1}`];
                if (nextInput) {
                    nextInput.focus();
                }
            }
        },

        handleOtpInput(index) {
            const currentValue = this.otpdigits[index];

            // If one digit is typed, move to the next input
            if (
                currentValue.length === 1 &&
                index < this.otpdigits.length - 1
            ) {
                this.$refs[`otpInput${index + 1}`]?.focus();
            }
        },

        handleBackspace(index, event) {
            if (
                event.key === "Backspace" &&
                this.otpdigits[index] === "" &&
                index > 0
            ) {
                const prevInput = this.$refs[`otpInput${index - 1}`];
                if (prevInput) {
                    prevInput.focus();
                }
            }
        },

        getOtpCode() {
            return this.otpDigits.join("");
        },
        handlePaste(event) {
            const pasted = event.clipboardData
                .getData("text")
                .replace(/\D/g, "")
                .slice(0, 6);
            for (let i = 0; i < pasted.length; i++) {
                this.otpdigits[i] = pasted[i];
            }
            this.$nextTick(() => {
                const nextIndex = pasted.length >= 6 ? 5 : pasted.length;
                this.$refs[`otpInput${nextIndex}`]?.focus();
            });
        },

        async resendOtp() {
            try {
                this.LoaderSendingEmail = true;
                this.errors = {};
                this.errorMessage = "";
                this.otpTimer = 0;
                const requestData = {
                    email: this.email.trim(),
                };
                const response = await axios.post("/resendOtp", requestData);
                if (response.data.status === "success") {
                    setTimeout(() => {
                        this.LoaderSendingEmail = false;
                    }, 300);
                    this.toaster = true;
                    this.Message = response.data.message;
                    this.toastColor = "success";
                    setTimeout(() => {
                        this.toaster = false;
                    }, 5000);
                    this.startOtpTimer(response.data.timer);
                }
            } catch (error) {
                // Handle validation errors (status 422)
                if (error.response && error.response.status === 422) {
                    this.LoaderSendingEmail = false;

                    this.errors = error.response.data.errors || {};
                    this.errorMessage = "Please correct the errors below.";
                }
                // Handle unexpected server errors (status 500)
                else if (error.response && error.response.status === 500) {
                    this.errorMessage =
                        error.response.data.message ||
                        "An unexpected error occurred. Please try again.";
                }
                // Handle network errors
                else {
                    this.errorMessage =
                        "A network error occurred. Please check your connection.";
                }
            } finally {
                // Re-enable the Resend OTP button
                // this.isResending = false;
            }
        },
        //filling data
        Emptyfill() {
            this.profilePic = "";
            this.previewPic = "/images/registertenant/Profile-PNG-Photo.png";
            this.firstname = "";
            this.lastname = "";
            this.email = "";
            this.password = "";
            this.password_confirmation = "";
            this.phonenumber = "";
            this.gender = "";
            this.govermentIdPicPreview = "";
            this.governmentIdFile = "";
            this.businessIdPicPreview = "";
            this.businessPermitFile = "";
            this.otpTimer = 0;
            this.otpdigits = Array(6).fill("");
        },
        showpassword() {
            const passwordField = document.getElementById("password");
            const confirmPasswordField = document.getElementById(
                "password_confirmation",
            );
            const type =
                passwordField.type === "password" ? "text" : "password";
            passwordField.type = type;
            confirmPasswordField.type = type;
        },
    },
    computed: {
        formattedTime() {
            const minutes = Math.floor(this.otpTimer / 60);
            const seconds = this.otpTimer % 60;
            return `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
        },
    },
    mounted() {
        this.$refs.loader.loading = false;
        this.$nextTick(() => {
            if (this.currentStep === 3) {
                this.$refs.otpInput0?.focus();
            }
        });
    },
};
</script>
<style scoped src="./../../../css/accountprocess/landlordRegister.css"></style>
