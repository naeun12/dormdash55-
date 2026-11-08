<?php
namespace App\Http\Controllers\tenant\auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use App\Models\messageModel;
use App\Services\IprogSMSService;
use Illuminate\Support\Facades\Mail;
use App\Models\landlord\roomModel;
use App\Models\landlord\landlordModel;
use App\Models\tenant\tenantModel;
use App\Models\conversationModel;
use App\Models\notificationModel;




class tenantmessageController extends Controller
{
  public function tenantMessageIndex($tenant_id)
{
            $sessionTenant_id = session('tenant_id');


     $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();
    $conversations = conversationModel::where('initiatorID', $tenant_id)
        ->where('initiatorRole', 'tenant')
        ->get();


    $history = $conversations->map(function ($convo) {
        preg_match('/landlord_([a-z0-9\-]+)/', $convo->topic, $matches);
        $landlord_id = $matches[1] ?? null;


        $landlord = $landlord_id ? landlordModel::find($landlord_id) : null;


        $lastMessage = messageModel::where('conversationID', $convo->id)
            ->latest('sentAt')
            ->first();


        return [
            'conversation_id'   => $convo->id,
            'receiver_name'     => $landlord ? $landlord->firstname . ' ' . $landlord->lastname : 'Unknown',
            'receiver_profile'  => $landlord->profilePicUrl ?? 'default-profile.png',
            'last_message'      => $lastMessage->message ?? '',
            'sent_at'           => $lastMessage->sentAt ?? null,
           
        ];
    })->sortByDesc('sent_at')->values();


    return view('tenant.auth.tenantmessage', [
        'title'      => 'DormDash Message',
        'cssPath'    => '',
        'demo'       => '',
        'tenant_id'  => $tenant_id,
        'history'    => $history,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);
}
      public function landlordtenantmessageIndex(Request $request, $tenant_id)
    {
        $landlord_id = $request->input('landlord_id');
     $notifications = notificationModel::where('receiverID', $tenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();
        if (!$landlord_id) {
            return back()->with('error', 'Landlord ID is missing.');
        }
   
        $tenant = tenantModel::find($tenant_id);
        $landlord = landlordModel::find($landlord_id);
   
        if (!$tenant || !$landlord) {
            return back()->with('error', 'Invalid tenant or landlord.');
        }
   
        $topic = "tenant_{$tenant_id}_landlord_{$landlord_id}";
   
        $conversation = conversationModel::where('topic', $topic)->first();
        conversationModel::where('topic', $topic)->update(['updated_at' => now()]);
        if (!$conversation) {
            $conversation = conversationModel::create([
                'topic' => $topic,
                'initiatorID' => $tenant_id,
                'initiatorRole' => 'tenant',
            ]);
        }
   
        $messages = messageModel::where('conversationID', $conversation->id)
            ->orderBy('sentAt', 'asc')
            ->get();
   
        return view('tenant.auth.tenantmessage', [
            'title' => 'DormDash Message',
            'cssPath' => '',
            'landlord' => $landlord,
            'tenant_id' => $tenant_id,
            'messages' => $messages,
            'demo' => $landlord_id,
            'conversation_id' => $conversation->id,
            'notifications' => $notifications,
        'unread_count' => $unreadCount,
        ]);
    }
   
    public function getLandlordConversation($tenant_id)
    {
        $conversations = conversationModel::where('topic', 'like', '%tenant_' . $tenant_id . '%')->orderBy('updated_at', 'desc')->get();
        $history = $conversations->map(function ($convo) use ($tenant_id) {
        preg_match('/landlord_([a-z0-9\-]+)/', $convo->topic, $matches);
        $landlord_id = $matches[1] ?? null;
        $landlord = $landlord_id ? landlordModel::find($landlord_id) : null;


        $lastMessage = messageModel::where('conversationID', $convo->id)
            ->orderBy('sentAt', 'desc')
            ->first();
             $hasUnread = messageModel::where('conversationID', $convo->id)
            ->where('receiverID', $tenant_id)
            ->where('isRead', 0)
            ->exists();


        return [
            'conversation_id'   => $convo->id,
            'receiver_name'     => $landlord ? $landlord->firstname . ' ' . $landlord->lastname : 'Unknown Landlord',
            'receiver_profile'  => $landlord->profilePicUrl ?? 'default-profile.png',
            'last_message'      => $lastMessage->message ?? '(No messages yet)',
            'sent_at'           => $lastMessage->sentAt ?? null,
            'is_read'           => $hasUnread ? 0 : 1, // 0 = unread, 1 = read
        ];
    });
    return response()->json($history);
}
    public function getLandlordMessages($conversation_id)
{
    $messages = \App\Models\messageModel::where('conversationID', $conversation_id)
        ->orderBy('sentAt', 'asc')
        ->get();


    return response()->json($messages);
    }
   
public function sendLandlordMessage(Request $request)
{
    $validated = $request->validate([
        'conversationID' => 'required|exists:conversations,id',
        'message' => 'required|string',
        'senderID' => 'required|string',
        'senderRole' => 'required|in:tenant,landlord',
    ]);


    // Get the conversation
    $conversation = \App\Models\conversationModel::find($validated['conversationID']);


    // Extract tenant and landlord IDs from the topic
    preg_match('/tenant_([a-z0-9\-]+)_landlord_([a-z0-9\-]+)/', $conversation->topic, $matches);


    $tenantID = $matches[1] ?? null;
    $landlordID = $matches[2] ?? null;


    // Determine receiver ID
    $receiverID = $validated['senderRole'] === 'landlord' ? $tenantID : $landlordID;
    $receiverRole = $validated['senderRole'] === 'landlord' ? 'tenant' : 'landlord';


    // Validate resolved receiver
    if (!$receiverID) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to resolve receiver ID from topic.'
        ], 422);
    }


