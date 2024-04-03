<template>
  <form @submit.prevent="addComment()" class="d-flex w-100 align-items-center">

    <Mentionable
        :keys="['@', '#']"
        :items="users"
        offset="6"
        insert-space
        @open="onOpen"
        @apply="onApply"
        class="w-100"
    >
    <input
        v-model="messageText"
        ref="messageText"
        class="form-control mx-0 w-100"
        placeholder="Type your message here..."
        type="text"
    >

      <template #no-result>
        <div class="dim">
          No result
        </div>
      </template>

      <template #item-@="{ item }">
        <div class="user mention-item">
          {{ item.full_name }}
        </div>
      </template>

      <template #item-#="{ item }">
        <div class="user mention-item">
          {{ item.full_name }}
        </div>
      </template>
    </Mentionable>

    <input
        type="file"
        name="file"
        class="hidden-input"
        ref="file"
        accept=".jpg,.jpeg,.png,.gif"
        @change="onChange($event)"
        hidden
    />

    <span class="nav-link px-3" data-bs-toggle="tooltip" title="Attach a File" @click="openFileInput">
      <i class="fe fe-paperclip cursor-pointer"></i>
    </span>

    <button type="submit" class="btn btn-icon  btn-primary brround"><i class="fa fa-paper-plane-o"></i></button>

  </form>
</template>

<script>

import 'floating-vue/dist/style.css';
import axios from "axios";
import {Mentionable} from "vue-mention";

export default {
  name: "MessageForm",
  components: {Mentionable},
  props: {
    emailId: {type: Number, required: true},
    blockId: {type: String},
    comment: {type: Object},
  },
  data() {
    return {
      messageText: '',
      users: [],
      issues: [],
      mentions: [],
      files: [],
      errorMessage: null,
    }
  },
  methods: {
    addCommentUrl() {
        if (this.comment) {
            return "/emails/" + this.emailId + "/messages";
        }
      return "/emails/" + this.emailId + "/comments";
    },
    addComment(assetId = null) {
      let emails = [];
      /* this.mentions is a users[] mentioned in chat.  */
      /* check if mentioned user still exists in message */
      /* after backend response mentioned users array should be cleared */
      if (this.mentions.length > 0) {
        this.mentions.forEach(user => {
          if (this.messageText.includes('@' + user.full_name)) {
            emails.push(user.email);
          }
        });
      }

      let data = {
        email_id: this.emailId,
        block_id: this.blockId,
        comment_id: this.comment ? this.comment.id : null,
        asset_id: assetId || null,
        message: this.messageText,
        emails: emails,
      };

      const url = this.addCommentUrl();

      axios.post(url, data)
          .then(response => {
            if (response.data.success) {
              this.messageText = '';
              this.mentions = [];
              this.$emit('get-messages');
            }
          })
          .catch(error => {
            console.error('Error adding comment:', error);
          });
    },
    getUsers() {
      axios.get("/users/get-list", {
          }).then(response => {
        if (response.data.success) {
          this.users = this.prepareUsersData(response.data.records);
        }
      }).catch(error => {
        console.log(error.response.data.message || error.response.data || error)
      });
    },
    onOpen(key) {
      this.items = key === '@' ? this.users : [];
    },
    onApply (item, key, replacedWith) {
      this.mentions.push(item);
      // return focus on input when mentioned
      this.$nextTick(() => {
        setTimeout(() => {
          this.$refs.messageText.focus();
        }, 50);
      });
    },
    prepareUsersData(data) {
      return data.map(user => {
        return {
          ...user,
          value: user.full_name,
        };
      });
    },
    openFileInput() {
      this.$refs.file.click();
    },
    onChange(e) {
      const newFiles = [...e.target.files];
      if (newFiles.length > 0) {
        this.files.push(...newFiles);
        this.upload();
      }
    },
    upload() {
      const formData = new FormData();
      formData.append('files[]', this.files[0]);
      formData.append('entity', 'email');
      formData.append('id', this.emailId);
      axios.post(`/asset-manager/upload-one`, formData,
          {headers: {'Content-Type': 'multipart/form-data'},
          }).then(response => {
        if (response.data.success) {
          if (response.data.asset_id) {
            this.addComment(response.data.asset_id);
          }
        } else {
          if (response.data.message) {
            this.errorMessage = response.data.message;
          }
          console.log('Something went wrong');
        }
      }).catch(error => {
            console.log(error);
          }).finally(()=>{
        this.files = [];
        this.$refs.file.value = null;
      })
    },
  },
  beforeMount() {
    this.getUsers();
  }
}
</script>

<style scoped lang="scss">
.mention-item {
    padding: 4px 10px;
    border-radius: 4px;
    cursor: pointer;
    min-width: 120px;
    &:hover {
        background: #f7faff;
    }
}
.mention-selected {
    background: rgb(192, 250, 153);
}
</style>
