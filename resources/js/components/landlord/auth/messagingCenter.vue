<template>
    <Loader ref="loader" />

    <div class="container-fluid vh-100 p-0 overflow-hidden bg-light">
        <div class="row g-0 h-100">
            <!-- Sidebar / Conversations List -->
            <div
                class="col-12 col-md-4 col-lg-3 bg-white border-end d-flex flex-column h-100 shadow-sm z-1"
            >
                <div
                    class="p-3 border-bottom d-flex align-items-center justify-content-between"
                >
                    <div>
                        <h5 class="fw-bold mb-0 text-primary-custom">Inbox</h5>
                        <small class="text-muted fw-medium"
                            >Active Conversations</small
                        >
                    </div>
                    <span
                        class="badge rounded-pill bg-light text-dark border"
                        >{{ conversations.length }}</span
                    >
                </div>

                <!-- Conversation Items Scrollable List -->
                <div
                    class="flex-grow-1 overflow-y-auto p-2 custom-scrollbar"
                    style="background-color: #f8f9fa"
                >
                    <div
                        v-for="convo in conversations"
                        :key="convo.conversation_id"
                        @click.prevent="selectConversation(convo)"
                        class="convo-card d-flex align-items-center gap-3 p-3 mb-2 rounded-4 border shadow-sm transition-all cursor-pointer"
                        :class="{
                            'active-convo':
                                activeConversationID === convo.conversation_id,
                            'unread-convo':
                                convo.is_read === 0 &&
                                activeConversationID !== convo.conversation_id,
                        }"
                    >
                        <div class="position-relative flex-shrink-0">
                            <img
                                :src="
                                    convo.receiver_profile ||
                                    '/default-profile.png'
                                "
                                class="rounded-circle border border-2 border-white shadow-sm"
                                style="
                                    width: 48px;
                                    height: 48px;
                                    object-fit: cover;
                                "
                                alt="User Profile"
                            />
                            <span
                                v-if="convo.is_read === 0"
                                class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-2 border-white rounded-circle"
                            ></span>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div
                                class="d-flex justify-content-between align-items-baseline mb-1"
                            >
                                <h6
                                    class="mb-0 fw-bold text-truncate me-2 fs-6"
                                >
                                    {{ convo.receiver_name }}
                                </h6>
                            </div>
                            <p class="mb-0 small text-truncate opacity-75">
                                {{ convo.last_message || "No messages yet" }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="conversations.length === 0"
                        class="text-center p-4 text-muted"
                    >
                        <i class="bi bi-chat-left-dots display-6"></i>
                        <p class="mt-2 mb-0 small">No conversations yet</p>
                    </div>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div
                class="col-12 col-md-8 col-lg-9 d-flex flex-column h-100 bg-white"
            >
                <!-- Chat Header -->
                <div
                    class="d-flex align-items-center justify-content-between p-3 border-bottom shadow-sm bg-white z-2"
                >
                    <div class="d-flex align-items-center">
                        <img
                            :src="
                                activeLandlord.profilePicUrl ||
                                '/default-profile.png'
                            "
                            class="rounded-circle me-3 border shadow-sm"
                            style="width: 44px; height: 44px; object-fit: cover"
                            alt="Active User Profile"
                        />
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">
                                {{
                                    activeLandlord.firstname
                                        ? activeLandlord.firstname +
                                          " " +
                                          activeLandlord.lastname
                                        : "Select a conversation"
                                }}
                            </h6>
                            <span
                                class="badge rounded-pill px-2 py-1 tenant-badge"
                                >TENANT</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div
                    ref="chatContainer"
                    class="flex-grow-1 p-4 overflow-y-auto bg-light custom-scrollbar chat-bg"
                >
                    <div
                        v-if="!activeConversationID"
                        class="h-100 d-flex flex-column justify-content-center align-items-center text-muted"
                    >
                        <i class="bi bi-chat-square-text display-4 mb-2"></i>
                        <p>
                            Select a conversation from the sidebar to start
                            chatting.
                        </p>
                    </div>

                    <div
                        v-else
                        v-for="msg in messages"
                        :key="msg.id"
                        class="mb-3 d-flex"
                        :class="
                            msg.senderID === currentUserID
                                ? 'justify-content-end'
                                : 'justify-content-start'
                        "
                    >
                        <div style="max-width: 70%">
                            <div
                                class="p-3 shadow-sm mb-1 message-bubble"
                                :class="
                                    msg.senderID === currentUserID
                                        ? 'bg-primary-custom text-white bubble-sent'
                                        : 'bg-white text-dark border bubble-received'
                                "
                            >
                                <p
                                    class="mb-0"
                                    style="
                                        line-height: 1.5;
                                        word-break: break-word;
                                    "
                                >
                                    {{ msg.message }}
                                </p>
                            </div>

                            <div
                                class="small opacity-75 d-flex align-items-center gap-1"
                                :class="
                                    msg.senderID === currentUserID
                                        ? 'justify-content-end text-end'
                                        : 'justify-content-start text-start'
                                "
                            >
                                <span
                                    style="font-size: 0.7rem"
                                    class="text-muted"
                                    >{{ formatTime(msg.sentAt) }}</span
                                >
                                <i
                                    v-if="msg.senderID === currentUserID"
                                    class="bi bi-check2-all text-primary ms-1"
                                    style="font-size: 0.85rem"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Footer -->
                <div class="p-3 bg-white border-top">
                    <form
                        @submit.prevent="pushMessage"
                        class="input-group bg-light rounded-pill p-1 border"
                    >
                        <input
                            type="text"
                            v-model="message"
                            class="form-control border-0 bg-transparent px-4 py-2 shadow-none"
                            placeholder="Write a message..."
                            :disabled="!activeConversationID"
                        />

                        <button
                            type="submit"
                            class="btn rounded-pill px-4 shadow-sm text-white fw-bold btn-send"
                            :disabled="!activeConversationID || !message.trim()"
                        >
                            <i class="bi bi-send-fill me-1"></i> Send
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import Loader from "@/components/loader.vue";

export default {
    components: {
        Loader,
    },
    data() {
        return {
            landlordID: "",
            conversations: [],
            activeConversationID: null,
            messages: [],
            message: "",
            tenantID: "",
            currentUserID: "",
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
            if (this.$refs.loader) this.$refs.loader.loading = true;

            axios
                .get(`/api/landlord/conversations/${this.landlordID}`)
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
                })
                .finally(() => {
                    if (this.$refs.loader) this.$refs.loader.loading = false;
                });
        },
        async selectConversation(convo) {
            this.activeConversationID = convo.conversation_id;
            const nameParts = (convo.receiver_name || "").split(" ");
            this.activeLandlord = {
                firstname: nameParts[0] || "",
                lastname: nameParts.slice(1).join(" ") || "",
                profilePicUrl: convo.receiver_profile,
                is_read: convo.is_read,
            };
            this.fetchMessages(convo.conversation_id);

            try {
                const res = await axios.post(
                    `/api/landlord/markasread/${this.activeConversationID}`,
                );
                if (res.data.success) {
                    convo.is_read = 1;
                }
            } catch (err) {
                console.error("Failed to mark conversation as read:", err);
            }
        },
        fetchMessages(conversationID) {
            if (this.$refs.loader) this.$refs.loader.loading = true;

            axios
                .get(`/api/get/landlord/messages/${conversationID}`)
                .then((res) => {
                    this.messages = res.data;
                    this.scrollToBottom();
                })
                .catch((err) => {
                    console.error("Failed to fetch messages:", err);
                })
                .finally(() => {
                    if (this.$refs.loader) this.$refs.loader.loading = false;
                });
        },

        pushMessage() {
            const trimmedMessage = this.message.trim();
            if (!trimmedMessage || !this.activeConversationID) return;

            if (this.$refs.loader) this.$refs.loader.loading = true;

            axios
                .post("/api/landlord/messages", {
                    conversationID: this.activeConversationID,
                    message: trimmedMessage,
                    senderID: this.landlordID,
                    senderRole: "landlord",
                })
                .then(() => {
                    this.fetchMessages(this.activeConversationID);
                    this.message = "";
                })
                .catch((err) => {
                    console.error("Failed to send message:", err);
                    alert("Failed to send message. Please try again.");
                })
                .finally(() => {
                    if (this.$refs.loader) this.$refs.loader.loading = false;
                });
        },
        subscribeToConversations() {
            if (!window.Echo) return;

            this.conversations.forEach((convo) => {
                const channelName = `chat.${convo.conversation_id}`;
                window.Echo.private(channelName)
                    .listen(".message.sent", (e) => {
                        if (
                            this.activeConversationID ==
                            e.message.conversationID
                        ) {
                            this.messages.push(e.message);
                            this.scrollToBottom();
                        }
                    })
                    .error((err) => {
                        console.error(
                            `Subscription error for ${channelName}:`,
                            err,
                        );
                    });
            });
        },

        formatTime(datetime) {
            if (!datetime) return "";
            return new Date(datetime).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
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
        const container = document.getElementById("MessagingCenter");
        if (container) {
            this.landlordID = container.getAttribute("landlord_id");
            this.fetchConversations();
        } else {
            console.error("MessagingCenter container not found");
        }
        this.tenantID = localStorage.getItem("tenant_id") || "your-default-id";
        this.currentUserID = this.landlordID;
        this.currentUserRole = "landlord";
    },
};
</script>

