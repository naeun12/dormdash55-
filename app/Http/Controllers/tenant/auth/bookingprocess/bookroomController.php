<?php

namespace App\Http\Controllers\tenant\auth\bookingprocess;

use App\Http\Controllers\Controller;
use App\Models\landlord\roomModel;
use App\Models\landlord\bookingModel;
use App\Models\notificationModel;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class bookroomController extends Controller
{
    // Ipakita ang booking page ug kuhaa ang notifications sa tenant.
    public function bookRoomPage($roomId, $tenantID)
    {
        // Kuhaa ang tenant ID nga naka-store sa current session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaa ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications sa tenant.
        $unreadCount = notificationModel::where('receiverID', $tenantID)
            ->where('isRead', false)
            ->count();

        // I-load ang booking page uban sa room ID, tenant ID, ug notification data.
        return view('tenant.auth.bookingProcess.roomBooking', [
            'title' => 'Book Room',
            'cssPath' => '',
            'roomId' => $roomId,
            'tenant_id' => $tenantID,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Kuhaa ang impormasyon sa room base sa room ID ug ibalik isip JSON.
    public function getRoom($roomID)
    {
        $room = roomModel::where('roomID', $roomID)->first();

        // Ibalik ang resulta aron magamit sa frontend.
        return response()->json([
            'status' => 'success',
            'room' => $room,
        ]);
    }

    // Proseso sa pag-book sa room sa tenant.
    public function bookingaRoom(Request $request)
    {
        try {

            // I-validate ang tanang impormasyon nga gisubmit sa booking form.
            $validatedData = $request->validate([
                'room_id' => 'required|integer',
                'tenant_id' => 'required|string',
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'contact_number' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'age' => 'required|integer|min:15|max:60',
                'gender' => 'required|in:Male,Female',
                'moveInDate' => 'required|date',
                'moveOutDate' => 'required|date|after:moveInDate',
                'studentpicture_id' => 'required|string',
            ], [
                // Custom error messages para sa move-in ug move-out dates.
                'moveInDate.required' => 'Please select a move-in date.',
                'moveInDate.date' => 'Move-in date must be a valid date.',
                'moveOutDate.required' => 'Please select a move-out date.',
                'moveOutDate.date' => 'Move-out date must be a valid date.',
                'moveOutDate.after' => 'Move-out date must be after the move-in date.',
                'studentpicture_id.required' => 'Student picture ID is required.'
            ]);

            // Kuhaa ang room uban sa konektadong landlord ug dorm information.
            $room = roomModel::with(['landlord', 'dorm'])
                ->findOrFail($validatedData['room_id']);

            // Kuhaa ang landlord nga tag-iya sa gipiling room.
            $landlord = $room->landlord;

            // Hunongon ang proseso kung walay makita nga landlord information.
            if (!$landlord) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Landlord information not found.'
                ], 404);
            }

            // I-save ang booking information sa database.
            // Ang initial status kay pending kay kinahanglan pa og confirmation sa landlord.
            $book = bookingModel::create([
                'fkroomID' => $validatedData['room_id'],
                'fktenantID' => $validatedData['tenant_id'],
                'firstname' => $validatedData['firstname'],
                'lastname' => $validatedData['lastname'],
                'contactNumber' => $validatedData['contact_number'],
                'contactEmail' => $validatedData['email'],
                'age' => $validatedData['age'],
                'gender' => $validatedData['gender'],
                'moveInDate' => $validatedData['moveInDate'],
                'moveOutDate' => $validatedData['moveOutDate'],
                'pictureID' => $validatedData['studentpicture_id'],
                'status' => 'pending',
            ]);

            // Kuhaa ang dynamic information nga gamiton sa notification.
            $tenantName = trim($book->firstname . ' ' . $book->lastname);
            $landlordName = $landlord->firstname ?? 'Landlord';
            $roomNumber = $room->roomNumber ?? 'N/A';
            $dormName = $room->dorm->dormName ?? 'Dormitory';

            // Paghimo og notification para mahibaw-an sa landlord nga naay bag-ong booking.
            $notifications = notificationModel::create([
                'senderID' => $validatedData['tenant_id'],
                'senderType' => 'tenant',
                'receiverID' => $landlord->landlordID,
                'receiverType' => 'landlord',
                'title' => 'New Booking Request',
                'message' => "{$tenantName} has requested to book Room #{$roomNumber} at {$dormName}.",
                'isRead' => false,
                'readAt' => null,
            ]);

            // I-broadcast ang notification aron ma-notify dayon ang landlord pinaagi sa real-time event.
            broadcast(
                new \App\Events\NewNotificationEvent($notifications)
            );

            // Himoa ang mensahe nga gamiton sa SMS ug email notification.
            $notificationMessage = "DormDash: Hello {$landlordName}! "
                . "You have a new booking request from {$tenantName} "
                . "for Room {$roomNumber} at {$dormName}. "
                . "Please log in to DormDash to review the booking.";

            // Sulayi pagpadala og SMS notification gamit ang iProG SMS service.
            try {
                // Kuhaa ang contact number sa landlord.
                $landlordPhone = $landlord->phoneNumber ?? null;

                // Ipadala ang SMS kung adunay registered phone number ang landlord.
                if ($landlordPhone) {
                    app(IprogSMSService::class)->sendSMS(
                        $landlordPhone,
                        $notificationMessage
                    );

                    // I-record sa log kung malampuson ang pagpadala sa SMS.
                    Log::info('Booking SMS sent to landlord.', [
                        'bookingID' => $book->bookingID,
                        'landlordID' => $landlord->landlordID,
                    ]);
                } else {
                    // I-record sa log kung walay phone number ang landlord.
                    Log::warning('Landlord phone number is missing.', [
                        'landlordID' => $landlord->landlordID,
                    ]);
                }

            } catch (\Throwable $smsError) {
                // I-record ang error kung mapakyas ang SMS sending.
                // Dili niini hunongon ang tibuok booking process.
                Log::error('Landlord booking SMS failed.', [
                    'bookingID' => $book->bookingID,
                    'error' => $smsError->getMessage(),
                ]);
            }

            // Sulayi pagpadala og email notification ngadto sa landlord.
            try {
                // Ipadala ang email kung adunay email address ang landlord.
                if (!empty($landlord->email)) {
                    Mail::raw($notificationMessage, function ($mail) use ($landlord) {
                        $mail->to($landlord->email)
                            ->subject('DormDash - New Booking Request');
                    });

                    // I-record sa log kung malampuson ang pagpadala sa email.
                    Log::info('Booking email sent to landlord.', [
                        'bookingID' => $book->bookingID,
                        'landlordID' => $landlord->landlordID,
                    ]);
                }

            } catch (\Throwable $mailError) {
                // I-record ang error kung mapakyas ang email sending.
                // Magpadayon gihapon ang booking process bisan mapakyas ang email.
                Log::error('Landlord booking email failed.', [
                    'bookingID' => $book->bookingID,
                    'error' => $mailError->getMessage(),
                ]);
            }

            // Ibalik ang success response kung nahimo na ang booking.
            return response()->json([
                'status' => 'success',
                'message' => 'Room booked successfully. Waiting for landlord confirmation.',
                'data' => $book
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            // Ibalik ang validation errors kung naay invalid o kulang nga input.
            return response()->json([
                'status' => 'validation_error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            // I-record ang detalye sa error kung mapakyas ang booking creation.
            Log::error('Booking creation failed.', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Ibalik ang error response ngadto sa frontend.
            return response()->json([
                'status' => 'error',
                'message' => 'Booking failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
