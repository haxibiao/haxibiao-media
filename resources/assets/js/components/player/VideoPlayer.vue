<template>
  <div id="dplayer" v-show="visible"></div>
</template>
<script>
import DPlayer from 'dplayer';
import { moment } from '../../utils';

export default {
  props: [
    'source',
    'episode',
    'movie_id',
    'notice',
    'series',
    'currentTime',
    'videoDuration',
    'apiDanmu',
    'apiSaveProgress',
    'apiGetProgress'
  ],
  mounted() {
    this.initPlayer();
    this.playerEventListener();
    this.restoreProgress();
    if (this.apiDanmu) {
      this.danmakuListener();
    }
    window.addEventListener('message', this.onWebViewMessage);
    window.addEventListener('beforeunload', this.beforeunloadListener);
  },
  beforeDestroy() {
    if (this.player) {
      this.player.destroy();
    }
    // 卸载事件
    window.removeEventListener('message', this.onWebViewMessage);
    window.removeEventListener('beforeunload', this.beforeunloadListener);
  },
  watch: {
    source(newV, oldV) {
      if (this.player) {
        this.player.switchVideo({
          url: newV,
          type: 'auto'
        });
        this.player.play();
      }
    },
    notice(newV, oldV) {
      if (this.player && newV) {
        this.player.notice(newV, '3000');
      }
    }
  },
  methods: {
    //播放器初始化
    initPlayer() {
      const options = {
        container: document.getElementById('dplayer'),
        preload: false,
        autoplay: true,
        screenshot: true,
        video: {
          url: this.source,
          type: 'auto'
        },
        pluginOptions: {
          hls: {}
        }
      };
      if (this.apiDanmu) {
        options.danmaku = {
          id: this.movie_id + '_' + this.series_index,
          user: this.$user.id,
          api: '/api/movie/danmu/',
          token: this.episode //是series 的 index
        };
      }
      this.player = new DPlayer(options);

      //5秒不能开始播放，算加载失败
      window.setTimeout(() => {
        if (!this.loadStatus) {
          window.playerEvent('加载失败');
          this.handlePlayError();
        }
      }, 5000);
    },

    // 播放器事件监听
    playerEventListener() {
      this.player.on('loadeddata', () => {
        this.loadStatus = true;
        console.log('片长加载成功');
        //加载成功切10分钟还在页面算完播
        window.setTimeout(() => {
          window.playerEvent('完播率统计');
        }, 600000);

        this.series_index = this.episode;
        if (this.seekTime) {
          this.player.seek(this.seekTime);
          this.seekTime = '';
        }
        const duration = moment.format(this.player.video.duration);
        this.$emit('update:videoDuration', duration);
      });

      this.player.on('timeupdate', () => {
        const currentTime = moment.format(this.player.video.currentTime);
        this.$emit('update:currentTime', currentTime);
      });

      this.player.on('ended', () => {
        this.$emit('playEnded');
        window.playerEvent('播完');
      });

      this.player.on('webfullscreen', () => {
        window.postMessage('fullscreen');
      });

      this.player.on('webfullscreen_cancel', () => {
        window.postMessage('fullscreen_cancel');
      });

      this.player.on('seeking', () => {
        const currentTime = moment.format(this.player.video.currentTime);
        this.$emit('update:currentTime', currentTime);
        let currentSeconds = Math.floor(this.player.video.currentTime);

        //30分钟以后 快进只弹海报一次
        if (currentSeconds >= 1800) {
          if (!this.inviteShown) {
            this.$bus.emit('SHOW_INVITE_MODAL');
            this.inviteShown = true;
          }
        }
      });

      this.player.on('error', () => {
        this.handlePlayError();
      });

      this.player.on('danmaku_send', (danmu) => {
        //调用一下评论接口，如果没有登录则打回来叫用户登录
        if (this.$user.id) {
          this.player.notice('您还没有登录，请登录后愉快的发送吧~', 5000);
          //隐藏弹幕的发送：因为Dplayer无论失败或成功 都会绘制一次弹幕直接隐藏这次操作
          this.player.danmaku.hide();
          return false;
        }
      });

      this.$bus.on('SHOW_INVITE_MODAL', () => {
        console.log('显示海报的时候隐藏video');
        this.visible = false;
        this.player.pause();
        this.player.notice('分享到群,或下载APP看完整高清版...', 5000);
      });

      this.$bus.on('CLOSE_INVITE_MODAL', () => {
        console.log('关闭海报的时候显示video');
        this.visible = true;
      });
    },

    // 弹幕监听
    danmakuListener() {
      const danmuPrefix = this.apiDanmu ? this.apiDanmu : 'danmu_';
      const channel = danmuPrefix + this.movie_id + '_' + this.episode;
      Echo.channel(channel).listen('DanmuEvent', (e) => {
        const danmu = {
          text: e.content,
          color: '#ffffff',
          type: e.type
        };
        //绘制弹幕 ,但是不对发送弹幕者进行二次绘制弹幕
        if (this.$user.id && this.$user.id == e.user_id) {
          return;
        }
        this.player.danmaku.draw(danmu);
      });
    },

    handlePlayError() {
      //线路出错的用容错影片播放
      if (window.fallback_movie) {
        this.player.switchVideo({
          url: window.fallback_movie,
          type: 'auto'
        });
        this.player.play();
      }
    },

    // 恢复播放记录
    restoreProgress() {
      //登录用户优先从获取数据库获取观看时长
      if (this.$user.id && this.apiGetProgress) {
        window.axios
          .post(
            this.apiGetProgress || `/api/movie/get-watch_progress`,
            {
              movie_id: this.movie_id
            },
            {
              headers: {
                token: this.$user.token
              }
            }
          )
          .then((response) => {
            if (response.data.status_code == 200) {
              //返回历史观看时长
              const history = response.data.data;
              // 跳转上次观看的集数
              console.log('restoreProgress api', history.source);
              this.$emit('update:source', history.source);
              this.$emit('update:episode', history.episode);
              this.seekTime = history.time;
              this.player.notice('上次观看到:' + moment.secondToDate(history.time), '5000');
            }
          });
      } else {
        let history = this.getCookieValue(this.movie_id);
        if (!history) {
          return;
        }
        history = JSON.parse(history);
        // 跳转上次观看的集数
        console.log('restoreProgress cookie', history.source);
        this.$emit('update:source', history.source);
        this.$emit('update:episode', history.episode);
        this.seekTime = history.time;
        this.player.notice('上次观看到:' + moment.secondToDate(history.time), '5000');
      }
    },

    //保存观看时长
    savePlayProgress() {
      if (this.$user.id && this.apiSaveProgress) {
        window.axios
          .post(
            this.apiSaveProgress || `/api/movie/save-watch_progress`,
            {
              movie_id: this.movie_id,
              series_id: this.series[this.episode - 1].id,
              progress: this.player.video.currentTime
            },
            {
              headers: {
                token: this.$user.token
              }
            }
          )
          .then(function (response) {
            console.log(response);
          })
          .catch((e) => {});
      } else {
        const history = {
          movieId: this.movie_id,
          time: this.player.video.currentTime,
          episode: this.episode,
          source: this.source
        };
        document.cookie = this.movie_id + '=' + JSON.stringify(history);
      }
    },

    beforeunloadListener(e) {
      this.savePlayProgress();
    },

    onWebViewMessage(event) {
      if (event.data == 'fullscreen_cancel') {
        this.player.fullScreen.cancel('web');
      }
    },

    // 获取cookie中指定key的数据
    getCookieValue(name) {
      let result = document.cookie.match('(^|[^;]+)\\s*' + name + '\\s*=\\s*([^;]+)');
      return result ? result.pop() : '';
    },

    //TODO:这里有一个历史问题就是之前没有传入参数series_id 所以使用第几集从后端算出来series_id 有空重构
    getSeiresIndex() {
      var history = this.getCookieValue(this.movie_id);
      if (typeof history == 'undefined' || history == null || history == '') {
        return this.episode;
      }
      var series_index = JSON.parse(history).episode;
      return series_index;
    }
  },
  data() {
    return {
      series_index: this.getSeiresIndex(),
      seekTime: '',
      loadStatus: null,
      inviteShown: false,
      visible: true
    };
  }
};
</script>

<style lang="scss" scoped>
#dplayer {
  width: 100%;
  height: 100%;
}
</style>
