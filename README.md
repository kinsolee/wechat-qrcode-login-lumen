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
}
```

3. 在wechat配置文件加入`qrcode_login_handler`配置，指定实现`QrcodeLoginHandlerInterface`的类
4. 在触发‘事件消息‘的event类实现`GuardAccessible`接口`getGuard`方法，返回guard对象。
5. 在`EventServiceProvider`里加入`Forchange\Wechat\QrcodeLogin\Listeners\PersistWxInfo::class`来指定步骤4中监听event的listener。