<template>
    <div id="dplayer"></div>
</template>
<script>
import Hls from 'hls.js';
import DPlayer from 'dplayer';
import moment from '../../common/moment';

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
        'apiGetProgress',
    ],
    mounted() {
        if (Hls.isSupported()) {
            console.log('hello hls.js isSupported!');
        }

        let options = {
            container: document.getElementById('dplayer'),
            preload: true,
            autoplay: true,
            screenshot: true,
            video: {
                url: this.source,
                type: 'hls',
            },
            pluginOptions: {
                hls: {},
            },
        };

        console.log('this.apiDanmu', this.apiDanmu ? true : false);
        // options.danmaku = this.apiDanmu ? true : false;
        if (this.apiDanmu) {
            options.danmaku = {
                id: this.movie_id + '_' + this.series_index,
                user: this.getUserId(),
                api: '/api/movie/danmu/',
                token: this.episode, //是series 的 index
            };
        }
        this.player = new DPlayer(options);

        // 绑定事件，监听页面刷新or关闭
        window.addEventListener('beforeunload', this.beforeunloadFn);
        this.bindVideoSourceUpdate();
        // 跳转到上次观看
        this.jumpHistory();
        // 自动播放下一集
        if (this.player) {
            this.player.on('ended', () => {
                this.$emit('playEnded');
            });
            this.player.on('webfullscreen', () => {
                window.postMessage('fullscreen');
            });
            this.player.on('webfullscreen_cancel', () => {
                window.postMessage('fullscreen_cancel');
            });
            this.player.on('loadeddata', () => {
                const duration = moment.format(this.player.video.duration);
                this.$emit('update:videoDuration', duration);
            });
            this.player.on('seeking', () => {
                const currentTime = moment.format(this.player.video.currentTime);
                this.$emit('update:currentTime', currentTime);
            });
            document.addEventListener('message', this.onWebViewMessage);
        }
        if (this.apiDanmu) {
            //校验弹幕发送规则
            this.bindDanmuSend();

            //绘制scoket弹幕
            let danmuPrefix = this.apiDanmu ? this.apiDanmu : 'danmu_';
            var channel = danmuPrefix + this.movie_id + '_' + this.episode;
            Echo.channel(channel).listen('DanmuEvent', (e) => {
                const danmu = {
                    text: e.content,
                    color: '#FFF',
                    type: e.type,
                };
                //绘制弹幕 ,但是不对发送弹幕者进行二次绘制弹幕
                if (this.getUserId() != null && this.getUserId() == e.user_id) {
                    return;
                }

                this.player.danmaku.draw(danmu);
            });
        }
    },
    updated() {},
    beforeDestroy() {
        if (this.player) {
            this.player.destroy();
        }
        // 卸载事件
        window.removeEventListener('beforeunload', this.beforeunloadFn);
        window.document.removeEventListener('message');
    },
    watch: {
        source(newV, oldV) {
            console.log('开始播放 url:' + newV);
            if (this.player) {
                this.player.switchVideo({
                    url: newV,
                });
                this.player.play();
            }
        },
        notice(newV, oldV) {
            if (this.player && newV) {
                this.player.notice(newV, '3000');
            }
        },
    },
    methods: {
        //保存历史观看时长
        saveUserHistory() {
            var that = this;
            if (!this.apiSaveProgress) {
                return;
            }
            window.axios
                .post(
                    this.apiSaveProgress ? this.apiSaveProgress : `/api/movie/save-watch_progress`,
                    {
                        movie_id: this.movie_id,
                        series_id: this.series[this.episode - 1].id,
                        progress: this.player.video.currentTime,
                    },
                    {
                        headers: {
                            token: that.$user.token,
                        },
                    }
                )
                .then(function(response) {
                    if (response && response.data) {
                        console.log(response);
                    }
                })
                .catch((e) => {});
        },
        //获取历史观看时长
        jumpUserHistory() {
            var that = this;
            if (!this.apiGetProgress) {
                return;
            }
            window.axios
                .post(
                    this.apiGetProgress ? this.apiGetProgress : `/api/movie/get-watch_progress`,
                    {
                        movie_id: that.movie_id,
                    },
                    {
                        headers: {
                            token: that.$user.token,
                        },
                    }
                )
                .then(function(response) {
                    if (response.data.status_code == 200) {
                        //返回历史观看时长
                        const history = response.data.data;
                        console.log(history);
                        // 跳转上次观看的集数
                        that.$emit('update:source', history.source);
                        that.$emit('update:episode', history.episode);
                        // 跳转到上次观看的时间
                        that.player.seek(history.time);
                        that.player.notice('上次观看到:' + that.secondToDate(history.time), '5000');
                    }
                });
        },
        getUserId() {
            if (Object.keys(this.$user).length != 0) {
                return this.$user.id;
            }
        },
        //TODO:这里有一个历史问题就是之前没有传入参数series_id 所以使用第几集从后端算出来series_id 有空重构
        getSeiresIndex() {
            var history = this.getCookieValue(this.movie_id);
            if (typeof history == 'undefined' || history == null || history == '') {
                return this.episode;
            }
            var series_index = JSON.parse(history).episode;
            return series_index;
        },
        //绑定弹幕发送事件，验证是否登录 发送弹幕长度
        bindDanmuSend() {
            var that = this;
            this.player.on('danmaku_send', function(danmu) {
                //调用一下评论接口，如果没有登录则打回来叫用户登录
                if (Object.keys(that.$user).length === 0) {
                    that.player.notice('您还没有登录，请登录后愉快的发送吧~', '5000');
                    //隐藏弹幕的发送：因为Dplayer无论失败或成功 都会绘制一次弹幕直接隐藏这次操作
                    that.player.danmaku.hide();
                    return false;
                }
            });
        },
        //绑定播放源更新事件
        bindVideoSourceUpdate() {
            var that = this;
            this.player.on('loadeddata', function() {
                this.series_index = that.episode;
            });
        },
        // 获取cookie中指定key的数据
        getCookieValue(name) {
            let result = document.cookie.match('(^|[^;]+)\\s*' + name + '\\s*=\\s*([^;]+)');
            return result ? result.pop() : '';
        },
        // 跳转到上次观看的集数和时长
        jumpHistory() {
            var history;
            //如果是登录用户，优先获取数据库存储的当前剧集的历史观看时长
            if (this.getUserId() > 0) {
                this.jumpUserHistory();
            } else {
                // 获取历史观看记录的信息
                history = this.getCookieValue(this.movie_id);
                // 如果为空 直接返回
                if (typeof history == 'undefined' || history == null || history == '') {
                    return;
                }
                history = JSON.parse(history);
                // 跳转上次观看的集数
                this.$emit('update:source', history.source);
                this.$emit('update:episode', history.episode);
                // 跳转到上次观看的时间
                this.player.seek(history.time);
                this.player.notice('上次观看到:' + this.secondToDate(history.time), '5000');
            }
        },
        beforeunloadFn(e) {
            // 从cookie 获取当前用户播放当前电影的多少集 的多少分钟
            if (this.getUserId() > 0) {
                this.saveUserHistory();
            } else {
                const history = {
                    movieId: this.movie_id,
                    time: this.player.video.currentTime,
                    episode: this.episode,
                    source: this.source,
                };
                document.cookie = this.movie_id + '=' + JSON.stringify(history);
            }
        },
        secondToDate(seconds) {
            var result = '';
            var h = Math.floor(seconds / 3600);
            var m = Math.floor((seconds / 60) % 60);
            var s = Math.floor(seconds % 60);
            if (h > 0) {
                result += h + ':';
            }
            return (result += m + ':' + s);
        },
        onWebViewMessage(event) {
            if (event.data == 'fullscreen_cancel') {
                this.player.fullScreen.cancel('web');
            }
        },
    },
    data() {
        return {
            series_index: this.getSeiresIndex(),
        };
    },
};
</script>

<style lang="scss" scoped>
#dplayer {
    width: 100%;
    height: 100%;
}
</style>
