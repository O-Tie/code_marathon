<template>
  <div class="" v-if="blockId">
    <div class="approve-buttons-wrapper">
        <div class="d-flex justify-content-center align-items-baseline" v-if="comment">
            <!-- Checkbox is checked -->
            <div class="material-switch d-flex justify-content-center align-items-center" v-if="comment && comment.is_approved == 1">
              <span class="mx-3" :class="{'disabled-label': !isAuthor}" style="margin-top: -0.4rem;">Approved</span>
              <input  id="someSwitchOptionSuccess" name="someSwitchOption001" type="checkbox" checked @change="changeCommentStatus" :disabled="!isAuthor">
              <label for="someSwitchOptionSuccess" class="label-success" :class="{'disabled-label': !isAuthor}"></label>
            </div>
            <!-- Checkbox is not checked -->
            <div class="material-switch d-flex justify-content-center align-items-center" v-else>
              <span class="mx-3" :class="{'disabled-label': !isAuthor}" style="margin-top: -0.4rem;" >Not Approved</span>
              <input id="someSwitchOptionSuccess" name="someSwitchOption001" type="checkbox" @change="changeCommentStatus" :disabled="!isAuthor">
              <label for="someSwitchOptionSuccess" class="label-success" :class="{'disabled-label': !isAuthor}"></label>
            </div>
        </div>
        <div class="" v-else><p class="text-center">Start giving your feedback by clicking the field below...</p></div>
    </div>
      <div class="feedback-list-wrapper">
          <div class="main-content-app pt-5">
                  <div class="main-content-body main-content-body-chat h-100">
                      <!-- main-chat-header -->
                      <div class="main-chat-body flex-2" id="ChatBody">
                          <div class="content-inner" v-if="messages.length > 0">
                              <div v-for="message in messages" :key="message.id" class="mb-3">
                                  <label v-if="!sameDay(message.created_at)" class="main-chat-time">
                                      <span>{{ formatDate(message.created_at) }}</span>
                                  </label>
                                  <div :class="{ 'media flex-row-reverse chat-right': message.created_by === email.created_by, 'media chat-left': message.created_by !== email.created_by }">
                                      <div class="main-img-user online">
                                          <img alt="User avatar" :src="message.user.avatar_image"
                                          :title="message.user.full_name">
                                      </div>
                                      <div class="media-body">
                                          <div v-if="message.asset_id && message.asset && message.asset.url">
                                            <img :src="message.asset.url" alt="Image">
                                          </div>
                                          <div v-else class="main-msg-wrapper">{{ message.message }}</div>
                                          <div>
                                              <span>{{ formatTime(message.created_at) }}</span>
                                              <a href="javascript:void(0)"><i class="icon ion-android-more-horizontal"></i></a>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                      <div class="main-chat-footer px-0">
                          <MessageForm :email-id="email.id" :block-id="blockId" :comment="comment" @get-messages="this.getMessages"/>
                      </div>
                  </div>
              </div>
      </div>
  </div>
  <div class="d-flex justify-content-center" v-else>
    <p>Select a block in the left-hand side to start giving feedback...</p>
  </div>
</template>

<script>

import MessageForm from "./MessageForm.vue";
import commonMixin from "../../../../mixins/common.mixin";

export default {
    name: 'Review',
    components: {MessageForm},
    mixins: [commonMixin],
    props: {
        email: {type: Object, required: true},
        blockId: {type: String, required: true},
        isAuthor: {type: Boolean, required: true},
    },
    data() {
        return {
            comment: null,
            messages: [],
            messagesCount: null,
            commentDate: null,
            intervalId: null,
        };
    },
    methods: {
        getMessages() {
            const params = {
                comment_id: this.commentId,
                block_id: this.blockId,
            };
            axios.get("/emails/" + this.email.id + "/messages", {params})
                .then(response => {
                    if (response.data.success) {
                        this.comment = response.data.comment;
                        if (this.comment) {
                            this.messages = this.comment.messages;
                            this.$emit('messages-count', this.comment.messages.length);
                        }
                    }
                }).catch(error => {
                console.log(error.response.data.message || error.response.data || error)
            }).finally(() => {
              this.$emit('update-status', this.comment);
            });
        },
        formatDate(dateTime) {
            return this.getLabelDate(dateTime).toUpperCase();
        },
        formatTime(dateTime) {
            const options = {hour: '2-digit', minute: '2-digit', hour12: false};
            return new Date(dateTime).toLocaleTimeString(undefined, options);
        },
        sameDay(date) {
            const formattedDate = this.formatDate(date);
            if (formattedDate !== this.commentDate) {
                this.commentDate = formattedDate;
                return false;
            }
            return true;
        },
        /* Update email comments after status changes. Needed for open export modal */
        changeCommentStatus() {
            axios.put("/emails/" + this.email.id + "/comments", {
                comment_id: this.comment.id,
                is_approved: this.comment.is_approved,
            }).then(response => {
                if (response.data.success) {
                    this.comment.is_approved = response.data.comment.is_approved;
                    this.switcher = Boolean(this.comment.is_approved);
                }
            }).catch(error => {
                console.log(error.response.data.message || error.response.data || error)
            }).finally(() => {
              this.$emit('update-status', this.comment);
            });
        }
    },
    beforeMount() {
        this.getMessages();
    },
    created() {
      // setInterval for call every minute
      this.intervalId = setInterval(() => {
        this.getMessages();
      }, 60000); // 60000 ms = 1 min
    },

    beforeDestroy() {
      // Destroy interval for prevent memory leak
      clearInterval(this.intervalId);
    },
}

</script>

<style scoped lang="scss">
.main-chat-body {
    overflow-y: scroll;
}

.disabled-label {
    opacity: 0.5;
}
</style>
