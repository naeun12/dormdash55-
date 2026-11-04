<?php

namespace App\Http\Controllers\tenant\auth\bookingprocess;

use App\Http\Controllers\Controller;
use App\Models\landlord\roomModel;
use App\Models\landlord\bookingModel;
use App\Models\notificationModel;
use App\Services\PhilSMSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class bookroomController extends Controller
{
    public function bookRoomPage($roomId,$tenantID)
    {
                    $sessionTenant_id = session('tenant_id');

         $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $tenantID)
            ->where('isRead', false)
            ->count();
        return view('tenant.auth.bookingProcess.roomBooking',['title'=>'Book Room','cssPath'=>'','roomId' =>$roomId,'tenant_id'=>$tenantID,'notifications' => $notifications,
             'unread_count' => $unreadCount,]);
    }
    public function getRoom($roomID)
    {
        $room = roomModel::where('roomID',$roomID)->first();
        return response()->json([
            'status' => 'success',
            'room' => $room,
        ]);
    }
    
public function bookingaRoom(Request $request)
{
    try {

        $validatedData = $request->validate([
            'room_id'           => 'required|integer',
            'tenant_id'         => 'required|string',
            'firstname'         => 'required|string|max:255',
            'lastname'          => 'required|string|max:255',
            'contact_number'    => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'age'               => 'required|integer|min:15|max:60',
            'gender'            => 'required|in:Male,Female',
            'moveInDate'        => 'required|date',
            'moveOutDate'       => 'required|date|after:moveInDate',
            'studentpicture_id' => 'required|string',
        ], [
            'moveInDate.required' => 'Please select a move-in date.',
            'moveInDate.date' => 'Move-in date must be a valid date.',
            'moveOutDate.required' => 'Please select a move-out date.',
            'moveOutDate.date' => 'Move-out date must be a valid date.',
            'moveOutDate.after' => 'Move-out date must be after the move-in date.',
            'studentpicture_id.required' => 'Student picture ID is required.'
        ]);


        // Get room and landlord information
        $room = roomModel::with(['landlord', 'dorm'])
            ->findOrFail($validatedData['room_id']);

        $landlord = $room->landlord;

        if (!$landlord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Landlord information not found.'
            ], 404);
        }


        // Create booking
        $book = bookingModel::create([
            'fkroomID'      => $validatedData['room_id'],
            'fktenantID'    => $validatedData['tenant_id'],
            'firstname'     => $validatedData['firstname'],
            'lastname'      => $validatedData['lastname'],
            'contactNumber' => $validatedData['contact_number'],
            'contactEmail'  => $validatedData['email'],
            'age'           => $validatedData['age'],
            'gender'        => $validatedData['gender'],
            'moveInDate'    => $validatedData['moveInDate'],
            'moveOutDate'   => $validatedData['moveOutDate'],
            'pictureID'     => $validatedData['studentpicture_id'],
            'status'        => 'pending',
        ]);


        // Dynamic booking information
        $tenantName = trim(
            $book->firstname . ' ' . $book->lastname
        );

        $landlordName = $landlord->firstname ?? 'Landlord';
        $roomNumber = $room->roomNumber ?? 'N/A';
        $dormName = $room->dorm->dormName ?? 'Dormitory';


        // Create landlord notification
        $notifications = notificationModel::create([
            'senderID'     => $validatedData['tenant_id'],
            'senderType'   => 'tenant',
            'receiverID'   => $landlord->landlordID,
            'receiverType' => 'landlord',
            'title'        => 'New Booking Request',
            'message'      => "{$tenantName} has requested to book Room #{$roomNumber} at {$dormName}.",
            'isRead'       => false,
            'readAt'       => null,
        ]);
        broadcast(
            new \App\Events\NewNotificationEvent($notifications)
        );
        try {

            $landlordPhone = $landlord->contactNumber    ?? null;

            if ($landlordPhone) {

                $smsMessage = "DormDash: Hello {$landlordName}! "
                    . "You have a new booking request from {$tenantName} "
                    . "for Room {$roomNumber} at {$dormName}. "
                    . "Please log in to DormDash to review the booking.";

                app(PhilSMSService::class)->sendSMS(
                    $landlordPhone,
                    $smsMessage
                );

                Log::info('Booking SMS sent to landlord.', [
                    'bookingID' => $book->bookingID,
                    'landlordID' => $landlord->landlordID,
                ]);

            } else {

                Log::warning('Landlord phone number is missing.', [
                    'landlordID' => $landlord->landlordID,
                ]);

            }

        } catch (\Throwable $smsError) {
            Log::error('Landlord booking SMS failed.', [
                'bookingID' => $book->bookingID,
                'error' => $smsError->getMessage(),
            ]);

        }


        return response()->json([
            'status' => 'success',
            'message' => 'Room booked successfully. Waiting for landlord confirmation.',
            'data' => $book
        ]);


    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'status' => 'validation_error',
            'errors' => $e->errors(),
        ], 422);


    } catch (\Throwable $e) {

        Log::error('Booking creation failed.', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Booking failed.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
