<template>
  <el-dialog
    center
    v-model="visible"
    destroy-on-close
    lock-scroll
    :show-close="false"
    custom-class="invite-modal"
    @open="$emit('onChange', true)"
    @close="$emit('onChange', false)"
  >
    <div class="content" @click="this.visible = false">
      <div class="header">
        <i class="el-icon-circle-close" @click="this.visible = false"></i>
        <p>
          {{ isIos ? '长按图片,添加到“照片”,分享至微信群继续看' : '长按图片转发至微信群继续看' }}
        </p>
      </div>
      <invite-card :movie="movie" />
    </div>
  </el-dialog>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { DEVICE_INFO } from '../config';
import InviteCard from './InviteCard.vue';

export default defineComponent({
  components: { InviteCard },
  props: {
    movie: {
      type: Object,
      default: {}
    }
  },
  emits: ['onChange'],
  mounted() {
    this.$bus.on('SHOW_INVITE_MODAL', () => {
      console.log('显示海报');
      this.visible = true;
    });
  },
  data() {
    return {
      visible: false,
      isIos: DEVICE_INFO.OS === 'IOS'
    };
  }
});
</script>

<style lang="scss">
.el-dialog.invite-modal {
  width: auto !important;
  position: relative;
  box-shadow: none;
  background: transparent;
  margin-top: 8vh !important;
  --el-dialog-padding-primary: 10px;
  .el-dialog__header {
    .el-dialog__title {
      color: #ffffff;
      font-size: 16px;
    }
  }
  .el-dialog__body {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0;
    .content {
      width: 100%;

      .header {
        color: #fff;
        text-align: center;
        font-size: 15px;
        padding-bottom: 15px;
        .el-icon-circle-close {
          font-size: 32px;
          color: #c0c0c0;
          text-align: center;
          margin-bottom: 15px;
        }
      }
    }
  }
}

.bottom {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  .close-button {
    width: 24px;
    height: 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgb(225 225 225 / 30%);
    color: #ffffff;
    &.enable {
      background: rgb(225 225 225 / 50%);
    }
  }
}
</style>
