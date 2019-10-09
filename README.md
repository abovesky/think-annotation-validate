# think-annotation-validate
TP5.1注解验证器，含参数中间件封装，路由参数验证中间件，方法注释参数提取器，基于`wangyu/reflex-core`扩展
## 安装

```bash
composer require abovesky/think-annotation-validate
```

## 快速入门

### 1. `middleware.php` 中间件注册

> 文件地址：config/middleware.php

内容：
```php
<?php

return [
    // 默认中间件命名空间
    'default_namespace' => 'app\\http\\middleware\\',
    'Validate' => Abovesky\Annotation\Validate\Validate::class,
];

```

### 2. 方法注释中使用 `@validate()`函数 或 `@param()`


- 函数说明

| @函数名 | 解释 | 格式 | 函数参数说明 |
| :---: | :---: | :---: | :---: |
| validate | 注释验证器函数| @validate('name') | name: 验证器名称 |
| param | 注释参数函数| @param('name','doc','rule') | name: 参数名称，doc: 解释, rule: 规则 |


- @validate函数说明
> @validate()函数，需要在`application/api/validate`目录下创建验证器。
或者：创建 `config/validate.php` 配置文件，内容为：
```php
<?php 
return [
    // 默认验证器路径
    'validate_root_path' => 'api/validate',
];
```

- @param函数说明

> @param函数与@validate,作者优选@validate，希望如果每个方法里只用一种验证方式


### 3. 在路由配置`route/route.php`配置路由时，加上`middleware()`

例如：

```php

<?php
use think\facade\Route;

Route::group('', function () {
    Route::group('v1', function () {
        // 查询所有图书
        Route::post('book/', 'api/v1.Book/create');
    });
})->middleware(['Validate'])->allowCrossDomain();
```

### 4. 通过`postman`访问测试效果

- 先确定下，注释验证函数是否启用

```php
/**
 * @doc('创建图书')
 * @route('','post')
 * @validate('CreateGroup')
 * @param('name','图书名称','require|graph|length:1,50')
 * @param('img','图书img','require|graph|length:1,16')
 * @return array
 */
public function create()
{
    return json(['msg'=>'创建成功'],200);
}
```
