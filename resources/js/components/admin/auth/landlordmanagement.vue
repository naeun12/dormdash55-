<template>
    <div class="landlord-dashboard p-3 p-md-4">
        <Loader ref="loader" />

        <!-- Top Header Card -->
        <div
            class="card header-card border-0 shadow-sm rounded-4 mb-4 p-3 p-md-4 bg-white"
        >
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3"
            >
                <div class="d-flex align-items-center gap-3">
                    <div
                        class="header-icon-box rounded-3 text-white d-flex align-items-center justify-content-center"
                    >
                        <i class="bi bi-person-badge fs-3"></i>
                    </div>
                    <div>
                        <h1 class="h3 fw-bold mb-1 text-dark">
                            Landlord Management
                        </h1>
                        <p class="text-muted small mb-0">
                            Manage accounts, verify documentation, and
                            communicate with landlords.
                        </p>
                    </div>
                </div>
                <button
                    class="btn btn-orange d-inline-flex align-items-center px-4 py-2-5 rounded-3 fw-semibold shadow-sm"
                    @click="downloadReport"
                >
                    <i class="bi bi-file-earmark-arrow-down-fill me-2"></i>
                    Export Report
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-2">
                <div class="input-group input-group-lg search-group border-0">
                    <span class="input-group-text bg-transparent border-0 ps-3">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control border-0 shadow-none fs-6"
                        placeholder="Search landlord by name or email..."
                        aria-label="Search Landlord"
                        v-model="searchTerm"
                    />
                </div>
            </div>
        </div>

        <!-- Landlords Table Card -->
        <div
            class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4"
            v-if="landlords && landlords.length"
        >
            <div class="table-responsive">
                <table class="table align-middle mb-0 custom-table">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-4">ID</th>
                            <th scope="col">Landlord</th>
                            <th scope="col">Email</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="landlord in landlords"
                            :key="landlord.landlordID"
                        >
                            <td class="ps-4 fw-bold text-secondary">
                                #{{ landlord.landlordID }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div
                                        class="avatar-sm rounded-circle me-3 bg-light text-primary d-flex align-items-center justify-content-center fw-bold border"
                                    >
                                        {{
                                            landlord.firstname
                                                ? landlord.firstname
                                                      .charAt(0)
                                                      .toUpperCase()
                                                : "L"
                                        }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            {{ landlord.firstname }}
                                            {{ landlord.lastname }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary">{{ landlord.email }}</td>
                            <td>
                                <span
                                    class="badge-status"
                                    :class="
                                        landlord.is_deactivated
                                            ? 'status-deactivated'
                                            : 'status-active'
                                    "
                                >
                                    <i
                                        class="bi me-1"
                                        :class="
                                            landlord.is_deactivated
                                                ? 'bi-x-circle-fill'
                                                : 'bi-check-circle-fill'
                                        "
                                    ></i>
                                    {{
                                        landlord.is_deactivated
                                            ? "Deactivated"
                                            : "Active"
                                    }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        class="btn btn-outline-primary btn-sm rounded-2 d-inline-flex align-items-center px-3"
                                        @click="
                                            viewTenantProfile(
                                                landlord.landlordID,
                                            )
                                        "
                                    >
                                        <i class="bi bi-eye me-1"></i> View
                                    </button>
                                    <button
                                        class="btn btn-outline-danger btn-sm rounded-2 d-inline-flex align-items-center px-3"
                                        :disabled="landlord.is_deactivated"
                                        @click="
                                            deactivatedLandlord(
                                                landlord.landlordID,
                                            )
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

            <!-- Pagination Footer -->
            <div class="card-footer bg-white border-0 py-3">
                <nav aria-label="Landlord pagination">
                    <ul class="pagination justify-content-center mb-0 gap-1">
                        <li
                            class="page-item"
                            :class="{ disabled: currentPage === 1 }"
                        >
                            <button
                                class="page-link rounded-3 px-3"
                                @click="fetchTenantsList(currentPage - 1)"
                            >
                                <i class="bi bi-chevron-left me-1"></i> Prev
                            </button>
                        </li>

                        <li
                            class="page-item"
                            v-for="page in lastPage"
                            :key="page"
                            :class="{ active: currentPage === page }"
                        >
                            <button
                                class="page-link rounded-3 px-3"
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
                                class="page-link rounded-3 px-3"
                                @click="fetchTenantsList(currentPage + 1)"
                            >
                                Next <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white"
        >
            <div
                class="empty-state-icon mx-auto mb-3 text-muted rounded-circle d-flex align-items-center justify-content-center"
            >
                <i class="bi bi-person-x fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Landlords Found</h5>
            <p class="text-muted small mb-0">
                We couldn't find any landlord records matching your current
                filter.
            </p>
        </div>

        <!-- Landlord View Modal -->
        <div
            v-if="landlordviewModal"
            class="modal d-block fade show backdrop-blur"
            tabindex="-1"
            @click.self="landlordviewModal = false"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div
                    class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
                >
                    <!-- Modal Header -->
                    <div class="modal-header px-4 py-3 border-bottom">
                        <h5 class="modal-title fw-bold text-dark">
                            Landlord Details
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="landlordviewModal = false"
                            aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body p-4 bg-light-subtle">
                        <!-- Profile Card Header -->
                        <div
                            class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white text-center"
                        >
                            <div
                                class="position-relative d-inline-block mx-auto mb-3"
                            >
                                <img
                                    :src="
                                        selectedLandlord.previewPicUrl ||
                                        (selectedLandlord.profilePicUrl
                                            ? '/' +
                                              selectedLandlord.profilePicUrl
                                            : '/default-avatar.png')
                                    "
                                    class="rounded-circle border border-3 border-white shadow-sm"
                                    style="
                                        width: 110px;
                                        height: 110px;
                                        object-fit: cover;
                                    "
                                />
                            </div>
                            <h4 class="fw-bold text-dark mb-1">
                                {{ selectedLandlord.firstname }}
                                {{ selectedLandlord.lastname }}
                            </h4>
                            <p class="text-muted small mb-2">
                                {{ selectedLandlord.email }}
                            </p>

                            <div class="d-flex justify-content-center gap-2">
                                <span
                                    class="badge-status"
                                    :class="
                                        selectedLandlord.is_deactivated
                                            ? 'status-deactivated'
                                            : 'status-active'
                                    "
                                >
                                    {{
                                        selectedLandlord.is_deactivated
                                            ? "Deactivated"
                                            : "Active"
                                    }}
                                </span>
                                <span
                                    class="badge-status"
                                    :class="
                                        selectedLandlord.isVerified
                                            ? 'status-verified'
                                            : 'status-unverified'
                                    "
                                >
                                    {{
                                        selectedLandlord.isVerified
                                            ? "Verified Account"
                                            : "Unverified Account"
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div
                            class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white"
                        >
                            <h6 class="fw-bold text-dark mb-3">
                                Personal & Contact Info
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 bg-light">
                                        <div class="text-muted small">
                                            Gender
                                        </div>
                                        <div class="fw-semibold text-dark">
                                            {{
                                                selectedLandlord.gender || "N/A"
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 bg-light">
                                        <div class="text-muted small">
                                            Phone Number
                                        </div>
                                        <div class="fw-semibold text-dark">
                                            {{
                                                selectedLandlord.phoneNumber ||
                                                "N/A"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Verification Documents -->
                        <div
                            class="card border-0 shadow-sm rounded-3 p-4 bg-white"
                        >
                            <h6 class="fw-bold text-dark mb-3">
                                Verification Documents
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div
                                        class="doc-preview-card border rounded-3 p-2 text-center bg-light"
                                    >
                                        <span
                                            class="d-block text-muted small mb-2 fw-semibold"
                                            >Government ID</span
                                        >
                                        <img
                                            :src="
                                                selectedLandlord.govermentID
                                                    ? selectedLandlord.govermentID.startsWith(
                                                          'http',
                                                      )
                                                        ? selectedLandlord.govermentID
                                                        : '/' +
                                                          selectedLandlord.govermentID
                                                    : '/default-avatar.png'
                                            "
                                            class="img-fluid rounded border shadow-sm doc-img"
                                        />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div
                                        class="doc-preview-card border rounded-3 p-2 text-center bg-light"
                                    >
                                        <span
                                            class="d-block text-muted small mb-2 fw-semibold"
                                            >Business Permit</span
                                        >
                                        <img
                                            :src="
                                                selectedLandlord.businessPermit
                                                    ? selectedLandlord.businessPermit.startsWith(
                                                          'http',
                                                      )
                                                        ? selectedLandlord.businessPermit
                                                        : '/' +
                                                          selectedLandlord.businessPermit
                                                    : '/default-avatar.png'
                                            "
                                            class="img-fluid rounded border shadow-sm doc-img"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer bg-white px-4 py-3 border-top">
                        <button
                            class="btn btn-outline-success d-inline-flex align-items-center rounded-3 px-3"
                            @click="
                                reActivateLandlord(selectedLandlord.landlordID)
                            "
                            :disabled="!selectedLandlord.is_deactivated"
                        >
                            <i class="bi bi-arrow-repeat me-1"></i> Reactivate
                            Account
                        </button>

                        <button
                            type="button"
                            class="btn btn-primary d-inline-flex align-items-center rounded-3 px-4"
                            @click="OpensendEmailModal(selectedLandlord)"
                        >
                            <i class="bi bi-envelope-fill me-2"></i> Send Email
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Send Email Modal -->
        <div
            v-if="emailModal"
            class="modal d-block fade show backdrop-blur"
            tabindex="-1"
            @click.self="ClosesendEmailModal"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div
                    class="modal-content border-0 shadow-lg rounded-4 overflow-hidden"
                >
                    <div class="modal-header px-4 py-3 border-bottom">
                        <h5 class="modal-title fw-bold text-dark">
                            Send Direct Email
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            @click="ClosesendEmailModal"
                            aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body p-4 bg-white">
                        <form @submit.prevent="sendEmail" id="emailForm">
                            <div class="mb-3">
                                <label
                                    for="emailTo"
                                    class="form-label text-secondary small fw-semibold"
                                    >Recipient</label
                                >
                                <input
                                    type="email"
                                    class="form-control rounded-3"
                                    id="emailTo"
                                    v-model="emailForm.to"
                                    placeholder="Recipient email"
                                    required
                                    readonly
                                />
                            </div>
                            <div class="mb-3">
                                <label
                                    for="emailSubject"
                                    class="form-label text-secondary small fw-semibold"
                                    >Subject</label
                                >
                                <input
                                    type="text"
                                    class="form-control rounded-3"
                                    id="emailSubject"
                                    v-model="emailForm.subject"
                                    placeholder="Enter message subject"
                                    required
                                />
                            </div>
                            <div class="mb-3">
                                <label
                                    for="emailMessage"
                                    class="form-label text-secondary small fw-semibold"
                                    >Message</label
                                >
                                <textarea
                                    class="form-control rounded-3"
                                    id="emailMessage"
                                    rows="4"
                                    v-model="emailForm.message"
                                    placeholder="Type your message here..."
                                    required
                                ></textarea>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3 border-top">
                        <button
                            type="button"
                            class="btn btn-outline-secondary rounded-3 px-3"
                            @click="ClosesendEmailModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            form="emailForm"
                            class="btn btn-primary rounded-3 px-4"
                        >
                            <i class="bi bi-send-fill me-1"></i> Send Message
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Modalconfirmation ref="modal" />
        <Toastcomponents ref="toast" />
    </div>
</template>

<script>
import axios from "axios";
import Loader from "@/components/loader.vue";
import Modalconfirmation from "@/components/modalconfirmation.vue";
import Toastcomponents from "@/components/Toastcomponents.vue";

export default {
    name: "LandlordDashboard",
    components: {
        Toastcomponents,
        Loader,
        Modalconfirmation,
    },
    data() {
        return {
            landlords: [],
            currentPage: 1,
            lastPage: 1,
            landlordviewModal: false,
            selectedLandlord: {},
            emailModal: false,
            emailForm: {
                to: "",
                subject: "",
                message: "",
            },
            searchTerm: "",
            searchDebounceTimeout: null,
        };
    },
    methods: {
        async fetchTenantsList(page = 1) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get("/admin/get-landlord", {
                    params: { q: this.searchTerm, page },
                });
                this.landlords = response.data.data;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
            } catch (error) {
                console.error("Failed to fetch landlords list:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async viewTenantProfile(id) {
            try {
                this.$refs.loader.loading = true;

                const response = await axios.get(`/admin/view-landlord/${id}`);
                this.selectedLandlord = response.data;
                this.landlordviewModal = true;
            } catch (error) {
                console.error("Error fetching landlord profile:", error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async deactivatedLandlord(id) {
            const confirmed = await this.$refs.modal.show({
                title: "Deactivate Landlord",
                message: `Are you sure you want to deactivate this landlord account?`,
                functionName: "Deactivate",
            });

            if (!confirmed) return;
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get(`/admin/deact-landlord/${id}`);
                this.fetchTenantsList(this.currentPage);
                this.$refs.toast.showToast(response.data.message, "success");
            } catch (error) {
                console.error(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        async reActivateLandlord(id) {
            const confirmed = await this.$refs.modal.show({
                title: "Reactivate Landlord",
                message: `Do you want to reactivate this landlord's account? They will regain full access once reactivated.`,
                functionName: "Reactivate",
            });

            if (!confirmed) return;
            this.$refs.loader.loading = true;

            try {
                const response = await axios.get(
                    `/admin/active-landlord/${id}`,
                );
                this.fetchTenantsList(this.currentPage);
                this.landlordviewModal = false;
                this.$refs.toast.showToast(response.data.message, "success");
            } catch (error) {
                console.error(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        OpensendEmailModal(landlord) {
            this.emailForm.to = landlord.email;
            this.emailForm.subject = `Notice regarding your Landlord Account`;
            this.emailForm.message = "";
            this.emailModal = true;
            this.landlordviewModal = false;
        },
        ClosesendEmailModal() {
            this.emailModal = false;
            this.landlordviewModal = true;
        },
        async sendEmail() {
            try {
                this.$refs.loader.loading = true;

                const formdata = new FormData();
                formdata.append("to", this.emailForm.to);
                formdata.append("subject", this.emailForm.subject);
                formdata.append("message", this.emailForm.message);

                const response = await axios.post(
                    "/admin/landlord/send/email",
                    formdata,
                );
                this.emailModal = false;

                this.$refs.toast.showToast(
                    response.data.message || "Email sent successfully",
                    "success",
                );
            } catch (error) {
                console.error(error);
            } finally {
                this.$refs.loader.loading = false;
            }
        },
        downloadReport() {
            try {
                this.$refs.loader.loading = true;
                window.open("/generate-landlord-report", "_blank");
            } catch (error) {
                console.error(error);
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
            clearTimeout(this.searchDebounceTimeout);
            this.searchDebounceTimeout = setTimeout(() => {
                axios
                    .get("/admin/search-landlords", { params: { q: newVal } })
                    .then((res) => {
                        this.landlords = res.data.data.data || res.data.data;
                    })
                    .catch((err) => console.error(err));
            }, 300);
        },
    },
};
</script>

<style scoped>
/* Theme Colors Variables */
:root {
    --primary-blue: #003c87;
    --primary-blue-hover: #002c63;
    --accent-orange: #fc7d07;
    --accent-orange-hover: #e06c00;
    --text-muted: #94a3b8;
    --border-soft: #e2e8f0;
}

/* Base Dashboard Styling */
.landlord-dashboard {
    background-color: #f8fafc;
    min-height: 100vh;
}

/* Header Elements */
.header-icon-box {
    width: 48px;
    height: 48px;
    background-color: var(--primary-blue, #003c87);
}

.btn-primary {
    background-color: var(--primary-blue, #003c87) !important;
    border-color: var(--primary-blue, #003c87) !important;
}

.btn-primary:hover {
    background-color: var(--primary-blue-hover, #002c63) !important;
    border-color: var(--primary-blue-hover, #002c63) !important;
}

.btn-outline-primary {
    color: var(--primary-blue, #003c87) !important;
    border-color: var(--primary-blue, #003c87) !important;
}

.btn-outline-primary:hover {
    background-color: var(--primary-blue, #003c87) !important;
    color: #fff !important;
}

.btn-orange {
    background-color: var(--accent-orange, #fc7d07);
    color: #ffffff;
    border: none;
    transition: all 0.2s ease-in-out;
}

.btn-orange:hover {
    background-color: var(--accent-orange-hover, #e06c00);
    color: #ffffff;
    transform: translateY(-1px);
}

/* Search Group */
.search-group {
    background-color: #f1f5f9;
    border-radius: 10px;
}

.search-group input {
    background: transparent;
}

/* Custom Table */
.custom-table thead {
    background-color: #f8fafc;
    border-bottom: 2px solid var(--border-soft, #e2e8f0);
}

.custom-table th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.custom-table td {
    padding-top: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-soft, #e2e8f0);
}

.avatar-sm {
    width: 38px;
    height: 38px;
    font-size: 0.9rem;
}

/* Status Badges */
.badge-status {
    display: inline-flex;
    align-items: center;
    padding: 0.35em 0.75em;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 50rem;
}

.status-active {
    background-color: #dcfce7;
    color: #15803d;
}

.status-deactivated {
    background-color: #fee2e2;
    color: #b91c1c;
}

.status-verified {
    background-color: #e0f2fe;
    color: #0369a1;
}

.status-unverified {
    background-color: #fef3c7;
    color: #b45309;
}

/* Empty State */
.empty-state-icon {
    width: 72px;
    height: 72px;
    background-color: #f1f5f9;
}

/* Pagination */
.page-link {
    color: #64748b;
    border: 1px solid var(--border-soft, #e2e8f0);
    font-weight: 500;
}

.page-item.active .page-link {
    background-color: var(--primary-blue, #003c87);
    border-color: var(--primary-blue, #003c87);
    color: #fff;
}

/* Modal Backdrops & Document Cards */
.backdrop-blur {
    background-color: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
}

.doc-img {
    max-height: 140px;
    width: 100%;
    object-fit: cover;
    transition: transform 0.2s ease;
}

.doc-img:hover {
    transform: scale(1.02);
}
</style>
