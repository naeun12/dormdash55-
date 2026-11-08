<?php

namespace App\Http\Controllers\landlord\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\messageModel;
use App\Models\conversationModel;
use App\Models\tenant\tenantModel;
use App\Events\MessageSent;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\landlord\landlordModel;
use App\Models\notificationModel;
class messagelandlordController extends Controller
{
   public function landlordmessageIndex($landlord_id)
{
    $sessionLandlordId = session('landlord_id');
     $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();
    $conversations = conversationModel::where('initiatorID', $landlord_id)
        ->where('initiatorRole', 'landlord')
        ->orderBy('updated_at','desc')
        ->get();
    $history = $conversations->map(function ($convo) {
        preg_match('/tenant_([a-z0-9\-]+)/', $convo->topic, $matches);
        $tenant_id = $matches[1] ?? null;

        $tenant = $tenant_id ? tenantModel::find($tenant_id) : null;

        $lastMessage = messageModel::where('conversationID', $convo->id)
            ->latest('sentAt')
            ->first();

        return [
            'conversation_id'   => $convo->id,
            'receiver_name'     => $tenant ? $tenant->firstname . ' ' . $tenant->lastname : 'Unknown',
            'receiver_profile'  => $tenant->profilePicUrl ?? 'default-profile.png',
            'last_message'      => $lastMessage->message ?? '',
            'sent_at'           => $lastMessage->sentAt ?? null,
        ];
    })->sortByDesc('sent_at')->values();

    return view('landlord.auth.messagingCenter', [
        'title'        => 'DormDash Message',
        'headerName'   => 'DormDash Message',
        'landlord_id'  => $landlord_id,
        'history'      => $history,
         'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
}
public function selecttenantToMessage(Request $request, $landlord_id)
{
    $tenant_id = $request->input('tenant_id');

    if (!$landlord_id || !$tenant_id) {
        return back()->with('error', 'Landlord or Tenant ID is missing.');
    }
    $notifications = notificationModel::where('receiverID', $landlord_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
             $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

    $landlord = landlordModel::find($landlord_id);
    $tenant = tenantModel::find($tenant_id);

    if (!$tenant || !$landlord) {
        return back()->with('error', 'Invalid tenant or landlord.');
    }
    $topic = "tenant_{$tenant_id}_landlord_{$landlord_id}";
    $conversation = conversationModel::firstOrCreate(
        ['topic' => $topic],
        ['initiatorID' => $landlord_id, 'initiatorRole' => 'landlord']
    );
            conversationModel::where('topic', $topic)->update(['updated_at' => now()]);


    $lastMessage = messageModel::where('conversationID', $conversation->id)
        ->latest('sentAt')
        ->first();

    $history = collect([
        [
            'conversation_id'   => $conversation->id,
            'receiver_name'     => $tenant->firstname . ' ' . $tenant->lastname,
            'receiver_profile'  => $tenant->profilePicUrl 
                                ? '/' . $tenant->profilePicUrl 
                                : '/default-profile.png',
            'last_message'      => $lastMessage->message ?? '(No messages yet)',
            'sent_at'           => $lastMessage->sentAt ?? null,
        ]
    ]);

    return view('landlord.auth.messagingCenter', [
        'title'        => 'DormDash Message',
        'headerName'   => 'DormDash Message',
        'landlord_id'  => $landlord_id,
        'history'      => $history,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
}

public function getConversations($landlord_id)
{
    $conversations = conversationModel::where('topic', 'like', '%landlord_' . $landlord_id . '%')->orderBy('updated_at', 'desc')->get();

    $history = $conversations->map(function ($convo) use ($landlord_id) {
        preg_match('/tenant_([a-z0-9\-]+)/', $convo->topic, $matches);
        $tenant_id = $matches[1] ?? null;
        $tenant = $tenant_id ? tenantModel::find($tenant_id) : null;

        $lastMessage = messageModel::where('conversationID', $convo->id)
            ->orderBy('sentAt', 'desc')
            ->first();

        $hasUnread = messageModel::where('conversationID', $convo->id)
            ->where('receiverID', $landlord_id)
            ->where('isRead', 0)
            ->exists();

        return [
            'conversation_id'   => $convo->id,
            'receiver_name'     => $tenant ? $tenant->firstname . ' ' . $tenant->lastname : 'Unknown Tenant',
            'receiver_profile'  => $tenant && $tenant->profilePicUrl
                                    ? asset('storage/' . $tenant->profilePicUrl)
                                    : asset('storage/uploads/profilePic/default-profile.png'),
            'last_message'      => $lastMessage->message ?? '(No messages yet)',
            'sent_at'           => $lastMessage->sentAt ?? null,
            'is_read'           => $hasUnread ? 0 : 1,
        ];
    });

    return response()->json($history);
}

public function getMessages($conversation_id)
{
    $messages = \App\Models\messageModel::where('conversationID', $conversation_id)
        ->orderBy('sentAt', 'asc')
        ->get();

    return response()->json($messages);
}
public function sendMessage(Request $request)
{
    $validated = $request->validate([
        'conversationID' => 'required|exists:conversations,id',
        'message' => 'required|string',
        'senderID' => 'required|string',
        'senderRole' => 'required|in:tenant,landlord',
        
    ]);
    $conversation = \App\Models\conversationModel::find($validated['conversationID']);
    preg_match('/tenant_([a-z0-9\-]+)_landlord_([a-z0-9\-]+)/', $conversation->topic, $matches);
    $tenantID = $matches[1] ?? null;
    $landlordID = $matches[2] ?? null;

    // Determine receiver ID
    $receiverID = $validated['senderRole'] === 'tenant' ? $landlordID : $tenantID;
    $receiverRole = $validated['senderRole'] === 'tenant' ? 'landlord' : 'tenant';

    // Validate resolved receiver
    if (!$receiverID) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to resolve receiver ID from topic.'
        ], 422);
    }
     conversationModel::where('id', $conversation->id)->update(['updated_at' => now()]);
    $message = new \App\Models\messageModel();
    $message->conversationID = $validated['conversationID'];
    $message->message = $validated['message'];
    $message->senderID = $validated['senderID'];
    $message->senderRole = $validated['senderRole'];
    $message->receiverID = $receiverID;
    $message->receiverRole = $receiverRole;
    $message->sentAt = now();
    $message->isRead = 0;
    $message->save();
    broadcast(new MessageSent($message))->toOthers();
    if ($receiverRole === 'landlord') {
        $receiver = \App\Models\landlord\landlordModel::where('landlordID', $receiverID)->first();
        $senderName = 'A tenant';
    } else {
        $receiver = \App\Models\tenant\tenantaccountModel::where('tenantID', $receiverID)->first();
        $senderName = 'A landlord';
    }
    $notifications = notificationModel::create([
    'senderID'     => $message->senderID,
    'senderType'   => 'tenant',
    'receiverID'   => $message->receiverID,
    'receiverType' => 'tenant',
    'title'        => 'Landlord Send a message',
    'message'      => "A landlord has sent you a message",
    'isRead'       => false,
    'readAt'       => null,
     ]);
    broadcast(new \App\Events\NewNotificationEvent($notifications));
    if ($receiver) {
            $notificationMessage = "DormDash: {$senderName} has sent you a new message. Please log in to your DormDash account to reply.";
             try {
            if (!empty($receiver->phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $receiver->phoneNumber,
                    $notificationMessage
                );
            }
        } catch (\Exception $smsError) {
            Log::error('Chat notification SMS failed: ' . $smsError->getMessage());
        }
        try {
            if (!empty($receiver->email)) {
                Mail::raw($notificationMessage, function ($mail) use ($receiver) {
                    $mail->to($receiver->email)
                        ->subject('DormDash - New Message Notification');
                });
            }
        } catch (\Exception $mailError) {
            Log::error('Chat notification email failed: ' . $mailError->getMessage());
        }
        }
    return response()->json([
        'success' => true,
        'message' => 'Message sent successfully.',
        'data' => $message
    ]);
}
public function markAsRead($id)
{
    $landlord_id = session('landlord_id');
    $updated = messageModel::where('conversationID', $id)
        ->where('isRead', 0)
        ->where('receiverID', $landlord_id)
        ->update(['isRead' => 1]);

    return response()->json([
        'success' => true,
        'message' => "$updated messages marked as read."
    ]);
}

}
