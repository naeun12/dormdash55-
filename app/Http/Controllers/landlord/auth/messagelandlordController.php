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
    // Ipakita ang messaging center sa landlord ug kuhaa ang iyang conversation history.
    public function landlordmessageIndex($landlord_id)
    {
        // Kuhaa ang landlord ID nga naka-save sa current session.
        $sessionLandlordId = session('landlord_id');

        // Kuhaa ang pinakabag-o nga lima ka notifications para sa landlord.
        $notifications = notificationModel::where('receiverID', $sessionLandlordId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // Kuhaa ang tanang conversations nga gisugdan sa landlord.
        $conversations = conversationModel::where('initiatorID', $landlord_id)
            ->where('initiatorRole', 'landlord')
            ->orderBy('updated_at', 'desc')
            ->get();

        // I-convert ang conversation records ngadto sa history format nga gamiton sa frontend.
        $history = $conversations->map(function ($convo) {

            // Kuhaa ang tenant ID gikan sa conversation topic gamit ang regular expression.
            preg_match('/tenant\_([a-z0-9\\-]+)/', $convo->topic, $matches);
            $tenant_id = $matches[1] ?? null;

            // Pangitaa ang tenant information base sa nakuha nga tenant ID.
            $tenant = $tenant_id ? tenantModel::find($tenant_id) : null;

            // Kuhaa ang pinakabag-o nga message sulod sa conversation.
            $lastMessage = messageModel::where('conversationID', $convo->id)
                ->latest('sentAt')
                ->first();

            // Ibalik ang conversation details nga ipakita sa messaging history.
            return [
                'conversation_id'   => $convo->id,
                'receiver_name'     => $tenant ? $tenant->firstname . ' ' . $tenant->lastname : 'Unknown',
                'receiver_profile'  => $tenant->profilePicUrl ?? 'default-profile.png',
                'last_message'      => $lastMessage->message ?? '',
                'sent_at'           => $lastMessage->sentAt ?? null,
            ];
        })->sortByDesc('sent_at')->values();

        // I-load ang messaging center view uban sa conversations ug notifications.
        return view('landlord.auth.messagingCenter', [
            'title'        => 'DormDash Message',
            'headerName'   => 'DormDash Message',
            'landlord_id'  => $landlord_id,
            'history'      => $history,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    // Paghimo o pagpili og conversation tali sa landlord ug tenant.
    public function selecttenantToMessage(Request $request, $landlord_id)
    {
        // Kuhaa ang tenant ID nga gipili sa landlord sa frontend.
        $tenant_id = $request->input('tenant_id');

        // Susiha kung naa bay landlord ID ug tenant ID.
        if (!$landlord_id || !$tenant_id) {
            return back()->with('error', 'Landlord or Tenant ID is missing.');
        }

        // Kuhaa ang pinakabag-o nga lima ka notifications sa landlord.
        $notifications = notificationModel::where('receiverID', $landlord_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Ihap ang unread notifications sa landlord.
        $unreadCount = notificationModel::where('receiverID', $landlord_id)
            ->where('isRead', false)
            ->count();

        // Pangitaa ang landlord ug tenant records sa database.
        $landlord = landlordModel::find($landlord_id);
        $tenant = tenantModel::find($tenant_id);

        // Susiha kung valid ang landlord ug tenant nga gipili.
        if (!$tenant || !$landlord) {
            return back()->with('error', 'Invalid tenant or landlord.');
        }

        // Maghimo og unique conversation topic base sa tenant ug landlord IDs.
        $topic = "tenant\_{$tenant_id}\_landlord\_{$landlord_id}";

        // Pangitaa ang existing conversation o paghimo og bag-o kung wala pa.
        $conversation = conversationModel::firstOrCreate(
            ['topic' => $topic],
            ['initiatorID' => $landlord_id, 'initiatorRole' => 'landlord']
        );

        // I-update ang conversation timestamp aron maapil kini sa pinakabag-o nga activity.
        conversationModel::where('topic', $topic)->update(['updated_at' => now()]);

        // Kuhaa ang pinakabag-o nga message sa napiling conversation.
        $lastMessage = messageModel::where('conversationID', $conversation->id)
            ->latest('sentAt')
            ->first();

        // I-format ang napiling conversation isip collection para sa messaging history.
        $history = collect([
            [
                'conversation_id'   => $conversation->id,
                'receiver_name'     => $tenant->firstname . ' ' . $tenant->lastname,

                // Kuhaa ang tenant profile picture; gamita ang default image kung walay profile picture.
                'receiver_profile'  => $tenant->profilePicUrl
                                        ? '/' . $tenant->profilePicUrl
                                        : '/default-profile.png',

                'last_message'      => $lastMessage->message ?? '(No messages yet)',
                'sent_at'           => $lastMessage->sentAt ?? null,
            ]
        ]);

        // Ibalik ang messaging center view uban sa napiling tenant ug conversation.
        return view('landlord.auth.messagingCenter', [
            'title'        => 'DormDash Message',
            'headerName'   => 'DormDash Message',
            'landlord_id'  => $landlord_id,
            'history'      => $history,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    // Kuhaa ang tanang conversations nga konektado sa landlord.
    public function getConversations($landlord_id)
    {
        // Pangitaa ang conversations gamit ang landlord ID nga naa sa topic.
        $conversations = conversationModel::where('topic', 'like', '%landlord\_' . $landlord_id . '%')
            ->orderBy('updated_at', 'desc')
            ->get();

        // I-format ang matag conversation ngadto sa data nga gamiton sa frontend.
        $history = $conversations->map(function ($convo) use ($landlord_id) {

            // Kuhaa ang tenant ID gikan sa conversation topic.
            preg_match('/tenant\_([a-z0-9\\-]+)/', $convo->topic, $matches);
            $tenant_id = $matches[1] ?? null;

            // Pangitaa ang tenant record base sa nakuha nga ID.
            $tenant = $tenant_id ? tenantModel::find($tenant_id) : null;

            // Kuhaa ang pinakabag-o nga message sulod sa conversation.
            $lastMessage = messageModel::where('conversationID', $convo->id)
                ->orderBy('sentAt', 'desc')
                ->first();

            // Susiha kung adunay unread messages nga nadawat sa landlord.
            $hasUnread = messageModel::where('conversationID', $convo->id)
                ->where('receiverID', $landlord_id)
                ->where('isRead', 0)
                ->exists();

            // Ibalik ang conversation information lakip ang receiver ug unread status.
            return [
                'conversation_id'   => $convo->id,
                'receiver_name'     => $tenant ? $tenant->firstname . ' ' . $tenant->lastname : 'Unknown Tenant',

                // Kuhaa ang tenant profile picture o gamita ang default profile image.
                'receiver_profile'  => $tenant && $tenant->profilePicUrl
                                        ? asset('storage/' . $tenant->profilePicUrl)
                                        : asset('storage/uploads/profilePic/default-profile.png'),

                'last_message'      => $lastMessage->message ?? '(No messages yet)',
                'sent_at'           => $lastMessage->sentAt ?? null,

                // Ibalik ang 0 kung adunay unread message ug 1 kung nabasa na ang messages.
                'is_read'           => $hasUnread ? 0 : 1,
            ];
        });

        // Ibalik ang conversation history isip JSON response para sa frontend.
        return response()->json($history);
    }

    // Kuhaa ang tanang messages sulod sa usa ka specific conversation.
    public function getMessages($conversation_id)
    {
        // Pangitaa ang messages base sa conversation ID ug ihan-ay gikan sa pinakauna.
        $messages = \App\Models\messageModel::where('conversationID', $conversation_id)
            ->orderBy('sentAt', 'asc')
            ->get();

        // Ibalik ang messages isip JSON aron ma-display sa chat interface.
        return response()->json($messages);
    }

    // I-save ug ipadala ang bag-ong message tali sa landlord ug tenant.
    public function sendMessage(Request $request)
    {
        // I-validate ang conversation ID, message content ug sender information.
        $validated = $request->validate([
            'conversationID' => 'required|exists:conversations,id',
            'message' => 'required|string',
            'senderID' => 'required|string',
            'senderRole' => 'required|in:tenant,landlord',
        ]);

        // Pangitaa ang conversation record gamit ang conversation ID.
        $conversation = \App\Models\conversationModel::find($validated['conversationID']);

        // Kuhaa ang tenant ug landlord IDs gikan sa conversation topic.
        preg_match('/tenant\_([a-z0-9\\-]+)\_landlord\_([a-z0-9\\-]+)/', $conversation->topic, $matches);
        $tenantID = $matches[1] ?? null;
        $landlordID = $matches[2] ?? null;

        // Tukia kung kinsa ang makadawat base sa role sa nagpadala.
        $receiverID = $validated['senderRole'] === 'tenant' ? $landlordID : $tenantID;
        $receiverRole = $validated['senderRole'] === 'tenant' ? 'landlord' : 'tenant';

        // Susiha kung nakuha ba ang valid nga receiver ID gikan sa conversation topic.
        if (!$receiverID) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve receiver ID from topic.'
            ], 422);
        }

        // I-update ang conversation timestamp tungod kay adunay bag-ong message.
        conversationModel::where('id', $conversation->id)->update(['updated_at' => now()]);

        // Paghimo og bag-ong message record.
        $message = new \App\Models\messageModel();

        // Ibutang ang impormasyon sa message sama sa sender, receiver ug conversation.
        $message->conversationID = $validated['conversationID'];
        $message->message = $validated['message'];
        $message->senderID = $validated['senderID'];
        $message->senderRole = $validated['senderRole'];
        $message->receiverID = $receiverID;
        $message->receiverRole = $receiverRole;
        $message->sentAt = now();

        // I-set nga unread ang bag-ong message kay wala pa kini mabasa sa receiver.
        $message->isRead = 0;

        // I-save ang message record sa database.
        $message->save();

        // I-broadcast ang bag-ong message para makita dayon sa laing user pinaagi sa real-time chat.
        broadcast(new MessageSent($message))->toOthers();

        // Pangitaa ang receiver account base sa iyang role.
        if ($receiverRole === 'landlord') {
            $receiver = \App\Models\landlord\landlordModel::where('landlordID', $receiverID)->first();
            $senderName = 'A tenant';
        } else {
            $receiver = \App\Models\tenant\tenantaccountModel::where('tenantID', $receiverID)->first();
            $senderName = 'A landlord';
        }

        // Paghimo og notification record aron mahibal-an sa receiver nga adunay bag-ong message.
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

        // I-broadcast ang bag-ong notification aron makita kini sa receiver in real time.
        broadcast(new \App\Events\NewNotificationEvent($notifications));

        // Ipadayon ang SMS ug email notification kung nakit-an ang receiver account.
        if ($receiver) {

            // Himoa ang notification message nga ipadala pinaagi sa SMS ug email.
            $notificationMessage = "DormDash: {$senderName} has sent you a new message. Please log in to your DormDash account to reply.";

            // Sulayi ang pagpadala og SMS notification kung naa ang receiver phone number.
            try {
                if (!empty($receiver->phoneNumber)) {
                    app(IprogSMSService::class)->sendSMS(
                        $receiver->phoneNumber,
                        $notificationMessage
                    );
                }
            } catch (\Exception $smsError) {
                // I-save sa Laravel log ang SMS error kung mapakyas ang pagpadala.
                Log::error('Chat notification SMS failed: ' . $smsError->getMessage());
            }

            // Sulayi ang pagpadala og email notification kung naa ang receiver email.
            try {
                if (!empty($receiver->email)) {
                    Mail::raw($notificationMessage, function ($mail) use ($receiver) {
                        $mail->to($receiver->email)
                            ->subject('DormDash - New Message Notification');
                    });
                }
            } catch (\Exception $mailError) {
                // I-save sa Laravel log ang email error kung mapakyas ang pagpadala.
                Log::error('Chat notification email failed: ' . $mailError->getMessage());
            }
        }

        // Ibalik ang success response ug ang bag-ong message data ngadto sa frontend.
        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => $message
        ]);
    }

    // I-mark as read ang mga messages nga nadawat sa landlord.
    public function markAsRead($id)
    {
        // Kuhaa ang landlord ID gikan sa current session.
        $landlord_id = session('landlord_id');

        // I-update ang unread messages sa conversation nga nadawat sa landlord.
        $updated = messageModel::where('conversationID', $id)
            ->where('isRead', 0)
            ->where('receiverID', $landlord_id)
            ->update(['isRead' => 1]);

        // Ibalik ang success response ug ihap sa messages nga na-mark as read.
        return response()->json([
            'success' => true,
            'message' => "$updated messages marked as read."
        ]);
    }
}
