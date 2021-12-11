<template>
  <div class="invite-card">
    <!-- 海报 html 元素 -->
    <div id="posterHtml">
      <img id="poster-bg" src="/images/bg_poster.png" />
      <div class="poster-container">
        <div class="poster" :v-if="postshow">
          <img id="poster-image" :src="posterImg" v-show="posterImg" />
          <img class="poster-image-placeholder" v-if="!posterImg" />
          <!-- 二维码 -->
          <div id="qrcodeImg" :v-if="postcode"></div>
          <!-- 教程UI -->
          <div class="description">我正在追，扫码打开一起看吧</div>
          <div class="content">
            <h2 class="title">{{ movie?.name }}</h2>
            <div class="info">
              {{ movie?.year }}·{{ movie?.country || '未知' }}·{{ movie?.type }}·共{{ movie?.count_series }}集
            </div>
          </div>
        </div>
        <div class="poster-bottom">
          <img class="logo" :src="appLogoUrl" />
          <div class="app-info">
            <h2>{{ appNameCN }}</h2>
            <p>{{ appSlogan }}</p>
          </div>
        </div>
      </div>
    </div>
    <!-- 海报最终合成图 -->
    <div id="myCanvas" style="display: none"></div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import html2canvas from 'html2canvas';
import QRCode from 'qrcodejs2';
import { getBase64Image } from '../utils/getBase64Image';
import Canvas2Image from '../utils/Canvas2Image';
import { HOST_NAME, APP_LOGO_URL, APP_NAME_CN, APP_SLOGAN } from '../config';

// 函数名longpress，参数为: 需长按元素的id、长按之后的逻辑函数func
function longpress(id, func) {
  var timeOutEvent;

  document.querySelector('#' + id).addEventListener('touchstart', function (e) {
    // 开启定时器前先清除定时器，防止重复触发
    clearTimeout(timeOutEvent);
    // 开启延时定时器
    timeOutEvent = setTimeout(function () {
      // 调用长按之后的逻辑函数func
      func();
    }, 100); // 长按时间为300ms，可以自己设置
  });

  document.querySelector('#' + id).addEventListener('touchmove', function (e) {
    // 长按过程中，手指是不能移动的，若移动则清除定时器，中断长按逻辑
    clearTimeout(timeOutEvent);
    /* e.preventDefault() --> 若阻止默认事件，则在长按元素上滑动时，页面是不滚动的，按需求设置吧 */
  });

  document.querySelector('#' + id).addEventListener('touchend', function (e) {
    // 若手指离开屏幕时，时间小于我们设置的长按时间，则为点击事件，清除定时器，结束长按逻辑
    clearTimeout(timeOutEvent);
  });
}

