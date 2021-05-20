<template>
    <div class="movie-editing">
        <div class="editing-panel">
            <div class="panel-wrap">
                <div class="editing-item">
                    <div class="editing-item-head">
                        <span class="editing-item-head-text">切换线路</span>
                        <span class="editing-item-head-more" @click="onClose">
                            <i class="iconfont icon-close"></i>
                        </span>
                    </div>
                    <div class="editing-type">
                        <el-select v-model="playLine" placeholder="默认线路" prefix-icon="el-icon-timer">
                            <el-option
                                v-for="item in playLines"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            >
                            </el-option>
                        </el-select>
                    </div>
                    <div class="editing-button">
                        <el-button type="primary" v-on:click="submit">切换线路</el-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['movieId', 'lines'],
    methods: {
        onClose() {
            this.$emit('onClose');
        },
        submit() {
            this.$emit('onSwitchLine', this.playLine);
        },
    },

    data() {
        return {
            playLine: null,
            playLines: [
                {
                    value: '内涵云',
                    label: '内涵云',
                },
                {
                    value: '韩剧线路',
                    label: '韩剧线路',
                },
            ],
        };
    },
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
