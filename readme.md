# haxibiao/media

> haxibiao/media 是哈希表内部媒体资源库(Image,Video)
> 

## 导语


## 环境要求
1. 还没完全检查完表结构差异，Video多了属性的，Spider在Article里实现的，需要先重构再兼容。

## 安装步骤

1. `composer.json`改动如下：
在`repositories`中添加 vcs 类型远程仓库指向 
`http://code.haxibiao.cn/packages/haxibiao-media` 
1. 执行`composer require haxibiao/media`
2. 执行`php artisan media:install && composer dump`
3. 给app/User.php 添加 use WithMedia
4. 执行`php artisan migrate`
5. routes/api.php 需要 require_once 'api/media.php'来提供rest api
6. 完成

### 如何完成更新？
> 远程仓库的composer package发生更新时如何进行更新操作呢？
1. 执行`composer update haxibiao/media`
2. 执行`php artisan media:install`

## GQL接口说明
graphql 部分代码还没统一... install之后需要自行维护

## Api接口说明
