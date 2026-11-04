<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\landingPageController;
use App\Http\Middleware\AfterRegistrationMiddleware;
//tenant
use App\Http\Controllers\tenant\auth\tenantupdateaccountController;
use App\Services\PhilSMSService;

use App\Http\Middleware\TenantAuth;
use App\Http\Controllers\tenant\tenantController;
use App\Http\Controllers\tenant\auth\homepageController;
use App\Http\Controllers\tenant\auth\dormdetailscontroller;
use App\Http\Controllers\landlord\auth\landlordaccountprocessController;
use App\Http\Controllers\landlord\auth\dashboardController;
use App\Http\Controllers\landlord\auth\dormpageController;
use App\Http\Controllers\landlord\auth\roompageController;
use App\Http\Controllers\landlord\auth\notificationslandlordController;
use App\Http\Controllers\landlord\auth\bookingpageController;
use App\Http\Controllers\landlord\auth\messagelandlordController;
use App\Http\Controllers\landlord\auth\NotificationController;
use App\Http\Controllers\landlord\auth\paymentlandlordController;
use App\Http\Controllers\landlord\auth\reservationController;
use App\Http\Controllers\landlord\auth\alltenantsController;
use App\Http\Controllers\tenant\auth\dormitoriesmapcontroller;
use App\Http\Controllers\tenant\auth\dormitories;
use App\Http\Controllers\tenant\auth\bookingprocess\selectionRoomController;
use App\Http\Controllers\tenant\auth\bookingprocess\bookroomController;
use App\Http\Controllers\tenant\auth\tenantmessageController;
use App\Http\Controllers\tenant\auth\nav\booking\mybookingController;
use App\Http\Controllers\tenant\auth\nav\booking\mybookingdetailsController;
use App\Http\Controllers\tenant\auth\nav\nextpayment\nextpaymentController;
use App\Http\Controllers\tenant\auth\nav\myroom\myroomsController;
use App\Http\Controllers\tenant\auth\nav\reservations\myreservationController;
use App\Http\Controllers\tenant\auth\reviewandfeedbackController;
use App\Http\Controllers\tenant\auth\nav\notificationsController;
use App\Http\Controllers\landlord\landlordupdateAccountController;
//admin 

use App\Http\Middleware\AdminAuth;
use App\Http\Controllers\admin\adminaccountController;
use App\Http\Controllers\admin\admindashboardController;
use App\Http\Controllers\admin\admintenantManagementController;
use App\Http\Controllers\admin\adminlandlordManagementController;
use App\Models\tenant\messageModel;
use App\Events\NewNotificationEvent;

use App\Models\Landlord;
use App\Models\notificationModel;
use App\Http\Middleware\LandlordAuth;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Support\Facades\Auth;
//view landingpage, login and register for landlord and tenant
Route::get('/', [landingPageController::class, 'landingPage'])->name('landingpage');
Route::get('/send-email', [landingPageController::class, 'sendEmail'])->name('send.email');
Route::get('/tenantLogin', [tenantController::class, 'login'])->name('login-tenant');
Route::get('/tenantRegister', [tenantController::class, 'register'])->name('register-tenant');
Route::get('/landlordLogin', [landlordaccountprocessController::class, 'landlordLogin'])->name('landlord-Login');
Route::post('/tenant-logout', [tenantController::class, 'logout'])->name('tenant.logout');
Route::post('/logout', [landlordaccountprocessController::class, 'logoutlandlord'])->name('logout');


Route::get('/landlordregister', [landlordaccountprocessController::class, 'landlordRegister'])->name('register-landlord');
//Route::get('/verify', [accountprocessController::class, 'startVerification'])->name('verify-otp');
Route::post('/verify-registration', [tenantController::class, 'verifyRegistration'])->name('verify.registration');
//creating tenants account

Route::match(['get', 'post'], '/SendOtp', [tenantController::class, 'SendOtp'])->name('SendOtp');
Route::match(['get', 'post'], '/resendOtp', [tenantController::class, 'resendOtp'])->name('resendOtp');
Route::match(['get', 'post'], '/registerTenant', [tenantController::class, 'registerTenant'])->name('registerTenant');
Route::match(['get', 'post'], 'tenant-login',[tenantController::class,'loginTenant'])->name('tenant-login');
//landlord login
Route::match(['get', 'post'], '/loginLandlord', [landlordaccountprocessController::class, 'loginLandlord'])->name('loginLandlord');



