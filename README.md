# haxibiao/media

> haxibiao/media 是哈希表内部媒体资源库

- Image 图片
- Video 视频
- Movie 影视(长视频)

## 导语

### 1. 新增长视频能力

- movie:push 提交长视频内容到云(哈希云，内涵云)
- movie:sync 从云同步长视频内容

### 2. 内容系统增加媒体特性

- trait WithMedia

### 3. 用户系统增加媒体特性

- trait UseMedia

## 互相依赖

- haxibiao-content Video/Movie 如同 Article,作为内容载体需要内容系统相同的能力
- haxibiao-cms Video/Movie 站群化，seo 优化

## 安装步骤

1. `composer.json`改动如下：
   在`repositories`中添加 vcs 类型远程仓库指向
   `http://code.haxibiao.cn/packages/haxibiao-media`
2. 执行`composer require haxibiao/media`
3. 执行`php artisan media:install && composer dump`
4. 给 app/User.php 添加 use UseMedia
5. 执行`php artisan migrate`
6. 完成

### 更新日志

### **1.2**

_Released on 2020-09-11_

-   修复 VOD 签名路由 ([#bc8b5ae6](http://code.haxibiao.cn/packages/haxibiao-media/commit/bc8b5ae69ff17885ed1236f8dd53316fc2545c47))
-   修复 image path 与 model 重名导致 nova 报错 ([#ea9fc9eb](http://code.haxibiao.cn/packages/haxibiao-media/commit/ea9fc9eb6aac8fc419b88322496b9da29c8f56a5))

### **1.1**

_Released on 2020-09-01_

-   修复 Api 路由注册失效的问题
-   为完成抖音无水印采集,提供接口能根据 hash 值获取视频的 qcvod_fileid
-   图片与模型的关系改为多态多对多
-   为方便工厂系项目集成,加入数据修复脚本 `ImageReFactoringCommand` 完成数据修复
-   为了减少回调,数据库保存抖音采集的信息
-   增加静态模型绑定,解决子类无法触发父类事件以及 Model 的扩展性问题

### 如何完成更新？

> 远程仓库的 composer package 发生更新时如何进行更新操作呢？

1. 执行`composer update haxibiao/media`
2. 执行`php artisan media:install`

## GQL 接口说明

graphql 部分代码还没统一... install 之后需要自行维护

## Api 接口说明
