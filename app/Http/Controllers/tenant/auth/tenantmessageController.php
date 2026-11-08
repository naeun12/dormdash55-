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
    // Ipakita ang messaging page sa tenant ug kuhaon ang iyang conversation history.
    public function tenantMessageIndex($tenant_id)
    {
        // Kuhaon ang tenant ID gikan sa active session.
        $sessionTenant_id = session('tenant_id');

        // Kuhaon ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $sessionTenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap pila ang wala pa mabasang notifications.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Kuhaon ang mga conversation nga gisugdan sa tenant.
        $conversations = conversationModel::where('initiatorID', $tenant_id)
            ->where('initiatorRole', 'tenant')
            ->get();

        // I-convert ang conversation records ngadto sa history format nga gamiton sa view.
        $history = $conversations->map(function ($convo) {

            // Kuhaon ang landlord ID gikan sa conversation topic.
            preg_match('/landlord\_([a-z0-9\\-]+)/', $convo->topic, $matches);
            $landlord_id = $matches[1] ?? null;

            // Pangitaon ang landlord information base sa nakuha nga ID.
            $landlord = $landlord_id ? landlordModel::find($landlord_id) : null;

            // Kuhaon ang pinakabag-o nga message sulod sa conversation.
            $lastMessage = messageModel::where('conversationID', $convo->id)
                ->latest('sentAt')
                ->first();

            // Ibalik ang impormasyon nga ipakita sa conversation history.
            return [
                'conversation_id'   => $convo->id,
                'receiver_name'     => $landlord ? $landlord->firstname . ' ' . $landlord->lastname : 'Unknown',
                'receiver_profile'  => $landlord->profilePicUrl ?? 'default-profile.png',
                'last_message'      => $lastMessage->message ?? '',
                'sent_at'           => $lastMessage->sentAt ?? null,
            ];
        })->sortByDesc('sent_at')->values();

        // I-load ang tenant messaging page uban sa conversation history ug notifications.
        return view('tenant.auth.tenantmessage', [
            'title'         => 'DormDash Message',
            'cssPath'       => '',
            'demo'          => '',
            'tenant_id'     => $tenant_id,
            'history'       => $history,
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    // Ipakita ang conversation tali sa tenant ug piniling landlord.
    public function landlordtenantmessageIndex(Request $request, $tenant_id)
    {
        // Kuhaon ang landlord ID nga gipadala gikan sa request.
        $landlord_id = $request->input('landlord_id');

        // Kuhaon ang pinakabag-o nga lima ka notifications sa tenant.
        $notifications = notificationModel::where('receiverID', $tenant_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang wala pa mabasang notifications.
        $unreadCount = notificationModel::where('receiverID', $tenant_id)
            ->where('isRead', false)
            ->count();

        // Mobalik sa previous page kung walay landlord ID nga nadawat.
        if (!$landlord_id) {
            return back()->with('error', 'Landlord ID is missing.');
        }

        // Pangitaon ang tenant ug landlord records.
        $tenant = tenantModel::find($tenant_id);
        $landlord = landlordModel::find($landlord_id);

        // Siguraduhon nga valid ang tenant ug landlord.
        if (!$tenant || !$landlord) {
            return back()->with('error', 'Invalid tenant or landlord.');
        }

        // Maghimo og unique topic para mailhan ang conversation sa duha ka users.
        $topic = "tenant\_{$tenant_id}\_landlord\_{$landlord_id}";

        // Susiha kung naa na bay existing conversation gamit ang topic.
        $conversation = conversationModel::where('topic', $topic)->first();

        // I-update ang last activity date sa conversation.
        conversationModel::where('topic', $topic)->update(['updated_at' => now()]);

        // Kung walay existing conversation, maghimo og bag-ong record.
        if (!$conversation) {
            $conversation = conversationModel::create([
                'topic' => $topic,
                'initiatorID' => $tenant_id,
                'initiatorRole' => 'tenant',
            ]);
        }

        // Kuhaon ang tanang messages sa conversation gikan sa pinakauna.
        $messages = messageModel::where('conversationID', $conversation->id)
            ->orderBy('sentAt', 'asc')
            ->get();

        // Ipakita ang messaging page uban sa landlord details ug message history.
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

    // Kuhaon ang listahan sa conversations sa tenant ug iyang mga landlord.
    public function getLandlordConversation($tenant_id)
    {
        // Pangitaon ang mga conversation nga adunay tenant ID sa ilang topic.
        $conversations = conversationModel::where('topic', 'like', '%tenant\_' . $tenant_id . '%')
            ->orderBy('updated_at', 'desc')
            ->get();

        // I-convert ang matag conversation ngadto sa data nga gamiton sa frontend.
        $history = $conversations->map(function ($convo) use ($tenant_id) {

            // Kuhaon ang landlord ID gikan sa conversation topic.
            preg_match('/landlord\_([a-z0-9\\-]+)/', $convo->topic, $matches);
            $landlord_id = $matches[1] ?? null;

            // Pangitaon ang landlord information.
            $landlord = $landlord_id ? landlordModel::find($landlord_id) : null;

            // Kuhaon ang pinakabag-o nga message sa conversation.
            $lastMessage = messageModel::where('conversationID', $convo->id)
                ->orderBy('sentAt', 'desc')
                ->first();

            // Susiha kung adunay unread messages nga nadawat sa tenant.
            $hasUnread = messageModel::where('conversationID', $convo->id)
                ->where('receiverID', $tenant_id)
                ->where('isRead', 0)
                ->exists();

            // Ibalik ang conversation details ug unread status.
            return [
                'conversation_id'   => $convo->id,
                'receiver_name'     => $landlord ? $landlord->firstname . ' ' . $landlord->lastname : 'Unknown Landlord',
                'receiver_profile'  => $landlord->profilePicUrl ?? 'default-profile.png',
                'last_message'      => $lastMessage->message ?? '(No messages yet)',
                'sent_at'           => $lastMessage->sentAt ?? null,
                'is_read'           => $hasUnread ? 0 : 1, // 0 = unread, 1 = read
            ];
        });

        // Ibalik ang conversation history isip JSON para magamit sa frontend.
        return response()->json($history);
    }

    // Kuhaon ang tanang messages sulod sa usa ka conversation.
    public function getLandlordMessages($conversation_id)
    {
        // Pangitaon ang messages base sa conversation ID ug ihan-ay gikan sa pinakauna.
        $messages = \App\Models\messageModel::where('conversationID', $conversation_id)
            ->orderBy('sentAt', 'asc')
            ->get();

        // Ibalik ang messages isip JSON response.
        return response()->json($messages);
    }

    // Pagpadala og message gikan sa tenant o landlord.
    public function sendLandlordMessage(Request $request)
    {
        // I-validate ang data nga nadawat gikan sa frontend.
        $validated = $request->validate([
            'conversationID' => 'required|exists:conversations,id',
            'message' => 'required|string',
            'senderID' => 'required|string',
            'senderRole' => 'required|in:tenant,landlord',
        ]);

        // Kuhaon ang conversation record base sa conversation ID.
        $conversation = \App\Models\conversationModel::find($validated['conversationID']);

        // Kuhaon ang tenant ID ug landlord ID gikan sa conversation topic.
        preg_match('/tenant\_([a-z0-9\\-]+)\_landlord\_([a-z0-9\\-]+)/', $conversation->topic, $matches);

        $tenantID = $matches[1] ?? null;
        $landlordID = $matches[2] ?? null;

        // I-determine kung kinsa ang receiver base sa role sa sender.
        $receiverID = $validated['senderRole'] === 'landlord' ? $tenantID : $landlordID;
        $receiverRole = $validated['senderRole'] === 'landlord' ? 'tenant' : 'landlord';

        // Siguraduhon nga nakuha ang valid receiver ID.
        if (!$receiverID) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve receiver ID from topic.'
            ], 422);
        }

        // Maghimo og bag-ong message record.
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

        // I-update ang activity date sa conversation human makapadala og message.
        conversationModel::where('id', $conversation->id)->update([
            'updated_at' => now()
        ]);

        // I-broadcast ang bag-ong message aron makita dayon sa laing user.
        broadcast(new MessageSent($message))->toOthers();

        // Kuhaon ang receiver account base sa iyang role.
        if ($receiverRole === 'tenant') {
            $receiver = \App\Models\tenant\tenantaccountModel::where('tenantID', $receiverID)->first();
            $senderName = 'A landlord';
        } else {
            $receiver = \App\Models\landlord\landlordModel::where('landlordID', $receiverID)->first();
            $senderName = 'A tenant';
        }

        // Maghimo og notification para mahibaw-an sa receiver nga adunay bag-ong message.
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

        // I-broadcast ang notification aron makita dayon sa receiver.
        broadcast(new \App\Events\NewNotificationEvent($notifications));

        // Magpadala og SMS ug email notification kung adunay receiver account.
        if ($receiver) {
            $notificationMessage = "DormDash: {$senderName} has sent you a new message. Please log in to your DormDash account to reply.";

            // Sulayi ang pagpadala og SMS gamit ang iProG SMS service.
            try {
                if (!empty($receiver->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $receiver->phoneNumber,
                        $notificationMessage
                    );
                }
            } catch (\Exception $smsError) {
                // I-save sa Laravel log ang error kung mapakyas ang SMS.
                Log::error('Landlord chat SMS failed: ' . $smsError->getMessage());
            }

            // Sulayi ang pagpadala og email notification.
            try {
                if (!empty($receiver->email)) {
                    Mail::raw($notificationMessage, function ($mail) use ($receiver) {
                        $mail->to($receiver->email)
                            ->subject('DormDash - New Message Notification');
                    });
                }
            } catch (\Exception $mailError) {
                // I-save sa Laravel log ang error kung mapakyas ang email.
                Log::error('Landlord chat email failed: ' . $mailError->getMessage());
            }
        }

        // Ibalik ang success response ug ang bag-ong message data.
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => $message
        ]);
    }

    // Markahan isip read ang mga messages nga nadawat sa naka-login nga tenant.
    public function markAsRead($id)
    {
        // Kuhaon ang tenant ID gikan sa active session.
        $tenant_id = session('tenant_id');

        // I-update ang unread messages sa conversation nga nadawat sa tenant.
        $updated = messageModel::where('conversationID', $id)
            ->where('isRead', 0)
            ->where('receiverID', $tenant_id)
            ->update(['isRead' => 1]);

        // Ibalik kung pila ka messages ang na-markahan isip read.
        return response()->json([
            'success' => true,
            'message' => "$updated messages marked as read."
        ]);
    }
}
