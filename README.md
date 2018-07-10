# wechat-qrcode-login-lumen
## 功能
微信扫码登录模块
使用微信场景值二维码，扫码关注事件实现，采用jwt进行鉴权

## 安装&使用

1. 项目引入composer包
```composer require kinsolee/wechat-qrcode-login-lumen```

2. 新建类实现```QrcodeLoginHandlerInterface```接口， 例：
```php
namespace App\Concretes;

use App\Models\User;
use Forchange\Wechat\QrcodeLogin\QrcodeLoginHandlerInterface;

class QrcodeLoginHandler implements QrcodeLoginHandlerInterface
{

    /**
     * 用户扫码后执行的操作
     * @param $wx_info
     * @return mixed
     */
    public function scanned($wx_info)
    {
        if (isset($wx_info['headimgurl']) && $wx_info['headimgurl'])
            $wx_info['headimgurl'] = str_replace('http://', 'https://', $wx_info['headimgurl']);

        User::updateOrCreate([
            'unionid' => $wx_info['unionid'],
        ], $wx_info);
    }

    /**
     * 添加jwt payload的数据
     * @param $wx_info
     * @return array
     */
    public function generateJwtPayload($wx_info)
    {
        $user = User::where(['unionid' => $wx_info['unionid']])->first();
        return [
            'id'  => $user->id,
            'oid' => $wx_info['openid'],
            'sub' => $wx_info['unionid']
        ];
    }
    public function statusDataAppend($wx_info)
    {
        return [
            'domain' => env('DOMAIN', 'wx.pandateacher.com'),
            'path'   => '/'
        ];
    }
}
```

3. 在wechat配置文件加入`qrcode_login_handler`配置，指定实现`QrcodeLoginHandlerInterface`的类



4. 在触发‘事件消息‘的event类实现`GuardAccessible`接口`getGuard`方法，返回guard对象。
5. 在`EventServiceProvider`里加入`Forchange\Wechat\QrcodeLogin\Listeners\PersistWxInfo::class`来指定步骤4中监听event的listener。

6. 注册路由，路由地址可以自定义：
```php
$router->group(['namespace' => '\Forchange\Wechat\QrcodeLogin\Controllers'], function ($router) {
    $router->get('user/login-wechat-qrcode', 'QrcodeLoginController@create');
    $router->get('user/login-wechat-qrcode-status', 'QrcodeLoginController@status');
});
```

接口返回说明：
QrcodeLoginController@create返回字段：

| 字段名 | 说明 |
|:---:|:---:|
|errcode|错误代码|
|data.qrcode_id|二维码ID|
|data.qrcode_url|二维码url|
|data.timeout|超时时间|

示例：
```json
{
    "errcode":0,
    "data":{
        "qrcode_id":"19e67c69-be5e-4f94-8648-fcd493a4bdc7",
        "qrcode_url":"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEy8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAybmQtTXNJU1E5c1UxWXdBWGhxMU4AAgRs4-taAwS0AAAA",
        "timeout":180
    }
}
```

QrcodeLoginController@status返回字段：

| 字段名 | 说明 |
|:---:|:---:|
|errcode|错误代码，|
|data|状态数据|
|data.jwt|生成的jwt|
|errmsg|错误信息|

>data里如需添加更多字段，可以实现QrcodeLoginHandlerInterface::statusDataAppend

示例：
```json
{
    "errcode":0,
    "data":{
        "jwt":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6NSwib2lkIjoibzhrVUlzNnllT0RYb0N5RW5YREtpVU9VTE5TVSIsInN1YiI6Im84NkM5c3lHaTRKWmVQUHBfbXdQSU90b2R2d2siLCJhcHAiOiJmZWF0dXJlX3BhbmRhY2xhc3NfYWJjIiwiZXhwIjoxNTI5MDMxMzE4LCJ2ZXIiOiJub2RlIn0.CajzV7hrRS_JlAfEsOK1mmU7OzHX864Y2Ylr1QVxYtQ",
        "domain":"/localhost",
        "path":"/"
    },
    "errmsg":"ok"
}
```




