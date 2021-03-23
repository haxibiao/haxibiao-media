<template>
    <div :class="['movie-player', wideSwitch && 'player_container_wide']">
        <div class="player-main">
            <div class="player-container player-fixed">
                <!-- <a class="player-fixed-off" href="javascript:;" style="display: none;"><i class="iconfont icon-close"></i></a> -->
                <div class="embed-responsive">
                    <div class="video-player">
                        <div class="fluid_video_wrapper">
                            <template v-if="source">
                                <video-player
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
                        </div>
                    </div>
                </div>
            </div>
            <ul class="player__operate clearfix">
                <li class="fl operation hide-xs">
                    <a
                        :class="['favorite', movie.isFan && 'highlight']"
                        href="javascript:void(0);"
                        v-on:click="favoriteHandler"
                    >
                        <i class="iconfont icon-collection-fill"></i>
                        <span>{{ movie.isFan ? '已收藏' : '收藏' }}</span>
                    </a>
                </li>
                <li class="fl operation">
                    <a
                        :class="['like', movie.isliked && 'highlight']"
                        href="javascript:void(0);"
                        v-on:click="likeHandler"
                    >
                        <i class="iconfont icon-good-fill"></i>
                        <span class="num">{{ movie.like_count || 0 }}</span>
                    </a>
                </li>
                <li class="fl operation" data-toggle="modal" data-target="#report-modal">
                    <a href="javascript:void(0);">
                        <i class="iconfont icon-warning-fill"></i>
                        <span class="mobile">举报</span>
                    </a>
                </li>
                <li class="fl operation download-app" dropdown-target=".download-app_qrcode" dropdown-toggle="hover">
                    <a :href="appDownloadUrl" target="_blank">
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
                <el-popover placement="bottom" trigger="manual" v-model="editingVisible" v-if="videoDuration">
                    <movie-editing
                        :apiClip="apiClip"
                        :movieId="movie.id"
                        :source="source"
                        :name="seriesName"
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
                </el-popover>
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
                    <div class="video_desc">
                        <div class="type">
                            {{ movie.score || Math.round(Math.max(6, Math.random() * 10)) + '.' + 0 }}分&nbsp;/&nbsp;
                            <a href="javascript:void(0);">{{ movie.region || '未知' }}</a
                            >&nbsp;/&nbsp;
                            <a href="javascript:void(0);">{{ movie.type_name || '未知' }}</a>
                        </div>
                    </div>
                </div>
                <div class="video_playlist" id="playlist">
                    <ul class="panel-content__list">
                        <li
                            :class="{
                                'col-xs-2': series.length > 1,
                                'col-xs-3': !(series.length > 1),
                                'col-sm-2': !(series.length > 1),
                                'col-lg-6': !(series.length > 1),
                            }"
                            v-for="(media, index) in series"
                            :key="media.id"
                        >
                            <a
                                href="javascript:void(0)"
                                :class="[
                                    'btn-episode',
                                    currentEpisode == index + 1 && 'active',
                                    !(series.length > 1) && 'btn-md',
                                ]"
                                v-on:click="clickEpisode(index)"
                            >
                                {{
                                    series.length > 1
                                        ? index + 1
                                        : media.name.length > 2
                                        ? media.name
                                        : movie.name + media.name
                                }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import 'element-ui/lib/theme-chalk/popover.css';
import 'element-ui/lib/theme-chalk/loading.css';
import 'element-ui/lib/theme-chalk/message.css';
import moment from '../../common/moment';

export default {
    props: [
        'movieData',
        'initEpisode',
        'qrcode',
        'appDownload',
        'apiDanmu',
        'apiSaveProgress',
        'apiGetProgress',
        'apiClip',
        'apiLike',
        'apiFavorite',
    ],

    created() {
        if (this.initEpisode) {
            this.currentEpisode = Number(this.initEpisode) + 1;
        }
    },
    mounted() {
        if (this.movieData !== null && typeof this.movieData === 'object') {
            this.movie = this.movieData;
            this.series = this.movie.series || [];
            console.log('this.series', this.series);
            this.source = this.$optional(this.series, `${this.currentEpisode - 1}.url`);
            console.log('this.source', this.source);
        }

        const that = this;
        let apiReport = this.apiReport ?? '/api/movie/report';
        this.$nextTick(function() {
            // 举报视频submit事件
            $('#report-modal .btn-submit').on('click', function reportSubmit(event) {
                const params = $('#report-form').serialize();
                if (!params) return;
                $.ajax({
                    type: 'POST',
                    url: `${apiReport}?id=${that.movie.id}&${params}`,
                    processData: false,
                    contentType: false,
                })
                    .done(function(res) {
                        if (res.data) {
                        } else if (res.message) {
                        }
                    })
                    .fail(function(err) {})
                    .always(() => {
                        $('#report-modal').modal('hide');
                    });
            });
            //   下载App
            $('.download-app').on('click touchstart', function downloadApk(event) {
                if ('ontouchstart' in document.documentElement) {
                    // 移动端直接下载
                    window.location.href = this.appDownloadUrl;
                } else {
                    window.location.href = this.appDownloadUrl;
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
            let apiLike = this.apiLike ?? '/api/movie/toggle-like';
            this.toggleLike();
            window.axios
                .post(
                    `${apiLike}`,
                    {
                        movie_id: that.movie.id,
                        type: 'movies',
                    },
                    {
                        headers: {
                            token: that.$user.token,
                        },
                    }
                )
                .then(function(response) {
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
            let apiFavorite = this.apiFavorite ?? '/api/movie/toggle-fan';
            this.toggleFavorite();
            window.axios
                .post(
                    `${apiFavorite}`,
                    {
                        movie_id: that.movie.id,
                        type: 'movies',
                    },
                    {
                        headers: {
                            token: that.$user.token,
                        },
                    }
                )
                .then(function(response) {
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
    },
    computed: {
        movieTitle() {
            return `${this.movie.name}${this.series.length > 1 ? ' 第' + this.currentEpisode + '集' : ''}`;
        },
        seriesName() {
            return this.series[this.currentEpisode - 1].name;
        },
        appDownloadUrl() {
            return this.appDownload ?? '/app';
        },
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
            videoDuration: '',
            currentTime: '',
        };
    },
};
</script>

<style lang="scss">
.el-popover {
    padding: 0 !important;
    border: none !important;
}
</style>
