<?php
/**
 * Created by PhpStorm.
 * User: kinso
 * Date: 2018/5/17
 * Time: 下午5:15
 */

namespace Forchange\Wechat\QrcodeLogin\Listeners;

use EasyWeChat\OfficialAccount\Application;
use EasyWeChat\OfficialAccount\Server\Guard;
use Forchange\Wechat\QrcodeLogin\GuardAccessible;
use Forchange\Wechat\QrcodeLogin\QrcodeLoginHandler;
use Forchange\Wechat\QrcodeLogin\QrcodeLoginHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PersistWxInfo
{
    public function handle(GuardAccessible $event)
    {
        Log::info("收到扫码，持久化微信信息！");
        /** @var Guard $server */
        $server = $event->getGuard();
        $msg = $server->getMessage();
        $msg_event = $msg['Event'] ?? '';
        if ($msg_event != 'subscribe' && $msg_event != 'SCAN') return true;//扫码关注事件
        $openid = $msg["FromUserName"];
        if (!isset($openid)) {
            return true;
        }
        $msg_event_key = $msg['EventKey'] ?? '';
        $event_key = str_replace('qrscene_', '', $msg_event_key);
        $prefix = config('wechat.qr-login-prefix', "qr-login");
        if (strpos($event_key, $prefix) === false) {
            return true;
        }
        $persist_data = Cache::get($event_key);
        if (!$persist_data) {
            return true;
        }
        try {
            //直接取Guard里的app属性
            $reflectionProperty = (new \ReflectionObject($server))->getProperty('app');
            $reflectionProperty->setAccessible(true);
            /** @var Application $app */
            $app = $reflectionProperty->getValue($server);
            $wx_info = $app->user->get($openid);
            $persist_data['wx_info'] = $wx_info;
            $expire_seconds = config('wechat.qr-login-expire', 180);
            Cache::put($event_key, $persist_data, $expire_seconds / 60);
            $handler = config('wechat.qrcode_login_handler', QrcodeLoginHandler::class);
            if (is_subclass_of($handler, QrcodeLoginHandlerInterface::class)) {
                app($handler)->scanned($wx_info);
            }
        } catch (\Exception $e) {
            Log::error('持久化微信用户信息失败：' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
        return true;
    }

}