    // Create message
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


    conversationModel::where('id', $conversation->id)->update([
        'updated_at' => now()
    ]);


    broadcast(new MessageSent($message))->toOthers();


    // Get receiver account
    if ($receiverRole === 'tenant') {
        $receiver = \App\Models\tenant\tenantaccountModel::where('tenantID', $receiverID)->first();
        $senderName = 'A landlord';
    } else {
        $receiver = \App\Models\landlord\landlordModel::where('landlordID', $receiverID)->first();
        $senderName = 'A tenant';
    }


    // Create notification
    $notifications = notificationModel::create([
        'senderID' => $message->senderID,
        'senderType' => $validated['senderRole'],
        'receiverID' => $message->receiverID,
        'receiverType' => $receiverRole,
        'title' => 'New Message',
        'message' => "{$senderName} has sent you a message.",
        'isRead' => false,
        'readAt' => null,
    ]);


    broadcast(new \App\Events\NewNotificationEvent($notifications));


    // Send SMS and email notification
    if ($receiver) {
        $notificationMessage = "DormDash: {$senderName} has sent you a new message. Please log in to your DormDash account to reply.";


        // Send SMS using iProG SMS
        try {
            if (!empty($receiver->phoneNumber)) {
                app(IprogSMSService::class)->sendSMS(
                    $receiver->phoneNumber,
                    $notificationMessage
                );
            }
        } catch (\Exception $smsError) {
            Log::error('Landlord chat SMS failed: ' . $smsError->getMessage());
        }


        // Send email notification
        try {
            if (!empty($receiver->email)) {
                Mail::raw($notificationMessage, function ($mail) use ($receiver) {
                    $mail->to($receiver->email)
                        ->subject('DormDash - New Message Notification');
                });
            }
        } catch (\Exception $mailError) {
            Log::error('Landlord chat email failed: ' . $mailError->getMessage());
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
    $tenant_id = session('tenant_id');
    $updated = messageModel::where('conversationID', $id)
        ->where('isRead', 0)
        ->where('receiverID', $tenant_id)
        ->update(['isRead' => 1]);


    return response()->json([
        'success' => true,
        'message' => "$updated messages marked as read."
    ]);
}
}



