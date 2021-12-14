<template>
  <div :class="['movie-player', wideSwitch && 'player_container_wide']">
    <div class="player-main">
      <div class="player-container player-fixed">
        <!-- <a class="player-fixed-off" href="javascript:;" style="display: none;"><i class="iconfont icon-close"></i></a> -->
        <div class="embed-responsive">
          <div class="video-player">
            <div class="fluid_video_wrapper">
              <template v-if="source">
                <div class="alert-wrap">
                  <!-- <el-alert
                                        title="谨防被骗！请不要相信视频中的广告和网站,播放卡顿的下载APP看更流畅"
                                        type="warning"
                                        center
                                        show-icon
                                    /> -->
                </div>
                <VideoPlayer
                  :current-time.sync="currentTime"
                  :video-duration.sync="videoDuration"
                  :notice="noticeInfo"
                  :source.sync="source"
                  :episode.sync="currentEpisode"
                  :movie_id="movie.id"
                  :series="series"
                  :apiDanmu="apiDanmu"
                  :apiSaveProgress="apiSaveProgress"
                  :apiGetProgress="apiGetProgress"
                  @playEnded="nextPicode"
                />
              </template>
              <template v-if="!source">
                <div class="alert-wrap">
                  <!-- <el-alert
                                        title="视频加载失败，请尝试切换线路进行播放"
                                        type="error"
                                        center
                                        show-icon
                                    /> -->
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
      <ul class="player__operate clearfix">
        <li class="fl operation hide-xs" v-if="this.$user.token">
          <a :class="['favorite', movie.isFan && 'highlight']" href="javascript:void(0);" v-on:click="favoriteHandler">
            <i class="iconfont icon-collection-fill"></i>
            <span>{{ movie.isFan ? '已收藏' : '收藏' }}</span>
          </a>
        </li>
        <li class="fl operation" v-if="this.$user.token">
          <a :class="['like', movie.isliked && 'highlight']" href="javascript:void(0);" v-on:click="likeHandler">
            <i class="iconfont icon-good-fill"></i>
            <span class="num">{{ movie.count_likes || 0 }}</span>
          </a>
        </li>
        <li class="fl operation" data-toggle="modal" data-target="#report-modal">
          <a href="javascript:void(0);">
            <i class="iconfont icon-warning-fill"></i>
            <span class="mobile">举报</span>
          </a>
        </li>
        <li class="fl operation download-app" dropdown-target=".download-app_qrcode" dropdown-toggle="hover">
          <a :href="downloadPageUrl" target="_blank">
            <i class="iconfont icon-mobile"></i>
            <span class="mobile">观看</span>
          </a>
          <div class="dropdown-box download-app_qrcode">
            <div class="qrcode_section">
              <div class="qr_pic" id="qrcode">
                <img style="display: block" :src="qrcode" />
              </div>
              <div class="qr_txt">扫一扫 手机继续看</div>
            </div>
          </div>
        </li>
        <!-- <el-popover placement="bottom" trigger="manual" v-model="editingVisible" v-if="videoDuration">
          <movie-editing
                        :api-clip="apiClip"
                        :movieId="movie.id"
                        :source="source"
                        :current-episode="currentEpisode"
                        :movie-title="movieTitle"
                        :current-time="currentTime"
                        :video-duration="videoDuration"
                        @onClose="editingVisible = !editingVisible"
                    />
          <li class="fl operation" slot="reference" @click="toggleEditing">
            <a href="javascript:void(0);">
              <i class="iconfont icon-scenes-fill"></i>
              <span class="mobile">剪辑</span>
            </a>
          </li>
        </el-popover> -->

        <!-- <el-popover placement="bottom" trigger="manual" v-model="playLinesVisible">
                    <play-lines
                        :lines="[]"
                        @onSwitchLine="switchLine"
                        @onClose="playLinesVisible = !playLinesVisible"
                    />
                    <li class="fl operation" slot="reference" @click="togglePlayLines">
                        <a href="javascript:void(0);">
                            <i class="iconfont icon-scenes-fill"></i>
                            <span class="mobile">线路</span>
                        </a>
                    </li>
                </el-popover>-->
        <li class="fr operation" style="margin: 0">
          <a
            :class="{ disabled: series.length <= currentEpisode }"
            href="javascript:void(0);"
            v-on:click="clickEpisode(currentEpisode)"
          >
            <i class="iconfont icon-arrow-right hide-xxs"></i>
            <span>下集</span>
          </a>
        </li>
        <li class="fr operation">
          <a
            :class="{ disabled: currentEpisode < 2 }"
            href="javascript:void(0);"
            v-on:click="clickEpisode(currentEpisode - 2)"
          >
            <i class="iconfont icon-arrow-left hide-xxs"></i>
            <span>上集</span>
          </a>
        </li>
      </ul>
      <div class="player_container_wide_switch hide-md" v-on:click="wideSwitchHandler">
        <span class="btn_switch_bg"></span>
        <i :class="['iconfont', 'icon-arrow-left', !wideSwitch && 'hidden']"></i>
        <i :class="['iconfont', 'icon-arrow-right', wideSwitch && 'hidden']"></i>
      </div>
    </div>
    <div class="player-side">
      <div class="side-panel">
        <div class="movie-info col-pd">
          <div class="video_desc">
            <h3 class="title">
              {{ movie.name }}&nbsp;
              <small class="text-ep" v-if="series.length > 1">第{{ currentEpisode }}集</small>
            </h3>
          </div>
          <div class="video_desc" >
            <div class="type">
              {{ movie.score || Math.round(Math.max(6, Math.random() * 10)) + '.' + 0 }}分&nbsp;/&nbsp;
              <a href="javascript:void(0);">{{ movie.region || '未知' }}</a
              >&nbsp;/&nbsp;
              <a href="javascript:void(0);">{{ movie.type_name || '未知' }}</a>
            </div>
            <div v-if="movie && movie.play_lines && movie.play_lines.length > 0">
              <el-dropdown @command="switchLine">
                <el-button>
                  {{ playLineName }}
                  <i class="iconfont icon-arrow-right hide-xxs"></i>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu slot="dropdown">
                    <el-dropdown-item
                      v-for="(lineName, index) in playLines"
                      :key="index"
                      :command="index"
                      :disabled="index == lineSelected"
                      >{{ lineName }}</el-dropdown-item
                    >
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </div>
        </div>
        <div class="video_playlist" id="playlist">
          <ul class="panel-content__list">
            <li
              :class="{
                'col-xs-2': series.length > 1,
                'col-xs-3': series.length <= 1,
                'col-sm-2': series.length <= 1,
                'col-lg-6': series.length <= 1
              }"
              v-for="(media, index) in series"
              :key="index"
            >
              <a
                href="javascript:void(0)"
                :class="['btn-episode', currentEpisode == index + 1 && 'active', !(series.length > 1) && 'btn-md']"
                v-on:click="clickEpisode(index)"
                >{{ index + 1 }}</a
              >
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { moment } from '../../utils';
import VideoPlayer from './VideoPlayer.vue';

