<template>
    <Loader ref="loader" />

    <div
        class="container-fluid vh-100 d-flex flex-column bg-light p-0 overflow-hidden"
    >
        <div class="row g-0 flex-grow-1 overflow-hidden">
            <div
                class="col-md-3 bg-white border-end d-flex flex-column shadow-sm"
            >
                <div class="p-4 border-bottom">
                    <h4 class="fw-bold mb-0" style="color: #003c87">Inbox</h4>
                    <small class="text-muted fw-medium"
                        >Active Conversations</small
                    >
                </div>

                <div
                    class="flex-grow-1 overflow-auto p-2"
                    style="background-color: #f8f9fa"
                >
                    <div
                        v-for="convo in conversations"
                        :key="convo.conversation_id"
                        @click.prevent="selectConversation(convo)"
                        class="d-flex align-items-center gap-3 p-3 mb-2 rounded-4 transition-all border-0 shadow-sm"
                        :style="
                            convo.is_read === 0
                                ? 'background-color: #003C87; color: white; cursor: pointer;'
                                : 'background-color: white; color: #333; border: 1px solid #eee !important; cursor: pointer;'
                        "
                    >
                        <div class="position-relative">
                            <img
                                :src="
                                    convo.receiver_profile ||
                                    'default-profile.png'
                                "
                                class="rounded-circle border border-2 border-white shadow-sm"
                                style="
                                    width: 52px;
                                    height: 52px;
                                    object-fit: cover;
                                "
                            />

                            <span
                                v-if="convo.is_read === 0"
                                class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-2 border-white rounded-circle"
                            >
                            </span>
                        </div>

                        <div class="flex-grow-1 overflow-hidden">
                            <h6 class="mb-0 fw-bold text-truncate">
                                {{ convo.receiver_name }}
                            </h6>
                            <p
                                class="mb-0 small text-truncate opacity-75"
                                :class="
                                    convo.is_read === 0
                                        ? 'text-white'
                                        : 'text-secondary'
                                "
                            >
                                {{ convo.last_message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9 d-flex flex-column bg-white">
                <div
                    class="d-flex align-items-center p-3 border-bottom shadow-sm bg-white"
                    style="z-index: 10"
                >
                    <img
                        :src="activeLandlord.profilePicUrl"
                        class="rounded-circle me-3 border shadow-sm"
                        style="width: 48px; height: 48px; object-fit: cover"
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
                            class="badge rounded-pill px-2 py-1"
                            style="
                                background-color: #eef2f7;
                                color: #003c87;
                                font-size: 0.7rem;
                            "
                            >TENANT</span
                        >
                    </div>
                </div>

                <div
                    ref="chatContainer"
                    class="flex-grow-1 p-4 overflow-auto bg-light"
                    style="
                        background-image: radial-gradient(
                            #dee2e6 0.5px,
                            transparent 0.5px
                        );
                        background-size: 20px 20px;
                    "
                >
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="mb-4 d-flex"
                        :class="
                            msg.senderID === currentUserID
                                ? 'justify-content-end'
                                : 'justify-content-start'
                        "
                    >
                        <div style="max-width: 65%">
                            <div
                                class="p-3 shadow-sm mb-1"
                                :style="
                                    msg.senderID === currentUserID
                                        ? 'background-color: #003C87; color: white; border-radius: 18px 18px 0px 18px;'
                                        : 'background-color: white; color: #333; border-radius: 18px 18px 18px 0px; border: 1px solid #eee;'
                                "
                            >
                                <p class="mb-0" style="line-height: 1.5">
                                    {{ msg.message }}
                                </p>
                            </div>

                            <div
                                class="small opacity-50 d-flex align-items-center"
                                :class="
                                    msg.senderID === currentUserID
                                        ? 'justify-content-end text-end'
                                        : 'justify-content-start text-start'
                                "
                            >
                                <span style="font-size: 0.7rem">{{
                                    formatTime(msg.sentAt)
                                }}</span>
                                <i
                                    v-if="msg.senderID === currentUserID"
                                    class="bi bi-check2-all ms-1 text-primary"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white border-top">
                    <div class="input-group bg-light rounded-pill p-1 border">
                        <input
                            type="text"
                            v-model="message"
                            class="form-control border-0 bg-transparent px-4 py-2"
                            placeholder="Write a message..."
                            @keyup.enter="pushMessage"
                        />

                        <button
                            class="btn rounded-pill px-4 shadow-sm text-white fw-bold"
                            style="background-color: #fc7d07; transition: 0.2s"
                            @click="pushMessage"
                        >
                            <i class="bi bi-send-fill me-1"></i> Send
                        </button>
                    </div>
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
            pollInterval: null,
            activeConversationID: null,
            messages: [],
            message: "",
            newMessage: "",

            tenantID: "",
            currentUserID: "",
            currentUserRole: "",
            echoChannel: null,

            activeConversationID: null,
            activeLandlord: {
                firstname: "",
                lastname: "",
                profilePicUrl: "",
            },
        };
    },

    methods: {
        fetchConversations() {
            this.$refs.loader.loading = true;

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
                    this.$refs.loader.loading = false;
                });
        },
        async selectConversation(convo) {
            this.activeConversationID = convo.conversation_id;
            this.activeLandlord = {
                firstname: convo.receiver_name.split(" ")[0] || "",
                lastname: convo.receiver_name.split(" ")[1] || "",
                profilePicUrl: convo.receiver_profile,
                is_read: convo.is_read,
            };
            this.fetchMessages(convo.conversation_id);
            if (this.messagePollInterval) {
                clearInterval(this.messagePollInterval);
            }
            const res = await axios.post(
                `/api/landlord/markasread/${this.activeConversationID}`,
            );

            if (res.data.success) {
                convo.is_read = 1;
            } else {
                console.error("Failed to mark conversation as read");
            }
        },
        fetchMessages(conversationID) {
            this.$refs.loader.loading = true;

            axios
                .get(`/api/get/landlord/messages/${conversationID}`)
                .then((res) => {
                    this.messages = res.data;
                    this.scrollToBottom(); // 👈 auto scroll here
                })
                .catch((err) => {
                    console.error("Failed to fetch messages:", err);
                })
                .finally();
            {
                this.$refs.loader.loading = false;
            }
        },

        pushMessage() {
            const trimmedMessage = this.message.trim();
            this.$refs.loader.loading = true;

            if (!trimmedMessage) return;

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
                    console.error("❌ Failed to send message:", err);
                    alert("Failed to send message. Please try again.");
                })
                .finally();
            {
                this.$refs.loader.loading = false;
            }
        },
        subscribeToConversations() {
            this.conversations.forEach((convo) => {
                const channelName = `chat.${convo.conversation_id}`;
                console.log(channelName);

                window.Echo.private(channelName)
                    .subscribed(() => {})
                    .listen(".message.sent", (e) => {
                        // Check if this message is for the currently active conversation
                        if (
                            this.activeConversationID ==
                            e.message.conversationID
                        ) {
                            this.messages.push(e.message);
                            this.scrollToBottom();
                        } else {
                            // Optional: update unread badge or notification
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
.list-group-item.active {
    background-color: #e7f1ff;
    border-left: 4px solid #0d6efd;
    font-weight: 500;
}

.transition {
    transition: background-color 0.2s ease-in-out;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-thumb {
    background-color: #adb5bd;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background-color: #868e96;
}
/* Conversation list scrollbar */
.flex-grow-1.overflow-auto::-webkit-scrollbar {
    width: 6px;
}

.flex-grow-1.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.flex-grow-1.overflow-auto::-webkit-scrollbar-thumb {
    background: #adb5bd;
    border-radius: 10px;
}

.flex-grow-1.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #003c87;
}

/* Chat messages scrollbar */
[ref="chatContainer"]::-webkit-scrollbar {
    width: 6px;
}

[ref="chatContainer"]::-webkit-scrollbar-track {
    background: #f1f1f1;
}

[ref="chatContainer"]::-webkit-scrollbar-thumb {
    background: #adb5bd;
    border-radius: 10px;
}

[ref="chatContainer"]::-webkit-scrollbar-thumb:hover {
    background: #003c87;
}

/* Firefox */
.flex-grow-1.overflow-auto,
[ref="chatContainer"] {
    scrollbar-width: thin;
    scrollbar-color: #adb5bd #f1f1f1;
}
</style>