export default defineComponent({
  setup() {
    let postcode = true;
    let postshow = true;
    return {
      appLogoUrl: APP_LOGO_URL,
      appNameCN: APP_NAME_CN,
      appSlogan: APP_SLOGAN,
      postshow,
      postcode
    };
  },
  mounted() {
    //隐藏清空上一张海报
    let canvasNode = document.getElementById('myCanvas');
    canvasNode.style.display = 'none';
    canvasNode.innerHTML = '';

    //要封面图加载成功，才去生成海报
    document.getElementById('poster-image').onload = () => {
      let movieUrl = 'https://' + HOST_NAME + '/movie/' + this.movie.id;
      this.createQrcode(movieUrl);
      this.createPoster();
      (window as any).playerEvent('生成海报', this.movie.name, this.movie.id);
    };

    getBase64Image(this.movie?.cover)
      .then((res) => {
        this.posterImg = res;
      })
      .catch((error) => {
        console.log('error', error);
      });
  },

  props: {
    movie: {
      type: Object,
      default: {}
    }
  },

  methods: {
    createQrcode(text) {
      // 生成二维码
      const qrcodeImgEl = document.getElementById('qrcodeImg');
      qrcodeImgEl.innerHTML = '';
      let width = document.documentElement.clientWidth;
      // width = width * 0.32;
      width = 60;

      let qrcode = new QRCode(qrcodeImgEl, {
        width: width,
        height: width,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });
      qrcode.makeCode(text);
    },
    createPoster() {
      // 生成海报
      const vm = this;
      const domObj = document.getElementById('posterHtml');

      var scale = 1;
      var width = domObj.offsetWidth;
      var height = domObj.offsetHeight;

      var canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;

      //放大2倍宽高 避免渲染模糊
      var scale = 2;
      canvas.width = width * scale;
      canvas.height = height * scale;
      // canvas.getContext('2d').scale(scale, scale);

      var opts = {
        scale: scale,
        canvas: canvas,
        width: width,
        height: height,
        useCORS: true,
        allowTaint: false,
        logging: false,
        letterRendering: true
      };

      let _this = this;
      html2canvas(domObj, opts).then(function (canvas) {
        var context = canvas.getContext('2d');

        // 关闭图片抗锯齿
        // (context as any).mozImageSmoothingEnabled = false;
        // (context as any).webkitImageSmoothingEnabled = false;
        // (context as any).msImageSmoothingEnabled = false;
        // (context as any).imageSmoothingEnabled = false;

        var img = Canvas2Image.convertToPNG(canvas, canvas.width, canvas.height);
        vm.postshow = false;
        vm.postcode = false;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.borderRadius = '8px';
        img.id = 'posterIMG';

        //海报合并图替换
        let canvasNode = document.getElementById('myCanvas');
        canvasNode.innerHTML = ''; //清空上一张海报
        canvasNode.appendChild(img); //填充新的海报

        let movie = _this.movie;
        longpress('posterIMG', function () {
          (window as any).playerEvent('长按分享', movie.name, movie.id);
        });

        let togglePosterDom = () => {
          //隐藏原素材封面图+二维码
          let posterEl = document.getElementById('posterHtml');
          (posterEl as any).style.display = 'none';
          //展示可以长按保存的新海报
          let myCanvas = document.getElementById('myCanvas');
          (myCanvas as any).style.display = 'block';
        };
        togglePosterDom();

        // vm.$nextTick(togglePosterDom);
      });
    }
  },

  data() {
    return {
      posterImg: null
    };
  }
});
</script>

<style lang="scss" scoped>
.invite-card {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
#posterHtml {
  // width: 86%;
  // left: 7%;
  // max-width: 320px;
  height: 580px;
}
#myCanvas {
  width: 86%;
  max-width: 320px;
  border-radius: 8px;
}
.poster-container {
  width: 320px;
  display: flex;
  // justify-content: center;
  flex-direction: column;
  align-items: center;
}
.poster {
  width: 280px;
  padding: 15px;
  background: #fff;
  overflow: hidden;
  position: relative;
  border-radius: 10px;
  top: 25px;
}
#poster-bg {
  width: 100%;
  height: 392px;
  position: absolute;
  // filter: blur(10px);
  overflow: hidden;
  margin: 0 auto;
  height: 580px;
  // top: -5%;
  // padding-right: 15px;
  max-width: 320px;
  // border-radius: 8px;
  // top: 0px;
  // left: 7%;
}
#poster-image {
  width: 250px;
  height: 322px;
  // background: #f1f2f3;
  border-radius: 8px;
}

.poster-image-placeholder {
  width: 250px;
  height: 322px;
  background: #f1f2f3;
  border-radius: 8px;
}

#qrcodeImg {
  position: absolute;
  left: 15px;
  top: 310px;
  border: solid 5px #fff;
  border-top-right-radius: 4px;
}
.description {
  margin-left: 65px;
  padding: 15px 0px 15px 10px;
  font-size: 10px;
  color: #3f4457;
}
.content {
  padding-top: 5px;
  color: #f1f1f1;
  .title {
    font-size: 16px;
    font-weight: 500;
    color: #101010;
    overflow: hidden;
  }
  .info {
    margin-top: 6px;
    font-size: 10px;
    color: #9da4ad;
  }
}
.poster-bottom {
  position: relative;
  top: 55px;
  display: flex;
  align-items: center;
  .logo {
    width: 50px;
    height: 50px;
    border-radius: 5px;
  }
  .app-info {
    color: #fff;
    margin-left: 15px;
    h2 {
      font-size: 18px;
      font-weight: 500;
      margin-bottom: 5px;
    }
  }
}
</style>
