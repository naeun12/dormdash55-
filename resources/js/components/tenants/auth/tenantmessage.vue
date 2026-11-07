<template>
    <div class="chat-wrapper vh-100 d-flex flex-column bg-light">
        <div class="row flex-grow-1 g-0 overflow-hidden">
            <!-- Sidebar / Conversations List -->
            <div
                class="col-md-3 bg-white border-end shadow-sm d-flex flex-column"
            >
                <div class="p-3 sidebar-header">
                    <h5 class="fw-bold text-white mb-0">Inbox</h5>
                </div>
                <div
                    class="list-group list-group-flush p-2 custom-inbox-scroll"
                >
                    <a
                        v-for="convo in conversations"
                        :key="convo.conversation_id"
                        href="#"
                        class="convo-item list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 px-3 mb-2 rounded-3 transition"
                        :class="{
                            'active-convo': isActiveConversation(
                                convo.conversation_id,
                            ),
                        }"
                        @click.prevent="selectConversation(convo)"
                    >
                        <div class="position-relative">
                            <img
                                :src="
                                    convo.receiver_profile
                                        ? `/${convo.receiver_profile}`
                                        : '/default-profile.png'
                                "
                                class="rounded-circle border border-2 profile-pic"
                            />
                            <span
                                v-if="convo.is_read === 0"
                                class="orange-dot"
                            ></span>
                        </div>

                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="mb-0 fw-bold name-label">
                                {{ convo.receiver_name }}
                            </h6>
                            <small
                                class="last-msg-text text-truncate d-block"
                                >{{ convo.last_message }}</small
                            >
                        </div>
                    </a>
                </div>
            </div>

            <!-- Chat Main Window -->
            <div
                class="col-md-9 d-flex flex-column p-0 chat-bg-pattern overflow-hidden"
            >
                <!-- Chat Header -->
                <div
                    class="d-flex align-items-center bg-white shadow-sm p-3 border-bottom border-primary-subtle"
                >
                    <img
                        :src="
                            activeLandlord.profilePicUrl
                                ? '/' + activeLandlord.profilePicUrl
                                : '/default-profile.png'
                        "
                        class="rounded-circle me-3 border border-2 border-primary"
                        style="width: 45px; height: 45px; object-fit: cover"
                    />
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">
                            {{
                                activeLandlord.firstname
                                    ? activeLandlord.firstname +
                                      " " +
                                      activeLandlord.lastname
                                    : "Loading..."
                            }}
                        </h6>
                        <small class="text-primary fw-semibold"
                            ><i class="bi bi-patch-check-fill me-1"></i
                            >Landlord</small
                        >
                    </div>
                </div>

                <!-- Messages Container -->
                <div
                    ref="chatContainer"
                    class="p-4 d-flex flex-column gap-3 custom-chat-scroll"
                >
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="d-flex w-100"
                        :class="
                            msg.senderID === currentUserID
                                ? 'justify-content-end'
                                : 'justify-content-start'
                        "
                    >
                        <div
                            :class="
                                msg.senderID === currentUserID
                                    ? 'bubble-sent'
                                    : 'bubble-received'
                            "
                            class="message-bubble shadow-sm"
                        >
                            <p class="mb-1">{{ msg.message }}</p>
                            <div class="bubble-time">
                                {{ formatRole(msg.senderRole) }} •
                                {{ formatTime(msg.sentAt) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Input Footer -->
                <div class="p-3 bg-white border-top">
                    <div class="input-group custom-input-group shadow-sm">
                        <input
                            type="text"
                            v-model="message"
                            class="form-control border-0 px-4"
                            placeholder="Type a message..."
                            @keyup.enter="pushMessage"
                        />
                        <button
                            class="btn btn-orange-send px-4"
                            @click="pushMessage"
                        >
                            <i class="bi bi-send-fill me-2"></i>SEND
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            landlordID: "",
            conversations: [],
            pollInterval: null,
            activeConversationID: null,
            messages: [],
            newMessage: "",
            message: "",
            tenantID: "",
            currentUserID: "",
            echoChannel: null,
            currentUserRole: "",
            activeLandlord: {
                firstname: "",
                lastname: "",
                profilePicUrl: "",
            },
        };
    },

    methods: {
        fetchConversations() {
            axios
                .get(`/api/tenant/conversations/${this.tenantID}`)
                .then((res) => {
                    this.conversations = res.data;
                    this.subscribeToConversations();
                    if (
                        !this.activeConversationID &&
                        this.conversations.length > 0
                    ) {
                        this.selectConversation(this.conversations[0]);
                    }
                })
                .catch((err) => {
                    console.error("Failed to fetch conversations:", err);
                });
        },
        async selectConversation(convo) {
            this.activeConversationID = convo.conversation_id;
            this.activeLandlord = {
                firstname: convo.receiver_name.split(" ")[0] || "",
                lastname: convo.receiver_name.split(" ")[1] || "",
                profilePicUrl: convo.receiver_profile || "default-profile.png",
                is_read: convo.is_read,
            };
            this.fetchMessages(convo.conversation_id);
            const res = await axios.post(
                `/api/tenant/markasread/${this.activeConversationID}`,
            );

            if (res.data.success) {
                convo.is_read = 1;
            } else {
                console.error("Failed to mark conversation as read");
            }
        },
        fetchMessages(conversationID) {
            axios
                .get(`/api/get/tenant/messages/${conversationID}`)
                .then((res) => {
                    this.messages = res.data;
                    this.scrollToBottom();
                })
                .catch((err) => {
                    console.error("Failed to fetch messages:", err);
                });
        },
        subscribeToConversations() {
            this.conversations.forEach((convo) => {
                const channelName = `chat.${convo.conversation_id}`;

                window.Echo.private(channelName)
                    .subscribed(() => {})
                    .listen(".message.sent", (e) => {
                        if (
                            this.activeConversationID ==
                            e.message.conversationID
                        ) {
                            this.messages.push(e.message);
                            this.scrollToBottom();
                        } else {
                            console.log(
                                `🔔 New message in another conversation: ${e.message.conversationID}`,
                            );
                        }
                    })
                    .error((err) => {
                        console.error(
                            `❌ Subscription error for ${channelName}:`,
                            err,
                        );
                    });
            });
        },
        pushMessage() {
            const trimmedMessage = this.message.trim();
            if (!trimmedMessage) return;

            axios
                .post("/api/tenant/messages", {
                    conversationID: this.activeConversationID,
                    message: trimmedMessage,
                    senderID: this.tenantID,
                    senderRole: "tenant",
                })
                .then(() => {
                    this.fetchMessages(this.activeConversationID);
                    this.message = "";
                })
                .catch((err) => {
                    console.error("❌ Failed to send message:", err);
                    alert("Failed to send message. Please try again.");
                });
        },

        isActiveConversation(id) {
            return this.activeConversationID === id;
        },

        formatTime(datetime) {
            return new Date(datetime).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        formatRole(role) {
            return role.charAt(0).toUpperCase() + role.slice(1);
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },
    },

    mounted() {
        const el = document.getElementById("tenantmessage");
        this.tenantID = el.getAttribute("data-tenant-id")?.trim();
        this.landlordID = el.getAttribute("data-landlord-id")?.trim();
        this.fetchConversations();
        this.currentUserID = this.tenantID;
        this.currentUserRole = "tenant";
    },
};
</script>

<style scoped></style>
<style scoped src="/resources/css/tenant/message.css"></style>
