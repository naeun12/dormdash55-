<template>
    <Loader ref="loader" />

    <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 p-3 bg-light shadow-sm rounded"
    >
        <h1 class="fw-bold text-dark mb-3 mb-md-0" style="font-size: 1.8rem">
            Tenant Management Dashboard
        </h1>
        <button
            class="btn btn-primary d-inline-flex align-items-center px-4 py-2 btn-hover-effect"
            @click="downloadReport"
        >
            <i class="bi bi-file-earmark-arrow-down me-2"></i>
            Download Tenant Report
        </button>
    </div>

    <div
        class="input-group w-100 shadow-sm rounded-pill overflow-hidden mb-3"
        style="border: 2px solid #4edce2"
    >
        <span class="input-group-text bg-white border-0">
            <i class="bi bi-search text-primary"></i>
        </span>
        <input
            type="text"
            class="form-control border-0 shadow-none"
            placeholder="Search Tenants name"
            aria-label="Search Tenant "
            v-model="searchTerm"
            style="border: 2px solid #4edce2"
        />
    </div>

    <div
        class="table-responsive shadow-sm rounded border border-1"
        v-if="tenants && tenants.length"
    >
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-info">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">First Name</th>
                    <th scope="col">Last Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(tenant, index) in tenants" :key="tenant.tenantID">
                    <th scope="row">{{ tenant.tenantID }}</th>
                    <td>{{ tenant.firstname }}</td>
                    <td>{{ tenant.lastname }}</td>
                    <td>{{ tenant.email }}</td>
                    <td>
                        <span
                            :class="
                                tenant.is_deactivated
                                    ? 'badge bg-danger'
                                    : 'badge bg-success'
                            "
                        >
                            {{
                                tenant.is_deactivated ? "Deactivated" : "Active"
                            }}
                        </span>
                    </td>
                    <td class="d-flex flex-wrap gap-2">
                        <button
                            class="btn btn-success btn-sm d-flex align-items-center"
                            @click="viewTenantProfile(tenant.tenantID)"
                        >
                            <i class="bi bi-eye me-1"></i> View
                        </button>

                        <button
                            class="btn btn-warning btn-sm d-flex align-items-center"
                            :disabled="tenant.is_deactivated"
                            @click="deactivatedTenant(tenant.tenantID)"
                        >
                            <i class="bi bi-slash-circle me-1"></i> Deactivate
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center flex-wrap">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <button
                        class="page-link"
                        @click="fetchTenantsList(currentPage - 1)"
                    >
                        Previous
                    </button>
                </li>

                <li
                    class="page-item"
                    v-for="page in lastPage"
                    :key="page"
                    :class="{ active: currentPage === page }"
                >
                    <button class="page-link" @click="fetchTenantsList(page)">
                        {{ page }}
                    </button>
                </li>

                <li
                    class="page-item"
                    :class="{ disabled: currentPage === lastPage }"
                >
                    <button
                        class="page-link"
                        @click="fetchTenantsList(currentPage + 1)"
                    >
                        Next
                    </button>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Fallback when no tenants -->
    <div
        v-else
        class="alert alert-info text-center shadow-sm rounded d-flex align-items-center justify-content-center"
    >
        <i class="bi bi-exclamation-circle me-2"></i>
        No tenants found.
    </div>
    <div
        v-if="tenantviewModal"
        class="modal d-block fade show"
        tabindex="-1"
        style="background-color: rgba(0, 0, 0, 0.5)"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div
                class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
            >
                <!-- Modal Header with gradient -->
                <div
                    class="modal-header p-3"
                    style="
                        background: linear-gradient(90deg, #4e73df, #224abe);
                        color: #fff;
                    "
                >
                    <h5 class="modal-title">Tenant Profile</h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="tenantviewModal = false"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <div
                        v-if="selectedTenant"
                        class="d-flex flex-column flex-md-row align-items-center align-items-md-start"
                    >
                        <!-- Profile Image -->
                        <div
                            class="text-center text-md-start mb-3 mb-md-0 me-md-4"
                        >
                            <img
                                :src="
                                    selectedTenant.previewPicUrl
                                        ? selectedTenant.previewPicUrl
                                        : selectedTenant.profilePicUrl
                                          ? '/storage/' +
                                            selectedTenant.profilePicUrl
                                          : '/default-avatar.png'
                                "
                                class="rounded-circle border border-3 border-white shadow-sm"
                                style="
                                    width: 150px;
                                    height: 150px;
                                    object-fit: cover;
                                "
                                alt="Landlord Profile"
                            />

                            <div>
                                <span
                                    class="badge mt-2"
                                    :class="
                                        selectedTenant.is_deactivated
                                            ? 'bg-danger'
                                            : 'bg-success'
                                    "
                                >
                                    {{
                                        selectedTenant.is_deactivated
                                            ? "Deactivated"
                                            : "Active"
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Tenant Info -->
                        <div class="flex-fill">
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >First Name:</strong
                                        >
                                        {{ selectedTenant.firstname }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >Last Name:</strong
                                        >
                                        {{ selectedTenant.lastname }}
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >Gender:</strong
                                        >
                                        {{ selectedTenant.gender }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >Current Address:</strong
                                        >
                                        {{ selectedTenant.currentAddress }}
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >Email:</strong
                                        >
                                        {{ selectedTenant.email }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >Phone:</strong
                                        >
                                        {{ selectedTenant.phoneNumber }}
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >City:</strong
                                        >
                                        {{ selectedTenant.city }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong class="text-primary"
                                            >Province:</strong
                                        >
                                        {{ selectedTenant.province }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button
                        class="btn btn-success d-inline-flex align-items-center"
                        @click="reActivateTenant(selectedTenant.tenantID)"
                        :disabled="!selectedTenant.is_deactivated"
                        style="transition: transform 0.2s"
                        onmouseover="this.style.transform = 'scale(1.1)'"
                        onmouseout="this.style.transform = 'scale(1)'"
                    >
                        <i class="bi bi-arrow-repeat me-1"></i>
                        <!-- Reactivate icon -->
                        Re Active
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="OpensendEmailModal(selectedTenant)"
                    >
                        <i class="bi bi-envelope-fill me-1"></i> Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div
        v-if="emailModal"
        class="modal d-block fade show"
        tabindex="-1"
        style="background-color: rgba(0, 0, 0, 0.5)"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div
                class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
            >
                <!-- Modal Header with gradient -->
                <div
                    class="modal-header p-3"
                    style="
                        background: linear-gradient(90deg, #4e73df, #224abe);
                        color: #fff;
                    "
                >
                    <h5 class="modal-title">Send Email</h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @click="ClosesendEmailModal"
                        aria-label="Close"
                    ></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4 bg-light">
                    <form @submit.prevent="sendEmail">
                        <div class="mb-3">
                            <label for="emailTo" class="form-label">To</label>
                            <input
                                type="email"
                                class="form-control"
                                id="emailTo"
                                v-model="emailForm.to"
                                placeholder="Recipient email"
                                required
                            />
                        </div>
                        <div class="mb-3">
                            <label for="emailSubject" class="form-label"
                                >Subject</label
                            >
                            <input
                                type="text"
                                class="form-control"
                                id="emailSubject"
                                v-model="emailForm.subject"
                                placeholder="Email subject"
                                required
                            />
                        </div>
                        <div class="mb-3">
                            <label for="emailMessage" class="form-label"
                                >Message</label
                            >
                            <textarea
                                class="form-control"
                                id="emailMessage"
                                rows="5"
                                v-model="emailForm.message"
                                placeholder="Type your message..."
                                required
                            ></textarea>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light">
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="sendEmail"
                    >
                        <i class="bi bi-envelope-fill me-1"></i> Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Modalconfirmation ref="modal" />
    <Toastcomponents ref="toast" />
</template>
<script>
import axios from "axios";
import Loader from "@/components/loader.vue";
import Modalconfirmation from "@/components/modalconfirmation.vue";
import Toastcomponents from "@/components/Toastcomponents.vue";

export default {
    components: {
        Toastcomponents,
        Loader,
        Modalconfirmation,
    },
    data() {
        return {
            tenants: [],
            currentPage: 1,
            lastpage: 1,
            tenantviewModal: false,
            selectedTenant: [],
            emailModal: false,
            emailForm: {
                to: "",
                subject: "",
                message: "",
            },
            searchTerm: "",
        };
    },
    methods: {
        async fetchTenantsList(page = 1) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get("/admin/get-tenants", {
                    params: { q: this.searchTerm, page },
                });
                this.tenants = response.data.data;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
            } catch (error) {
                console.log(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async viewTenantProfile(id) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get(`/admin/view-tenants/${id}`);
                const tenant = response.data;

                this.selectedTenant = tenant;
                this.tenantviewModal = true;

                // You can now open a modal or display tenant info
            } catch (error) {
                console.error("Error fetching tenant profile:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async deactivatedTenant(id) {
            const confirmed = await this.$refs.modal.show({
                title: "Deactivate Tenant",
                message: `Are you sure you want to deactivate this tenant account?`,
                functionName: "Deactivate", // this can be used for the button text inside the modal
            });

            if (!confirmed) {
                return;
            }
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get(`/admin/deact-tenant/${id}`);
                this.fetchTenantsList();
                this.$refs.toast.showToast(response.data.message, "success");
            } catch (error) {
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async reActivateTenant(id) {
            const confirmed = await this.$refs.modal.show({
                title: "Reactivate Tenant",
                message: `Do you want to reactivate this tenant's account? They will regain full access once reactivated.`,
                functionName: "Reactivate", // text on the confirm button
            });

            if (!confirmed) {
                return;
            }
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get(`/admin/active-tenant/${id}`);
                this.fetchTenantsList();
                this.tenantviewModal = false;
                this.$refs.toast.showToast(response.data.message, "success");
            } catch (error) {
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        OpensendEmailModal(tenant) {
            this.emailForm.to = tenant.email; // pre-fill recipient
            this.emailForm.subject = tenant.firstname; // optional: reset subject
            this.emailForm.message = ""; // optional: reset message
            this.emailModal = true;
            this.tenantviewModal = false;
        },
        ClosesendEmailModal() {
            this.emailModal = false;
            this.tenantviewModal = true;
        },
        async sendEmail() {
            const formdata = new FormData();
            formdata.append("to", this.emailForm.to);
            formdata.append("subject", this.emailForm.subject);
            formdata.append("message", this.emailForm.message);
            const response = await axios.post(
                "/admin/tenant/send/email",
                formdata,
            );
            this.emailModal = false;

            this.$refs.toast.showToast(response.data.message, "success");
        },
        downloadReport() {
            try {
                this.$refs.loader.loading = true;

                window.open("/generate-tenant-report", "_blank");
            } catch {
            } finally {
                this.$refs.loader.loading = false;
            }
        },
    },
    mounted() {
        this.fetchTenantsList();
    },
    watch: {
        searchTerm(newVal) {
            axios
                .get("/admin/search-tenants", { params: { q: newVal } })
                .then((res) => {
                    this.tenants = res.data.data.data; // for paginated results, data is nested
                })
                .catch((err) => console.error(err));
        },
    },
};
</script>
