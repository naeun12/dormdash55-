<template>
    <transition name="fade">
        <div v-if="loading" class="modern-loader-overlay">
            <div class="loader-content">
                <div class="pulse-ring ring-1"></div>
                <div class="pulse-ring ring-2"></div>

                <div class="logo-wrapper">
                    <img :src="logoImage" alt="DormDash" class="brand-logo" />
                </div>

                <div class="progress-container">
                    <div class="progress-bar-ind"></div>
                </div>

                <span class="loading-text"
                    >DormDash is preparing your space...</span
                >
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "Loader",
    data() {
        return {
            loading: false, // Set to true for preview
            logoImage: "/images/Logo/Logo.png",
        };
    },
};
</script>

<style scoped src="../../../public/css/partials/loader.css"></style>