//landlord

Route::match(['get', 'post'], '/personalDetails', [landlordaccountprocessController::class, 'personalDetails'])->name('stepOne');
Route::post('/IdentityVerifaction', [landlordaccountprocessController::class, 'IdentityVerifaction'])->name('IdentityVerifaction');
Route::post('/businessPermitValidation', [landlordaccountprocessController::class, 'businessPermitValidation'])->name('businessPermitValidation');
Route::post('/RegisterLandlord', [landlordaccountprocessController::class, 'RegisterLandlord'])->name('RegisterLandlord');

//landlord auth

Route::middleware([LandlordAuth::class])->group(function () {
    Route::match(['get', 'post'], '/landlordDashboard/{landlordId}', [dashboardController::class, 'landlordDashboard'])->name('landlord.dashboard');
    Route::match(['get', 'post'], '/landlordDormManagement/{landlordId}', [dormpageController::class, 'DormManagement'])->name('landlord.dormanagement');
    Route::match(['get', 'post'], '/input-text', [dormpageController::class, 'inputFieldDorm'])->name('input-text');
    Route::match(['get', 'post'], '/upload-main-image', [dormpageController::class, 'uploadmainImage'])->name('upload-main-image');
    Route::match(['get', 'post'], '/upload-secondary-image', [dormpageController::class, 'uploadsecondaryImage'])->name('upload-secondary-image');
    Route::match(['get', 'post'], '/uplad-third-image', [dormpageController::class, 'uploadthirdImage'])->name('upload-third-image');
    Route::match(['get', 'post'], '/edit-main-image', [dormpageController::class, 'editmainImage'])->name('edit-main-image');
    Route::match(['get', 'post'], '/edit-secondary-image', [dormpageController::class, 'editsecondaryImage'])->name('edit-secondary-image');
    Route::match(['get', 'post'], '/edit-images/{id}', [dormpageController::class, 'imageUpdated'])->name('edit-images');
    Route::match(['get', 'post'], '/landlordRoomManagement/{landlordId}', [roompageController::class, 'RoomManagement'])->name('landlordRoomManagement');
    Route::match(['get', 'post'], '/booking-index/{landlord_id}', [bookingpageController::class, 'bookingpageIndex'])->name('booking.index');
    Route::match(['get', 'post'], '/reservation-index/{landlord_id}', [reservationController::class, 'reservationIndex'])->name('reservation.index');
    Route::match(['get', 'post'], '/all-tenants-index/{landlord_id}', [alltenantsController::class, 'alltenantIndex'])->name('all.tenants.index');
    Route::match(['get', 'post'], '/message/landlord/{landlord_id}', [messagelandlordController::class, 'landlordmessageIndex'])->name('message.landlord');
    Route::match(['get', 'post'], '/ReviewandFeedback', [ReviewandFeedbackController::class, 'ReviewandFeedback'])->name('ReviewandFeedback');
    // Route::match(['get', 'post'], '/NotificationPage', [NotificationController::class, 'NotificationPage'])->name('NotificationPage');

    //functions for getting data dashboard 
    Route::get('/get/landlord/{landlord_id}', [dashboardController::class, 'getLandlord']);
    Route::get('/get/total-tenants/{landlord_id}', [dashboardController::class, 'getTotalTenants']);
    Route::get('/get/available-beds/{landlord_id}', [dashboardController::class, 'availableBeds']);
    Route::get('/get/reservation-list/{landlord_id}', [dashboardController::class, 'getReservationList']);
    Route::get('/get/booking-list/{landlord_id}', [dashboardController::class, 'getBookingList']);
    Route::get('/get/dorm-id/{landlord_id}', [dashboardController::class, 'getDormID']);
    Route::get('/get/room-profits/{landlord_id}', [dashboardController::class, 'getroomProfits']);
    Route::get('/get/gender-distribution/{landlord_id}', [dashboardController::class, 'getgenderDistribution']);
    Route::get('/generate-full-report/{landlordID}', [dashboardController::class, 'generateFullReport']);



    //functions for landlord dorm management
    Route::get('/getlandlordVerifiedStatus', [dormpageController::class, 'getlandlordVerifiedStatus'])->name('getlandlordVerifiedStatus');
    Route::post('/AddDorm', [dormpageController::class, 'AddDorm'])->name('AddDorm');
    Route::post('/UpdateDorm/{id}', [dormpageController::class, 'UpdateDorm']);
    Route::delete('/DeleteDorm/{id}', [dormpageController::class, 'DeleteDorm'])->name('DeleteDorm');
    Route::get('/ListDorms', [dormpageController::class, 'ListDorms'])->name('ListDorms');
    Route::get('/view-dorm/{id}', [dormpageController::class, 'ViewDorm']);
    Route::get('/SearchDorms', [dormpageController::class, 'searchDorms'])->name('SearchDorms');
    Route::get('/filter-locations', [dormpageController::class, 'filterLocations'])->name('filter.locations');
    Route::get('/filter-availability', [dormpageController::class, 'filteredAvailability'])->name('filter.availability');
    //functions for landlord dorm rules and policy
    Route::post('/add-rules', [dormpageController::class, 'addRulesAndPolicy'])->name('add.rules');
    Route::delete('/delete-rules/{pivotId}', [dormpageController::class, 'deleteRulesAndPolicies'])->name('delete.rules');
     //functions for amenities
     Route::post('/add-amenities', [dormpageController::class, 'AddAmenities'])->name('add.amenities');
     Route::delete('/delete-amenities/{id}', [dormpageController::class, 'DeleteAmenities'])->name('delete.amenities');
    //functions for landlord room management
    Route::post('/addRoom', [roompageController::class, 'addRoom'])->name('addRoom');
    Route::post('/add-roomfeatures', [roompageController::class, 'addRoomFeatures'])->name('add.roomfeatures');
    Route::delete('/delete-roomfeatures/{pivotId}', [roompageController::class, 'deleteRoomFeatures'])->name('delete.roomfeatures');
    Route::post('/update-room/{id}', [roompageController::class, 'UpdateRoom'])->name('update.room');
    // Route::post('/upload-images',[imagesDormImages::class,'roomImages'])->name('upload-images');
    Route::delete('/DeleteRoom/{id}', [roompageController::class, 'DeleteRoom'])->name('DeleteRoom');
    Route::get('/ListRooms', [roompageController::class, 'ListRooms'])->name('ListRooms');
    Route::get('/get-rooms-by-dorm/{dormId}', [roompageController::class, 'getRoomsByDorm'])->name('get.rooms.by.dorm');
    Route::get('/get-rooms-by-gender/{gender}', [roompageController::class, 'getRoomsByGender'])->name('get.rooms.by.gender');
    Route::get('/get-rooms-by-availability/{availability}', [roompageController::class, 'getRoomsByAvailability'])->name('get.rooms.by.availability');
    Route::get('/get-rooms-by-room-type', [roompageController::class, 'getRoomsByRoomType'])->name('get.rooms.by.room.type');
    Route::get('/ViewRoom/{id}', [roompageController::class, 'ViewRoom'])->name('ViewRoom');
    Route::get('/SearchRooms', [roompageController::class, 'searchRooms'])->name('SearchRooms');
    Route::post('/rooms/allow-reserve/{id}', [roompageController::class, 'allowReserve'])
     ->name('rooms.allowReserve');

    //functions for booking approval
    Route::get('/booking-list', [bookingpageController::class, 'bookingList']);
    Route::get('/booking-tenant-view/{id}', [bookingpageController::class, 'ViewTenant']);
    Route::post('/handle-tenant-booking',[bookingpageController::class,'handletenantBooking']);
    Route::delete('/delete-booking/{id}',[bookingpageController::class,'deleteBooking']);
    Route::get('/search-booking', [bookingpageController::class, 'searchBooking']);
    Route::get('/api/dorms', [bookingpageController::class, 'getDormName']);
    Route::get('/api/dorms/{dormId}/tenants', [bookingpageController::class, 'getTenantsByDorm']);
    Route::get('/api/roomnumber/booking', [bookingpageController::class, 'getrooms']);
    Route::get('/api/applications/booking/', [bookingpageController::class, 'getapplications']);

    //functions for reservation 
    Route::get('/reservation-list', [reservationController::class, 'reservationList']);
    Route::get('/view-reservation/reservation/{id}', [reservationController::class, 'viewReservation']);
    Route::get('/search-reservation', [reservationController::class, 'searchReservation']);
    Route::delete('/delete-reservation/reservation/{id}', [reservationController::class, 'reservationDelete']);
    Route::get('/api/dorms/{dormId}/reservation', [reservationController::class, 'getTenantsByDorm']);
    Route::get('/api/roomnumber/reservation', [reservationController::class, 'getRoomsNumber']);
    Route::get('/get/allroomNumbers', [reservationController::class, 'getAllReservations']);
    Route::post('/accept-reservation', [reservationController::class, 'acceptReservation']);
    Route::get('/api/applications/reservation', [reservationController::class, 'getapplications']);
    //functions for all tenants
    Route::get('/tenants-list', [alltenantsController::class, 'tenantsList']);
    Route::get('/tenants-view/{id}', [alltenantsController::class, 'ViewTenant']);
    Route::put('/tenants-update/{id}', [alltenantsController::class, 'updateTenantInformation']);
    Route::get('get-dorms/{landlordId}', [alltenantsController::class, 'getDorms']);
    Route::get('get/roomtype',[alltenantsController::class,'getRoomTypes']);
    Route::get('get/rooms',[alltenantsController::class,'getRooms']);
    Route::get('get/roomsdetails',[alltenantsController::class,'getRoomsDetails']);
    Route::put('/tenant/room/update/{roomID}',[alltenantsController::class,'roomUpdate']);
    Route::get('/tenants/search', [alltenantsController::class, 'searchTenants']);
    Route::get('/tenants/filter-by-dorm', [alltenantsController::class, 'filterByDorm']);
    Route::post('/notify/tenant',[alltenantsController::class,'notifyTenant']);
    Route::get('/add/movein/tenant',[alltenantsController::class,'addMoveInTenant']);
    Route::get('/search/movein/tenant',[alltenantsController::class,'searchMoveInTenant']);
    Route::get('/movein/tenant',[alltenantsController::class,'moveInTenant']);
    Route::get('/view/tenant/payment/{tenantID}',[alltenantsController::class,'viewTenantPayment']);
    Route::post('/update/extension/{id}',[alltenantsController::class,'updateTenantExtension']);
    Route::post('/update/tenant/payment/extension/{id}',[alltenantsController::class,'updateTenantPaymentExtension']);
    Route::post('/soft-delete/tenant/{id}',[alltenantsController::class,'softDelete']);
    Route::get('/report/active-tenants', [alltenantsController::class, 'generateActiveTenantReport'])
    ->name('report.activeTenants');
    Route::get('/report/extension-payments', [alltenantsController::class, 'generateExtensionPaymentReport'])
    ->name('report.extensionPayments');

    //functions for messaging landlord
    Route::get('/api/landlord/conversations/{landlord_id}', [messagelandlordController::class, 'getConversations']);
    Route::get('/api/select/landlord/conversations/{landlord_id}', [messagelandlordController::class, 'selecttenantToMessage']);
    Route::get('/api/get/landlord/messages/{conversation_id}', [messagelandlordController::class, 'getMessages']);
    Route::post('/api/landlord/messages', [messagelandlordController::class, 'sendMessage']);
     Route::post('/api/landlord/markasread/{id}', [messagelandlordController::class, 'markAsRead']);

    //functions for notfications landlord
    Route::get('/notifications/lanlodrd/{landlord_id}', [notificationslandlordController::class, 'notificationsLandlord'])->name('notifications.landlord');
    Route::get('/get/notifications/landlord/{landlord_id}', [notificationslandlordController::class, 'getLandlordNotificationsList']);
    Route::post('/mark/read/landlord/{landlord_id}', [notificationslandlordController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [notificationslandlordController::class, 'markAllAsRead']);
    Route::post('/clear/notifications', [notificationslandlordController::class, 'clearAll']);
    //functions for payment landlord 
     Route::get('/paymentLandlord/{landlord_id}', [paymentlandlordController::class, 'paymentLandlordindex'])->name('payment.landlord');
    Route::post('/landlord/verify-payment', [paymentlandlordController::class, 'verifyPaymentLandlord'])->name('verify.payment.landlord');
    Route::get('/landlord/payment-success', [paymentlandlordController::class, 'paymentSuccess']);
    Route::get('/landlord/payment-cancel', [paymentlandlordController::class, 'paymentCancel']);
    Route::get('/get/landlord/data/{landlord_id}', [paymentlandlordController::class, 'getLandlordData']);
    //functions for landlord account update 
    Route::get('/landlord/account/update/{landlordId}', [landlordupdateAccountController::class, 'updateAccountIndex'])->name('landlord.account.update');
    Route::get('/get/landlord/data/{id}', [landlordupdateAccountController::class, 'getlandlordData']);
    Route::post('/update/landlord/data/{id}', [landlordupdateAccountController::class, 'updatelandlordAccount']);
    Route::post('/update/landlord/documents/{landlord_id}', [landlordupdateAccountController::class, 'updateDocuments']);
});
//tenant auth
//tenant account process
Route::middleware([TenantAuth::class])->group(function () {
    Route::get('/homepage/{tenant_id}', [homepageController::class, 'homepage'])->name('homepage');
    Route::get('/tenant/update/account/{tenant_id}', [tenantupdateaccountController::class, 'tenantaccountUpdate'])->name('tenant.update');
 
    Route::get('/room-details/{dormitory_id}/{tenant_id}', [dormdetailscontroller::class, 'dormDetailsIndex'])->name('room.details');
    Route::get('/room-selection/{dormitoryID}/{tenantID}', [selectionRoomController::class, 'SelectionRoom'])->name('room.selection');
    Route::get('/available-room/{dormitoryID}', [selectionRoomController::class, 'availableRooms'])->name('available.room');
    Route::get('/occupied-room/{dormitoryID}', [selectionRoomController::class, 'occupiedRooms'])->name('occupied.room');
    Route::get('/under-maintenace-room/{dormitoryID}', [selectionRoomController::class, 'maintenanceRooms'])->name('under.maintenace.room');
    Route::get('/filter-price-range/{dormitoryID}', [selectionRoomController::class, 'selectedPriceRange']);
    Route::get('/filter-gender/{dormitoryID}', [selectionRoomController::class, 'filterGender']);
    Route::post('/reserved-room', [selectionRoomController::class, 'reservation'])->name('reserved.room');
    Route::get('/view-room-details/{id}', [selectionRoomController::class, 'viewRoomDetails'])->name('view.room.details');
    Route::get('/booking-process/{roomId}/{tenantID}', [bookroomController::class, 'bookRoomPage']);
    Route::get('/get-room-details/{roomID}', [bookroomController::class, 'getRoom'])->name('get.room.details');
    Route::post('/book-room',[bookroomController::class,'bookingaRoom']);

    //homepage
    Route::get('/tenant/dorms/lapu-lapu', [homepageController::class, 'dormLapuLapu']);
    Route::get('/tenant/dorms/mandaue', [homepageController::class, 'dormMandaeu']);  
    Route::get('/api/top-rated-dorms', [homepageController::class, 'topRatedDorms']);  
   Route::get('gender-preference-dorms/{tenant_id}',[homepageController::class,'genderPreference']);
    Route::get('get/preferred-tenants',[homepageController::class,'getTenantPersonalized']);
    Route::post('update/submit-personalized',[homepageController::class,'updateSubmitPersonalized']);
    Route::get('get/room-and-dorm-personalized',[homepageController::class,'getRoomandDormPersonalized']);
    Route::post('/tenant-information', [dormdetailscontroller::class, 'tenantInformation'])->name('tenant.information');
    Route::post('/tenant-idPicture', [dormdetailscontroller::class, 'uploadTenantId'])->name('tenant.idPicture');
    Route::get('/dorms/{id}/review-stats', [dormdetailscontroller::class, 'reviewStats']);
    Route::get('/dorm-details', [dormdetailscontroller::class, 'ViewDorms'])->name('dorm.details');
    Route::get('/get/dorm/askai/{id}', [dormdetailscontroller::class, 'getdormAskAI']);
    Route::post('/send/ai', [dormdetailscontroller::class, 'askAI']);
    Route::get('/roomDetail/{room_id}',[dormdetailscontroller::class,'roomDetails']);
     Route::get('fillup/tenant',[dormdetailscontroller::class,'fillupTenant']);


    
    //map dormitories page
    Route::get('/dorm-map{tenant_id}', [dormitoriesmapcontroller::class, 'dormitoriesMap'])->name('dorm.map');
    Route::get('/nearby-dorms', [dormitoriesmapcontroller::class, 'getNearbyByCoordinates']);
    // dormitories page
    Route::get('/dormitories{tenant_id}', [dormitories::class, 'dormitoriesListing'])->name('dormitories');
    Route::get('/list-dorms', [dormitories::class, 'Listdorms']);
    Route::post('/search-locations', [dormitories::class, 'searchLocations']);
    Route::post('/pricerecommendations', [dormitories::class, 'priceRecommendations']);
    Route::post('/gender-recommendations', [dormitories::class, 'genderRecommendations']);
    Route::get('/select-cities',[dormitories::class,'selectedCityDorms']);
    Route::get('/fetch-amenities',[dormitories::class,'fetchAmenities']);
    Route::post('get/amenities',[dormitories::class,'getAmenities']);
    Route::get('get/rate',[dormitories::class,'getRate']);
    Route::get('get/sortByDate',[dormitories::class,'getSortByDate']);
    Route::get('get/availability',[dormitories::class,'getAvailability']);
    Route::post('/ai/question/reccomendations', [dormitories::class, 'getQuestionRecommendations']);
    Route::get('/most/watched/dorm/{id}', [dormitories::class, 'mostWatchedDorm']);
//review and rating page
    Route::get('/rating/reviews/{dormitory_id}/{tenant_id}', [reviewandfeedbackController::class, 'reviewandrating'])->name('review.rating');
    Route::get('/fetch/reviewsandrating/{dormitory_id}', [reviewandfeedbackController::class, 'fetchreviewandrating']);


    //tenant message
   // In your routes
    Route::get('/tenant-message-nav/{tenant_id}', [tenantmessageController::class, 'tenantmessageIndex'])->name('tenant.message');
    Route::get('/tenant-message/{tenant_id}', [tenantmessageController::class, 'landlordtenantmessageIndex'])->name('tenant.landlord.message');
    Route::get('/api/tenant/conversations/{tenant_id}', [tenantmessageController::class, 'getLandlordConversation']);
    Route::get('/api/get/tenant/messages/{conversation_id}', [tenantmessageController::class, 'getLandlordMessages']);
    Route::post('/api/tenant/messages', [tenantmessageController::class, 'sendLandlordMessage']);
    Route::post('/api/tenant/markasread/{id}', [tenantmessageController::class, 'markAsRead']);
    // view booking
    
    Route::get('/view/booking/{tenant_id}', [mybookingController::class, 'viewBooking'])->name('view.booking');
    Route::get('/tenant/my-bookings/{tenant_id}', [mybookingController::class, 'mybookingList']);
    Route::get('/view/booking/details/{tenant_id}/{booking_id}', [mybookingdetailsController::class, 'viewBookingDetails'])->name('view.booking.details');
    Route::get('/my-bookings/details/{booking_id}', [mybookingdetailsController::class, 'bookingDetails']);
    Route::post('/tenant/pay-room/', [mybookingdetailsController::class, 'payRoom']);
        Route::get('/cancel/booking/{bookingID}',[mybookingdetailsController::class,'cancelbooking']);

    // view next payment
    Route::get('/view/payment/{tenant_id}', [nextpaymentController::class, 'nextpaymentIndex'])->name('next.payment');
    Route::get('/tenant/payment/history/list/{tenant_id}', [nextpaymentController::class, 'paymentHistory']);
    Route::get('/api/total-amount/{tenantID}', [nextpaymentController::class, 'getTotalAmount']);
    

 // view My Rooms
    Route::get('/view/myrooms/{tenant_id}', [myroomsController::class, 'myroomsIndex'])->name('next.myrooms');
    Route::get('/tenant/room-list/{tenant_id}', [myroomsController::class, 'myroomsList']);
    Route::get('/tenant/{id}/receipt', [myroomsController::class, 'generateReceipt']);
    Route::post('/extend-rent', [myroomsController::class, 'extendRent']);
    Route::post('/reviewandrating', [myroomsController::class, 'ReviewAndRating']);
    Route::post('/send/issue/', [myroomsController::class, 'sendIssue']);
    Route::post('/update/rentstatus', [myroomsController::class, 'notifyrentUpdate']);
    //reservations view
    Route::get('/view/reservation/{tenant_id}', [myreservationController::class, 'viewReservation'])->name('view.reservation');
    Route::get('/tenant/my-reservation/{tenant_id}', [myreservationController::class, 'myReservationList']);
    Route::get('/tenant/my-reservation/details/{reservationID}', [myreservationController::class, 'reservationDetails']);
    Route::get('/tenant/cancel/reservation/{reservationID}', [myreservationController::class, 'cancelReservation']);
    Route::post('/tenant/pay/reservation/{reservationID}', [myreservationController::class, 'paymentReservation']);
// notifications view
    Route::get('/view/notifications/{tenant_id}', [notificationsController::class, 'notificationstenant'])->name('view.notifications.tenant');
     Route::get('/get/notifications/tenant/{tenant_id}', [notificationsController::class, 'gettenantotificationsList']);
    Route::post('/mark/read/tenant/{tenant_id}', [notificationsController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read/tenant', [notificationsController::class, 'markAllAsRead']);
    Route::post('/clear/notifications/tenant', [notificationsController::class, 'clearAll']);
// tenant update 
   Route::get('/get/tenant/data/{id}', [tenantupdateaccountController::class, 'getTenantData']);
    Route::post('/update/tenant/data/{id}', [tenantupdateaccountController::class, 'updatetenantAccount']);

});
Route::get('/login-admin', [adminaccountController::class, 'adminIndex'])->name('admin.login');
Route::post('/login-admin', [adminaccountController::class, 'adminLogin']);
Route::post('/admin/logout', [adminaccountController::class, 'logoutlandlord'])
    ->name('admin.logout');
Route::get('/admin/create', [adminaccountController::class, 'createAdmin'])->name('admin.create');
Route::middleware(['web', AdminAuth::class])->group(function () {
    Route::get('/admin/dashboard/{admin_id}', [admindashboardController::class, 'adminDashboardIndex'])
        ->name('admin.dashboard');
    Route::get('/admin/get/total',[admindashboardController::class,'getTotals']);
    Route::get('/admin/get-subscribers-total', [admindashboardController::class, 'getSubscribersTotal']);
    Route::get('/generate-subscription-report', [admindashboardController::class, 'generateSubscriptionReport'])
    ->name('generate.subscription.report');

    Route::get('/admin/tenantManagement/{admin_id}', [admintenantManagementController::class, 'admintenantManagemnentIndex'])
        ->name('admin.tenantmanagemnt');
    Route::get('/admin/get-tenants', [admintenantManagementController::class, 'getTenants']);
    Route::get('/admin/view-tenants/{id}', [admintenantManagementController::class, 'viewTenantProfile']);
    Route::get('/admin/deact-tenant/{id}', [admintenantManagementController::class, 'deactivatetenantAccount']);
    Route::get('/admin/active-tenant/{id}', [admintenantManagementController::class, 'reactivetenantAccount']);
    Route::post('/admin/tenant/send/email', [admintenantManagementController::class, 'sendTenantEmail']);
    Route::get('/generate-tenant-report', [admintenantManagementController::class, 'downloadTenantReport'])
    ->name('generate.tenant.report');
    Route::get('/admin/search-tenants', [admintenantManagementController::class, 'searchTenants']);


    Route::get('/admin/LandlordManangement/{admin_id}', [adminlandlordManagementController::class, 'landlordManagementIndex'])
        ->name('admin.landlordmanagement');
    Route::get('/admin/get-landlord', [adminlandlordManagementController::class, 'getLandlords']);
        Route::get('/admin/view-landlord/{id}', [adminlandlordManagementController::class, 'viewLandlordProfile']);
  Route::get('/admin/deact-landlord/{id}', [adminlandlordManagementController::class, 'deactivateLandlordAccount']);
    Route::get('/admin/active-landlord/{id}', [adminlandlordManagementController::class, 'reactivelandlordAccount']);
    Route::post('/admin/landlord/send/email', [adminlandlordManagementController::class, 'sendLandlordEmail']);
    Route::get('/generate-landlord-report', [adminlandlordManagementController::class, 'downloadLandlordReport'])
    ->name('generate.landlord.report');
    Route::get('/admin/search-landlords', [adminlandlordManagementController::class, 'searchLandlords']);

});

Route::get('/test-auth', function () {
    return auth('landlord')->check() ? 'Authenticated as landlord' : 'NOT authenticated';
});













