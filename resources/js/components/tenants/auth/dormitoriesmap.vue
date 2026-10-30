<template>
    <Loader ref="loader" />
    <Toastcomponents ref="toast" />
    <NotificationList ref="toastRef" />

    <div class="map-instruction-wrapper">
        <div class="text-center mb-5 animate__animated animate__fadeIn">
            <h2 class="fw-bold text-dash-blue display-6">
                Explore <span class="text-dash-orange">Surigao City</span>
            </h2>
            <p class="text-muted mx-auto" style="max-width: 600px">
                Discover the best places to stay near your desired location.
                Precision helps us find your perfect match.
            </p>
        </div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div
                        class="card instruction-card border-0 shadow-sm rounded-4 overflow-hidden"
                    >
                        <div class="row g-0 align-items-center">
                            <div
                                class="col-sm-3 bg-dash-blue-soft d-flex align-items-center justify-content-center p-4"
                            >
                                <div class="logo-pulse-wrapper">
                                    <img
                                        :src="logoImage"
                                        alt="Pin Logo"
                                        class="floating-pin shadow-sm"
                                    />
                                    <div class="pulse-ring"></div>
                                </div>
                            </div>

                            <div class="col-sm-9">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <div
                                            class="step-number text-dash-orange fw-bold"
                                        >
                                            01
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-1">
                                                Set Your Location
                                            </h5>
                                            <p
                                                class="text-muted mb-0 lh-sm small"
                                            >
                                                <strong
                                                    >Drag and drop the
                                                    pin</strong
                                                >
                                                on the map to mark where you
                                                want to stay. This helps us
                                                calculate the distance to nearby
                                                dormitories accurately.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 ps-5">
                                        <span
                                            class="badge rounded-pill bg-light text-dash-blue border py-2 px-3"
                                        >
                                            <i
                                                class="bi bi-info-circle me-1"
                                            ></i>
                                            Pro-tip: Drop it near your school or
                                            workplace!
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Two-column layout -->
    <div class="map-dashboard-container m-4">
        <div class="row g-4">
            <div class="col-xl-8 col-lg-7">
                <div
                    class="map-wrapper shadow-lg border-0 rounded-5 overflow-hidden"
                >
                    <div id="map" class="main-map-frame"></div>
                    <div
                        class="map-overlay-badge animate__animated animate__fadeIn"
                    >
                        <i class="bi bi-geo-fill text-dash-orange"></i>
                        <span>Surigao City Interactive View</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div
                    class="info-sidebar bg-white border-0 shadow-lg rounded-5 d-flex flex-column overflow-hidden"
                >
                    <div
                        class="sidebar-header p-4 border-bottom bg-light-subtle rounded-top-5"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div class="brand-circle shadow-sm">
                                <img
                                    :src="logoImage"
                                    alt="DormDash"
                                    class="img-fluid p-2"
                                />
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dash-blue">
                                    Nearby Stays
                                </h5>
                                <small class="text-muted"
                                    >Based on your pinned location</small
                                >
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-scrollable p-3">
                        <div
                            v-if="nearbyDorms.length === 0"
                            class="empty-state text-center py-5"
                        >
                            <div class="empty-icon mb-3">📍</div>
                            <p class="text-muted fw-medium small">
                                No dormitories found nearby.<br />Try dragging
                                the pin to a new area.
                            </p>
                        </div>

                        <div
                            v-else
                            class="dorm-list-stack d-flex flex-column gap-3"
                        >
                            <div
                                v-for="dorm in nearbyDorms"
                                :key="dorm.id"
                                class="sidebar-card rounded-4 p-3 shadow-sm"
                                @click="viewDormsDetails(dorm.dormID)"
                            >
                                <div class="d-flex gap-3">
                                    <div
                                        class="thumb-container position-relative"
                                    >
                                        <img
                                            :src="
                                                dorm.images?.mainImage ||
                                                dorm.mainImage ||
                                                '/images/default-dorm.webp'
                                            "
                                            class="rounded-3 shadow-sm"
                                        />
                                        <div class="distance-tag">
                                            {{
                                                parseFloat(
                                                    dorm.distance_km,
                                                ).toFixed(1)
                                            }}
                                            km
                                        </div>
                                    </div>

                                    <div class="flex-grow-1 min-w-0">
                                        <h6
                                            class="fw-bold text-dark text-truncate mb-1"
                                        >
                                            {{ dorm.dormName }}
                                        </h6>
                                        <div
                                            class="d-flex align-items-center gap-1 text-muted mb-2"
                                        >
                                            <i
                                                class="bi bi-geo-alt-fill x-small text-dash-orange"
                                            ></i>
                                            <span
                                                class="x-small text-truncate"
                                                >{{ dorm.address }}</span
                                            >
                                        </div>

                                        <div
                                            class="d-flex justify-content-between align-items-center mt-2"
                                        >
                                            <span
                                                v-if="isPrice"
                                                class="fw-bold text-dash-blue"
                                            >
                                                ₱{{
                                                    dorm.price
                                                        ? dorm.price.toLocaleString()
                                                        : "0"
                                                }}
                                            </span>
                                            <button
                                                class="btn btn-dash-outline-sm rounded-pill"
                                            >
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="sidebar-footer p-3 text-center border-top bg-white rounded-bottom-5"
                    >
                        <span
                            class="badge rounded-pill text-dark bg-light border px-3 py-2"
                        >
                            {{ nearbyDorms.length }} Dorms available in this
                            area
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";
import Toastcomponents from "@/components/Toastcomponents.vue";
import Loader from "@/components/loader.vue";
import NotificationList from "@/components/notifications.vue";