export default {
  components: {
    VideoPlayer
  },
  props: [
    'movieData',
    'initEpisode',
    'qrcode',
    'apkUrl',
    'appDownload',
    'apiDanmu',
    'apiSaveProgress',
    'apiGetProgress',
    'apiClip',
    'apiLike',
    'apiFavorite'
  ],
  updated() {
    console.log('updated source', this.source);
  },
  created() {
    if (this.initEpisode) {
      this.currentEpisode = Number(this.initEpisode) + 1;
    }
  },
  mounted() {
    if (this.movieData !== null && typeof this.movieData === 'object') {
      this.movie = this.movieData;
      console.log('movie', this.movieData);

      this.series = this.movie.play_lines[0].data || this.movie.series || [];
      this.playLineName = this.movie.play_lines[0].name || this.movie.series.source_name || '默认';

      //线路信息
      if (this.movie.play_lines) {
        let lines = [];
        for (var lineIndex in this.movie.play_lines) {
          let line = this.movie.play_lines[lineIndex];
          lines.push(line.name);
        }
        this.playLines = lines;
        console.log('this.playLines', this.playLines);
      }

      this.source = this.series[this.currentEpisode - 1]?.url;
      console.log('mounted source', this.currentEpisode, this.source);
    }

    const that = this;
    let apiReport = this.apiReport ? this.apiReport : '/api/movie/report';
    this.$nextTick(function () {
      // 举报视频submit事件
      $('#report-modal .btn-submit').on('click', function reportSubmit(event) {
        this.$bus.emit('SHOW_INVITE_MODAL');

        const params = $('#report-form').serialize();
        if (!params) return;
        $.ajax({
          type: 'POST',
          url: `${apiReport}?id=${that.movie.id}&${params}`,
          processData: false,
          contentType: false
        })
          .done(function (res) {
            if (res.data) {
            } else if (res.message) {
            }
          })
          .fail(function (err) {})
          .always(() => {
            $('#report-modal').modal('hide');
          });
      });

      // 下载App
      $('.download-app').on('click touchstart', function downloadApk(event) {
        if ('ontouchstart' in document.documentElement) {
          // 移动端直接下载
          window.location.href = that.apkUrl;
        } else {
          // PC进入下载扫码页
          window.location.href = that.downloadPageUrl;
        }
      });
    });
  },
  methods: {
    // 点击集数播放
    clickEpisode(index) {
      this.currentEpisode = index + 1;
      this.source = this.series[index].url;
      console.log('clickEpisode.source', this.source);
    },
    // 播放下一集
    nextPicode() {
      if (this.series.length > this.currentEpisode) {
        this.currentEpisode++;
        this.source = this.series[this.currentEpisode - 1].url;
        console.log('nextPicode.source', this.source);
      }
    },
    toggleLike() {
      this.movie.isliked ? this.movie.like_count-- : this.movie.like_count++;
      this.movie.isliked = !this.movie.isliked;
    },
    // 点赞处理
    likeHandler() {
      if (!this.$user.token) {
        $('#login-modal').modal('toggle');
        return;
      }
      const that = this;
      let apiLike = this.apiLike ? this.apiLike : '/api/movie/toggle-like';
      this.toggleLike();
      window.axios
        .post(
          `${apiLike}`,
          {
            movie_id: that.movie.id,
            type: 'movies'
          },
          {
            headers: {
              token: that.$user.token
            }
          }
        )
        .then(function (response) {
          if (response && response.data) {
            that.noticeInfo = that.movie.isliked ? '视频已收入我的喜欢' : '';
          } else {
            that.toggleLike();
          }
        })
        .catch((e) => {
          that.toggleLike();
        });
    },
    toggleFavorite() {
      this.movie.isFan = !this.movie.isFan;
    },
    // 收藏处理
    favoriteHandler() {
      if (!this.$user.token) {
        $('#login-modal').modal('toggle');
        return;
      }
      const that = this;
      let apiFavorite = this.apiFavorite ? this.apiFavorite : '/api/movie/toggle-fan';
      this.toggleFavorite();
      window.axios
        .post(
          `${apiFavorite}`,
          {
            movie_id: that.movie.id,
            type: 'movies'
          },
          {
            headers: {
              token: that.$user.token
            }
          }
        )
        .then(function (response) {
          if (response && response.data) {
            that.noticeInfo = that.movie.isFan ? '视频已放到收藏夹' : '';
          } else {
            that.toggleFavorite();
          }
        })
        .catch((e) => {
          that.toggleFavorite();
        });
    },
    // 切换player side
    wideSwitchHandler() {
      this.wideSwitch = !this.wideSwitch;
    },
    toggleEditing() {
      if (this.$user.token) {
        this.editingVisible = !this.editingVisible;
      } else {
        $('#login-modal').modal('toggle');
      }
    },

    switchLine(index) {
      this.lineSelected = index;
      this.currentEpisode = 1; //切换后剧集数会不同，默认跳转到第一集
      this.series = this.movieData.play_lines[index].data;
      this.source = this.movieData.play_lines[index].data[0].url;
      this.playLineName = this.movieData.play_lines[index].name;
      console.log('切换线路后 this.series', this.series);
    },

    togglePlayLines() {
      this.playLinesVisible = !this.playLinesVisible;
    }
  },
  computed: {
    movieTitle() {
      return `${this.movie.name}${this.series.length > 1 ? ' 第' + this.currentEpisode + '集' : ''}`;
    },
    seriesName() {
      let serie = this.series[this.currentEpisode - 1];
      return serie ? serie.name : '';
    },
    downloadPageUrl() {
      // return !this.appDownload ? '/app' : this.appDownload;
      return this.appDownload ? this.appDownload : '/app';
    }
  },
  data() {
    return {
      movie: {},
      currentEpisode: 1,
      source: null,
      series: [],
      noticeInfo: '',
      wideSwitch: false,
      editingVisible: false,
      playLinesVisible: false,
      videoDuration: '',
      currentTime: '',
      playLines: null,
      lineSelected: 0,
      playLineName: '默认'
    };
  }
};
</script>

<style lang="scss">
.el-popover {
  padding: 0 !important;
  border: none !important;
}
.alert-wrap {
  position: absolute;
  width: 100%;
  z-index: 100;
  padding: 10px;
}
.video_desc {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items:center;
}
</style>