<style scoped>
.text-primary-custom {
    color: #003c87;
}

.bg-primary-custom {
    background-color: #003c87;
}

.cursor-pointer {
    cursor: pointer;
}

.min-w-0 {
    min-width: 0;
}

/* Conversation Items Styling */
.convo-card {
    background-color: #ffffff;
    color: #333333;
    transition: all 0.2s ease-in-out;
}

.convo-card:hover {
    background-color: #f0f4f9;
    transform: translateY(-1px);
}

.active-convo {
    background-color: #003c87 !important;
    color: #ffffff !important;
}

.active-convo p {
    color: rgba(255, 255, 255, 0.8) !important;
}

.unread-convo {
    border-left: 4px solid #fc7d07 !important;
    font-weight: 600;
}

/* Badge & Buttons */
.tenant-badge {
    background-color: #eef2f7;
    color: #003c87;
    font-size: 0.7rem;
}

.btn-send {
    background-color: #fc7d07;
    border: none;
    transition:
        background-color 0.2s ease,
        opacity 0.2s ease;
}

.btn-send:hover:not(:disabled) {
    background-color: #e06c00;
}

.btn-send:disabled {
    opacity: 0.6;
}

/* Chat Message Bubbles */
.message-bubble {
    word-wrap: break-word;
}

.bubble-sent {
    border-radius: 18px 18px 0px 18px;
}

.bubble-received {
    border-radius: 18px 18px 18px 0px;
}

.chat-bg {
    background-image: radial-gradient(#dee2e6 0.75px, transparent 0.75px);
    background-size: 20px 20px;
}

/* Modern Unified Scrollbar Styling */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
    transition: background-color 0.2s;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #003c87;
}
</style>
