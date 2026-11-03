<template>
    <Loader ref="loader" />

    <div class="container-fluid px-0">
        <!-- Header Section -->
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 p-4 bg-white shadow-sm rounded-4 border-0"
        >
            <div>
                <h1
                    class="fw-bold text-dark mb-1"
                    style="font-size: 1.75rem; color: #2a2d34"
                >
                    <i
                        class="bi bi-people-fill me-2"
                        style="color: #003c87"
                    ></i>
                    Tenant Management
                </h1>
                <p class="text-muted small mb-0">
                    Monitor active accounts, view profiles, and manage
                    permissions.
                </p>
            </div>
            <button
                class="btn btn-primary d-inline-flex align-items-center px-4 py-2.5 rounded-pill shadow-sm mt-3 mt-md-0 fw-semibold custom-btn-primary"
                @click="downloadReport"
            >
                <i class="bi bi-file-earmark-arrow-down me-2"></i> Download
                Tenant Report
            </button>
        </div>

        <!-- Search Bar -->
        <div
            class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white custom-search-card"
        >
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0 ps-3">
                    <i class="bi bi-search fs-5" style="color: #003c87"></i>
                </span>
                <input
                    type="text"
                    class="form-control border-0 shadow-none bg-transparent ps-2 py-2"
                    placeholder="Search tenant name..."
                    aria-label="Search Tenant"
                    v-model="searchTerm"
                />
            </div>
        </div>

        <!-- Tenants Table Section -->
        <div
            class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white"
            v-if="tenants && tenants.length"
        >
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead
                        class="table-light text-uppercase fs-7 text-secondary fw-bold"
                    >
                        <tr>
                            <th scope="col" class="py-3 ps-4">#</th>
                            <th scope="col" class="py-3">First Name</th>
                            <th scope="col" class="py-3">Last Name</th>
                            <th scope="col" class="py-3">Email</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3 text-end pe-4">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(tenant, index) in tenants"
                            :key="tenant.tenantID"
                        >
                            <th scope="row" class="ps-4 text-muted fw-normal">
                                {{ tenant.tenantID }}
                            </th>
                            <td class="fw-semibold text-dark">
                                {{ tenant.firstname }}
                            </td>
                            <td class="fw-semibold text-dark">
                                {{ tenant.lastname }}
                            </td>
                            <td class="text-muted">{{ tenant.email }}</td>
                            <td>
                                <span
                                    :class="
                                        tenant.is_deactivated
                                            ? 'badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill fw-semibold'
                                            : 'badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold'
                                    "
                                >
                                    {{
                                        tenant.is_deactivated
                                            ? "Deactivated"
                                            : "Active"
                                    }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        class="btn btn-outline-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center"
                                        @click="
                                            viewTenantProfile(tenant.tenantID)
                                        "
                                    >
                                        <i class="bi bi-eye me-1"></i> View
                                    </button>

                                    <button
                                        class="btn btn-outline-danger btn-sm rounded-pill px-3 d-inline-flex align-items-center"
                                        :disabled="tenant.is_deactivated"
                                        @click="
                                            deactivatedTenant(tenant.tenantID)
                                        "
                                    >
                                        <i class="bi bi-slash-circle me-1"></i>
                                        Deactivate
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-0 py-3">
                <nav aria-label="Page navigation">
                    <ul
                        class="pagination justify-content-center mb-0 flex-wrap"
                    >
                        <li
                            class="page-item"
                            :class="{ disabled: currentPage === 1 }"
                        >
                            <button
                                class="page-link rounded-start-pill border-0 shadow-none text-secondary"
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
                            <button
                                class="page-link border-0 shadow-none mx-1 rounded-circle custom-page-bubble"
                                @click="fetchTenantsList(page)"
                            >
                                {{ page }}
                            </button>
                        </li>

                        <li
                            class="page-item"
                            :class="{ disabled: currentPage === lastPage }"
                        >
                            <button
                                class="page-link rounded-end-pill border-0 shadow-none text-secondary"
                                @click="fetchTenantsList(currentPage + 1)"
                            >
                                Next
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Fallback when no tenants -->
        <div
            v-else
            class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center"
        >
            <div class="text-muted mb-2">
                <i
                    class="bi bi-folder2-open display-4"
                    style="color: #003c87"
                ></i>
            </div>
            <h5 class="fw-bold text-dark">No tenants found</h5>
            <p class="text-muted small mb-0">
                Try checking your search keyword or add new tenants.
            </p>
        </div>
    </div>

    <!-- Tenant Profile Modal -->
    <div
        v-if="tenantviewModal"
        class="modal fade show d-block custom-modal-backdrop"
        tabindex="-1"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div
                class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
            >
                <!-- Modal Header -->
                <div
                    class="modal-header custom-modal-header text-white p-4 border-0"
                >
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-badge me-2"></i> Tenant Profile
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white shadow-none"
                        @click="tenantviewModal = false"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <div
                        v-if="selectedTenant"
                        class="row g-4 align-items-center"
                    >
                        <div class="col-md-4 text-center">
                            <img
                                :src="
                                    selectedTenant.previewPicUrl
                                        ? selectedTenant.previewPicUrl
                                        : selectedTenant.profilePicUrl
                                          ? '/storage/' +
                                            selectedTenant.profilePicUrl
                                          : '/default-avatar.png'
                                "
                                class="rounded-circle border border-3 border-white shadow-sm mb-3"
                                style="
                                    width: 130px;
                                    height: 130px;
                                    object-fit: cover;
                                "
                                alt="Tenant Profile"
                            />
                            <div>
                                <span
                                    class="badge px-3 py-2 rounded-pill fw-semibold"
                                    :class="
                                        selectedTenant.is_deactivated
                                            ? 'bg-danger bg-opacity-10 text-danger'
                                            : 'bg-success bg-opacity-10 text-success'
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

                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-6">
                                    <span
                                        class="text-muted small text-uppercase fw-semibold d-block"
                                        >First Name</span
                                    >
                                    <span class="fw-bold text-dark">{{
                                        selectedTenant.firstname
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span
                                        class="text-muted small text-uppercase fw-semibold d-block"
                                        >Last Name</span
                                    >
                                    <span class="fw-bold text-dark">{{
                                        selectedTenant.lastname
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span
                                        class="text-muted small text-uppercase fw-semibold d-block"
                                        >Gender</span
                                    >
                                    <span class="text-dark">{{
                                        selectedTenant.gender || "N/A"
                                    }}</span>
                                </div>
                                <div class="col-6">
                                    <span
                                        class="text-muted small text-uppercase fw-semibold d-block"
                                        >Phone</span
                                    >
                                    <span class="text-dark">{{
                                        selectedTenant.phoneNumber || "N/A"
                                    }}</span>
                                </div>
                                <div class="col-12">
                                    <span
                                        class="text-muted small text-uppercase fw-semibold d-block"
                                        >Email</span
                                    >
                                    <span class="text-dark">{{
                                        selectedTenant.email
                                    }}</span>
                                </div>
                                <div class="col-12">
                                    <span
                                        class="text-muted small text-uppercase fw-semibold d-block"
                                        >Current Address</span
                                    >
                                    <span class="text-dark"
                                        >{{
                                            selectedTenant.currentAddress ||
                                            "N/A"
                                        }}, {{ selectedTenant.city || "" }}
                                        {{
                                            selectedTenant.province || ""
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="modal-footer bg-white border-0 p-3 d-flex justify-content-between"
                >
                    <button
                        class="btn btn-outline-success rounded-pill px-4 fw-semibold"
                        @click="reActivateTenant(selectedTenant.tenantID)"
                        :disabled="!selectedTenant.is_deactivated"
                    >
                        <i class="bi bi-arrow-repeat me-1"></i> Reactivate
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary rounded-pill px-4 fw-semibold custom-btn-primary"
                        @click="OpensendEmailModal(selectedTenant)"
                    >
                        <i class="bi bi-envelope-fill me-1"></i> Send Email
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Email Modal -->
    <div
        v-if="emailModal"
        class="modal fade show d-block custom-modal-backdrop"
        tabindex="-1"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div
                class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
            >
                <div
                    class="modal-header custom-modal-header text-white p-4 border-0"
                >
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-envelope-paper me-2"></i> Send Email to
                        Tenant
                    </h5>
                    <button
                        type="button"
                        class="btn-close btn-close-white shadow-none"
                        @click="ClosesendEmailModal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <form @submit.prevent="sendEmail">
                        <div class="mb-3">
                            <label
                                for="emailTo"
                                class="form-label fw-semibold text-secondary small text-uppercase"
                                >To</label
                            >
                            <input
                                type="email"
                                class="form-control rounded-3 py-2 px-3 border-0 bg-white shadow-sm text-muted"
                                id="emailTo"
                                v-model="emailForm.to"
                                readonly
                                required
                            />
                        </div>
                        <div class="mb-3">
                            <label
                                for="emailSubject"
                                class="form-label fw-semibold text-secondary small text-uppercase"
                                >Subject</label
                            >
                            <input
                                type="text"
                                class="form-control rounded-3 py-2 px-3 border-0 bg-white shadow-sm"
                                id="emailSubject"
                                v-model="emailForm.subject"
                                placeholder="Enter email subject..."
                                required
                            />
                        </div>
                        <div class="mb-3">
                            <label
                                for="emailMessage"
                                class="form-label fw-semibold text-secondary small text-uppercase"
                                >Message</label
                            >
                            <textarea
                                class="form-control rounded-3 py-2 px-3 border-0 bg-white shadow-sm"
                                id="emailMessage"
                                rows="5"
                                v-model="emailForm.message"
                                placeholder="Type your message here..."
                                required
                            ></textarea>
                        </div>
                    </form>
                </div>

                <div
                    class="modal-footer bg-white border-0 p-3 d-flex justify-content-end gap-2"
                >
                    <button
                        type="button"
                        class="btn btn-outline-secondary rounded-pill px-4 fw-medium"
                        @click="ClosesendEmailModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm custom-btn-primary"
                        @click="sendEmail"
                    >
                        <i class="bi bi-send-fill me-1"></i> Send Email
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
<style scoped>
.custom-btn-primary {
    background-color: #003c87 !important;
    border-color: #003c87 !important;
    transition: all 0.2s ease-in-out;
}

.custom-btn-primary:hover {
    background-color: #002b66 !important;
    border-color: #002b66 !important;
    transform: translateY(-1px);
}

.custom-search-card {
    border: 1.5px solid rgba(0, 60, 135, 0.1) !important;
    transition: all 0.2s ease-in-out;
}

.custom-search-card:focus-within {
    border-color: #003c87 !important;
    box-shadow: 0 0 0 4px #e8f1ff !important;
}

.custom-modal-backdrop {
    background-color: rgba(15, 23, 42, 0.65) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.custom-modal-header {
    background: linear-gradient(135deg, #003c87 0%, #002b66 100%) !important;
}

.custom-page-bubble {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-item.active .custom-page-bubble {
    background-color: #003c87 !important;
    color: #fff !important;
}
</style>
