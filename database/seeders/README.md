# 说明

## 1. 注意默认 namespace

> Database\Seeders;

## 2. git模块方式加载注意

记得修改composer.json 文件的 classmap 添加例如breeze模块的

``` "autoload-dev": {
        "classmap": [
            "packages/haxibiao/breeze/database/seeders"
        ]
    }
