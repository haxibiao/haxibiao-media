<template>
  <div class="movie-editing">
    <div
      class="editing-panel"
      v-loading="loading"
      element-loading-text="剪辑处理中"
      element-loading-spinner="el-icon-loading"
      element-loading-background="rgba(0, 0, 0, 0.8)"
    >
      <div class="panel-wrap">
        <div class="editing-item">
          <div class="editing-item-head">
            <span class="editing-item-head-text">视频剪辑</span>
            <span class="editing-item-head-more" @click="onClose">
              <i class="iconfont icon-close"></i>
            </span>
          </div>
          <div class="editing-type">
            <el-input v-model="title" placeholder="视频标题" prefix-icon="el-icon-edit"></el-input>
          </div>
          <div class="editing-type">
            <el-time-select
              placeholder="起始时间"
              v-model="startTime"
              :default-value="new Date('December 17, 2020 00:00:01')"
              :picker-options="{
                selectableRange: `00:00:01 - ${videoDuration}`
              }"
            >
            </el-time-select>
          </div>
          <div class="editing-type">
            <el-select v-model="duration" placeholder="剪辑时长" prefix-icon="el-icon-timer">
              <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"> </el-option>
            </el-select>
          </div>
          <!-- <div class="editing-type">
                            <div class="editing-type-title">剪辑时长</div>
                            <div class="editing-type-content">
                            </div>
                        </div> -->
          <div class="editing-button">
            <el-button type="primary" v-on:click="submit" :disabled="disabled">开始剪辑</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import moment from '../../common/moment';

export default {
  props: ['apiClip', 'movieId', 'source', 'currentEpisode', 'movieTitle', 'currentTime', 'videoDuration'],
  methods: {
    onClose() {
      this.$emit('onClose');
    },
    submit() {
      this.loading = true;
      var startTime = moment.toSecond(new Date(this.startTime));
      var endTime = startTime + this.duration;
      let apiClip = this.apiClip ? this.apiClip : '/api/movie/clip';
      window.axios
        .post(
          `${apiClip}`,
          {
            start_time: startTime,
            end_time: endTime,
            post_title: this.title,
            m3u8: this.source,
            movie_id: this.movieId,
            series_index: this.currentEpisode
          },
          {
            headers: {
              token: this.$user.token
            }
          }
        )
        .then((response) => {
          this.startTime = '';
          this.$message({
            showClose: true,
            message: '剪辑成功',
            type: 'success'
          });
        })
        .catch((e) => {
          this.$message({
            showClose: true,
            message: '剪辑失败',
            type: 'error'
          });
        })
        .finally(() => {
          this.loading = false;
        });
    }
  },

  watch: {
    currentTime(n, o) {
      if (!this.loading) {
        // this.startTime = new Date(`2021 ${n}`);
        this.startTime = new Date(`December 17, 2020  ${n}`);
      }
    },
    movieTitle(n, o) {
      if (!this.loading) {
        this.title = n;
      }
    }
  },

  computed: {
    disabled() {
      return !this.title || !this.startTime || !this.duration;
    }
  },

  data() {
    return {
      title: this.movieTitle,
      startTime: '',
      options: [
        {
          value: 15,
          label: '15s'
        },
        {
          value: 30,
          label: '30s'
        },
        {
          value: 45,
          label: '45s'
        },
        {
          value: 60,
          label: '60s'
        },
        {
          value: 120,
          label: '2min'
        },
        {
          value: 180,
          label: '3min'
        },
        {
          value: 240,
          label: '4min'
        },
        {
          value: 300,
          label: '5min'
        }
      ],
      duration: '',
      loading: false
    };
  }
};
</script>

<style lang="scss">
.movie-editing {
  display: inline-block;
  cursor: default;
  background: none;
  border-radius: 4px;
  text-align: left;
  box-sizing: border-box;
  font-size: 12px;
  overflow: hidden;
  // position: absolute;
  // bottom: 46px;
  // left: 46px;
  // z-index: 1001;
}
.editing-panel {
  background-color: #fff;
}
.panel-wrap {
  width: 260px;
  max-height: 480px;
}
.editing-item {
  width: 100%;
  height: 100%;
  padding: 20px;
}
.editing-item-head {
  margin-bottom: 15px;
  .editing-item-head-text {
    font-size: 18px;
    font-weight: 400;
    color: #1f2f3d;
  }
  .editing-item-head-more {
    float: right;
    width: 24px;
    height: 24px;
    text-align: center;
    line-height: 24px;
    cursor: pointer;
    .iconfont {
      font-size: 18px;
    }
  }
}

.editing-type {
  margin-bottom: 15px;
}
.el-date-editor.el-input,
.el-date-editor.el-input__inner {
  width: 100%;
}
.el-button,
.el-select {
  width: 100%;
}
</style>