export default {
    components: {
        Toastcomponents,
        Loader,
        NotificationList,
    },
    data() {
        return {
            logoImage:
                "https://capstonedormhubdeploy1-production.up.railway.app/images/Logo/logo.png",
            map: null,
            draggableMarker: null,
            nearbyDorms: [],
            markers: [],
            allDorms: "",
            selectedPriceRange: "",
            selectedGenderType: "",
            isPrice: false,
            notifications: [],
            receiverID: "",
            tenant_id: "",
        };
    },
    name: "DormMap",
    mounted() {
        // Load Google Maps script dynamically
        if (!window.google || !window.google.maps) {
            const script = document.createElement("script");
            script.src =
                "https://maps.googleapis.com/maps/api/js?key=AIzaSyCbVSKsv35IGFWYg9C96B5swf6UaVj9IGQ&callback=initMap";
            script.async = true;
            window.initMap = () => this.initMap(); // 👈 Fix here

            document.head.appendChild(script);
        } else {
            this.initMap();
        }
        this.tenant_id = window.tenant_id;

        this.subscribeToNotifications();
    },
    methods: {
        subscribeToNotifications() {
            if (this.hasSubscribed) return;
            this.hasSubscribed = true;

            this.receiverID = this.tenant_id;
            Echo.private(`notifications.${this.tenant_id}`)
                .subscribed(() => {
                    console.log("✔ Subscribed!");
                })
                .listen(".NewNotificationEvent", (e) => {
                    this.notifications.unshift(e); // save for list
                    this.$refs.toastRef.pushNotification({
                        title: e.title || "New Notification",
                        message: e.message,
                        color: "success",
                    });
                });
        },

        viewDormsDetails(dormitoryId) {
            this.tenant_id = window.tenant_id;
            window.location.href = `/room-details/${dormitoryId}/${this.tenant_id}`;
        },

        initMap() {
            const surigaoCity = { lat: 9.784, lng: 125.4888 }; // Surigao City center

            const centerBetween = {
                lat: surigaoCity.lat,
                lng: surigaoCity.lng,
            };

            this.map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: centerBetween,
                draggable: true,
                zoomControl: true,
                scrollwheel: true,
                disableDoubleClickZoom: false,
            });
            this.fetchNearbyDorms(centerBetween.lat, centerBetween.lng);
            this.draggableMarker = new google.maps.Marker({
                position: centerBetween,
                map: this.map,
                draggable: true,
                animation: google.maps.Animation.DROP,
                title: "Drag me!",
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png",
                    scaledSize: new google.maps.Size(50, 50),
                },
            });

            this.draggableMarker.addListener("dragstart", () => {
                this.draggableMarker.setAnimation(google.maps.Animation.BOUNCE);
            });

            this.draggableMarker.addListener("dragend", (e) => {
                const lat = e.latLng.lat();
                const lng = e.latLng.lng();
                this.fetchNearbyDorms(lat, lng);
            });
        },
        async fetchNearbyDorms(lat, lng) {
            try {
                this.$refs.loader.loading = true;
                const response = await axios.get(`nearby-dorms`, {
                    params: { lat, lng },
                });
                this.clearMarkers();

                if (response.data.status === "success") {
                    this.allDorms = response.data.data;
                    this.selectedPriceRange = "";
                    this.selectedGenderType = "";
                    this.isPrice = false;
                    this.nearbyDorms = this.allDorms.filter(
                        (dorm) => parseFloat(dorm.distance_km) <= 2,
                    );

                    const bounds = this.map.getBounds(); // Get current visible map area
                    this.nearbyDorms.forEach((dorm) => {
                        const lat = parseFloat(dorm.latitude);
                        const lng = parseFloat(dorm.longitude);
                        const km = parseFloat(dorm.distance_km);
                        if (km > 2) return;

                        const latLng = new google.maps.LatLng(lat, lng);
                        if (bounds && !bounds.contains(latLng)) return;

                        // Check if the dorm is within the map bounds
                        const marker = new google.maps.Marker({
                            position: { lat, lng },
                            map: this.map,
                            icon: {
                                url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png", // Default red pin
                                scaledSize: new google.maps.Size(40, 40),
                            },
                            title: `${dorm.dormName} (${dorm.distance_km} km)`,
                        });

                        const infoWindow = new google.maps.InfoWindow({
                            content: `
                            <div style="max-width: 250px;">
                                <h6>${dorm.dormName}</h6>
                                <p style="margin:0;">${dorm.address}</p>
                                <small><b>Distance:</b> ${dorm.distance_km} km</small>
                            </div>`,
                        });

                        marker.addListener("click", () => {
                            infoWindow.open(this.map, marker);
                        });

                        this.markers.push(marker);
                    });
                    this.$refs.loader.loading = false;
                }
            } catch (error) {
                console.error("Error fetching dorms:", error);
                this.nearbyDorms = [];
                this.allDorms = null;
                this.clearMarkers();
                this.$refs.loader.loading = false;
            }
        },
        clearMarkers() {
            if (this.markers && this.markers.length > 0) {
                this.markers.forEach((marker) => {
                    if (marker && typeof marker.setMap === "function") {
                        marker.setMap(null); // removes marker from map
                    }
                });
            }
            this.markers = []; // Clear tracking
        },

        onDragStart(event) {
            this.offsetX = event.offsetX;
            this.offsetY = event.offsetY;
        },
        onDragEnd(event) {
            const containerRect = event.target
                .closest(".row")
                .getBoundingClientRect();
            this.pinX = event.pageX - containerRect.left - this.offsetX;
            this.pinY = event.pageY - containerRect.top - this.offsetY;
        },
    },
};
</script>
<style scoped src="../../../../css/tenant/dormitorymap.css"></style>
