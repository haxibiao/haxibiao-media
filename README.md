# haxibiao/media

> haxibiao/media 是哈希表内部媒体资源库(Image,Video)

## 导语

## 环境要求

1. 还没完全检查完表结构差异，Video 多了属性的，Spider 在 Article 里实现的，需要先重构再兼容。

## 安装步骤

1. `composer.json`改动如下：
   在`repositories`中添加 vcs 类型远程仓库指向
   `http://code.haxibiao.cn/packages/haxibiao-media`
2. 执行`composer require haxibiao/media`
3. 执行`php artisan media:install && composer dump`
4. 给 app/User.php 添加 use WithMedia
5. 执行`php artisan migrate`
6. 完成

### 更新日志
**1.1**

_Released on 2020-09-01_

- 修复Api路由注册失效的问题
- 为完成抖音无水印采集,提供接口能根据hash值获取视频的qcvod_fileid
- 图片与模型的关系改为多态多对多
- 为方便工厂系项目集成,加入数据修复脚本 `ImageReFactoringCommand` 完成数据修复
- 为了减少回调,数据库保存抖音采集的信息
- 增加静态模型绑定,解决子类无法触发父类事件以及Model的扩展性问题


### 如何完成更新？

> 远程仓库的 composer package 发生更新时如何进行更新操作呢？

1. 执行`composer update haxibiao/media`
2. 执行`php artisan media:install`

## GQL 接口说明

graphql 部分代码还没统一... install 之后需要自行维护

## Api 接口说明