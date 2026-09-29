import "bootstrap";
import "bootstrap-icons/font/bootstrap-icons.css";

import Echo from "laravel-echo";
import Pusher from "pusher-js";
import "./bootstrap";
import { createApp } from "vue";
import TenantRegister from "./components/tenants/tenantRegister.vue";
import LandlordRegister from "./components/landlord/landlordregister.vue";
import LandlordLogin from "./components/landlord/landlordlogin.vue";
import LandlordDormManagement from "./components/landlord/auth/dormManagement.vue";
import landlordDashboard from "./components/landlord/auth/dashboard.vue";
import LandlordRoomManagement from "./components/landlord/auth/roomManagement.vue";
import tenantpage from "./components/landlord/auth/tenantpage.vue";
import BookingManagement from "./components/landlord/auth/bookingpage.vue";
import MessagingCenter from "./components/landlord/auth/messagingCenter.vue";
import ReviewandFeedback from "./components/landlord/auth/ReviewandFeedback.vue";
import notificationsLandlord from "./components/landlord/auth/notificationslandlord.vue";
import homepage from "./components/tenants/auth/homepage.vue";
import tenant from "./components/tenants/tenantLogin.vue";
import RoomDetails from "./components/tenants/auth/roomdetails.vue";
import roomSelection from "./components/tenants/auth/bookingProcess/roomSelection.vue";
import roomBook from "./components/tenants/auth/bookingProcess/bookRoom.vue";
import dormitoriesMap from "./components/tenants/auth/dormitoriesmap.vue";
import dormitories from "./components/tenants/auth/dormitories.vue";
import tenantmessage from "./components/tenants/auth/tenantmessage.vue";
import viewBooking from "./components/tenants/auth/nav/booking/mybooking.vue";
import viewBookingDetails from "./components/tenants/auth/nav/booking/mybookingdetails.vue";
import reservationPage from "./components/landlord/auth/reservationpage.vue";
import nextPayment from "./components/tenants/auth/nav/nextpayment/nextpaymentdue.vue";
import myRooms from "./components/tenants/auth/nav/rooms/myrooms.vue";
import myReservation from "./components/tenants/auth/nav/reservation/reservation.vue";
import paymentLandlord from "./components/landlord/auth/paymentlandlord.vue";
import reviewandrating from "./components/tenants/auth/reviewandrating.vue";
import notificationsTenant from "./components/tenants/auth/nav/notificationstenant.vue";
import landlordaccountUpdated from "./components/landlord/landlordupdate.vue";
import tenantupdateaccount from "./components/tenants/auth/tenantupdateaccount.vue";

//tenant

//admin
import adminlogin from "./components/admin/adminlogin.vue";
import AdminDashboard from "./components/admin/auth/admindashboard.vue";
import adminlandlordManagement from "./components/admin/auth/landlordmanagement.vue";
import admintenantManagement from "./components/admin/auth/tenantmanagement.vue";

import AOS from "aos";
import "aos/dist/aos.css";
AOS.init();

const tenantContainer = document.querySelector("#TenantRegisterContainer");
if (tenantContainer) {
    createApp(TenantRegister).mount("#TenantRegisterContainer");
}
const landlordContainer = document.querySelector("#LandlordRegisterContainer");
if (landlordContainer) {
    createApp(LandlordRegister).mount("#LandlordRegisterContainer");
}
const landlordContainerLogin = document.querySelector(
    "#landlordContainerLogin",
);
if (landlordContainerLogin) {
    createApp(LandlordLogin).mount("#landlordContainerLogin");
}
const dashboardController = document.querySelector("#dashboard");
if (dashboardController) {
    createApp(landlordDashboard).mount("#dashboard");
}
const landlorddormManagementContainer = document.querySelector(
    "#landlorddormManagement",
);
if (landlorddormManagementContainer) {
    createApp(LandlordDormManagement).mount("#landlorddormManagement");
}

const landlordroomManagementContainer = document.querySelector(
    "#landlordroomManagement",
);
if (landlordroomManagementContainer) {
    createApp(LandlordRoomManagement).mount("#landlordroomManagement");
}
const tenantpageContainer = document.querySelector("#tenantpage");
if (tenantpageContainer) {
    createApp(tenantpage).mount("#tenantpage");
}

const BookingManagementController =
    document.querySelector("#BookingManagement");
if (BookingManagementController) {
    createApp(BookingManagement).mount("#BookingManagement");
}

const MessagingCenterController = document.querySelector("#MessagingCenter");
if (MessagingCenterController) {
    createApp(MessagingCenter).mount("#MessagingCenter");
}
const ReviewandFeedbackController =
    document.querySelector("#ReviewandFeedback");
if (ReviewandFeedbackController) {
    createApp(ReviewandFeedback).mount("#ReviewandFeedback");
}
const NotificationController = document.querySelector("#Notification");
if (NotificationController) {
    createApp(Notification).mount("#Notification");
}
const homepageController = document.querySelector("#homepage");

if (homepageController) {
    createApp(homepage).mount("#homepage");
}
const tenantController = document.querySelector("#tenant");

if (tenantController) {
    createApp(tenant).mount("#tenant");
}
const RoomDetailsController = document.querySelector("#RoomDetails");

if (RoomDetailsController) {
    createApp(RoomDetails).mount("#RoomDetails");
}
const dormitoriesMapController = document.querySelector("#dormitoriesMap");

if (dormitoriesMapController) {
    createApp(dormitoriesMap).mount("#dormitoriesMap");
}
const dormitoriesController = document.querySelector("#dormitories");

if (dormitoriesController) {
    createApp(dormitories).mount("#dormitories");
}
const roomSelectionController = document.querySelector("#roomSelection");
if (roomSelectionController) {
    createApp(roomSelection).mount("#roomSelection");
}
const roomBookController = document.querySelector("#roomBook");

if (roomBookController) {
    createApp(roomBook).mount("#roomBook");
}
const tenantMessageController = document.querySelector("#tenantmessage");

if (tenantMessageController) {
    createApp(tenantmessage).mount("#tenantmessage");
}
const reservationController = document.querySelector("#reservationPage");

if (reservationController) {
    createApp(reservationPage).mount("#reservationPage");
}
const viewBookingController = document.querySelector("#viewBooking");

if (viewBookingController) {
    createApp(viewBooking).mount("#viewBooking");
}
const viewBookingDetailsController = document.querySelector(
    "#viewBookingDetails",
);

if (viewBookingDetailsController) {
    createApp(viewBookingDetails).mount("#viewBookingDetails");
}
const nextPaymentController = document.querySelector("#nextPayment");

if (nextPaymentController) {
    createApp(nextPayment).mount("#nextPayment");
}
const myRoomsController = document.querySelector("#myRooms");

if (myRoomsController) {
    createApp(myRooms).mount("#myRooms");
}
const myReservationController = document.querySelector("#myReservation");

if (myReservationController) {
    createApp(myReservation).mount("#myReservation");
}
const notificationsLandlordController = document.querySelector(
    "#notificationsLandlord",
);

if (notificationsLandlordController) {
    createApp(notificationsLandlord).mount("#notificationsLandlord");
}
const paymentlandlordController = document.querySelector("#paymentLandlord");

if (paymentlandlordController) {
    createApp(paymentLandlord).mount("#paymentLandlord");
}
const reviewandratingController = document.querySelector("#reviewandrating");

if (reviewandratingController) {
    createApp(reviewandrating).mount("#reviewandrating");
}
const notificationsTenantController = document.querySelector(
    "#notificationsTenant",
);

if (notificationsTenantController) {
    createApp(notificationsTenant).mount("#notificationsTenant");
}
const landlordaccountUpdatedController = document.querySelector(
    "#landlordaccountUpdated",
);
if (landlordaccountUpdatedController) {
    createApp(landlordaccountUpdated).mount("#landlordaccountUpdated");
}
//tenant
const tenantupdateaccountController = document.querySelector(
    "#tenantupdateaccount",
);
if (tenantupdateaccountController) {
    createApp(tenantupdateaccount).mount("#tenantupdateaccount");
}
//admin
const adminloginController = document.querySelector("#adminlogin");
if (adminloginController) {
    createApp(adminlogin).mount("#adminlogin");
}

const admindashboardController = document.querySelector("#admindashboard");
if (admindashboardController) {
    createApp(AdminDashboard).mount("#admindashboard");
}
const adminlandlordManagementController = document.querySelector(
    "#adminlandlordManagement",
);
if (adminlandlordManagementController) {
    createApp(adminlandlordManagement).mount("#adminlandlordManagement");
}
const admintenantManagementController = document.querySelector(
    "#admintenantManagement",
);
if (admintenantManagementController) {
    createApp(admintenantManagement).mount("#admintenantManagement");
}
