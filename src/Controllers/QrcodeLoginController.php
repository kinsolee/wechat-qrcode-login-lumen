<?php
/**
 * Created by PhpStorm.
 * User: kinso
 * Date: 2018/5/17
 * Time: 下午2:59
 */

namespace Forchange\Wechat\QrcodeLogin\Controllers;

use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Firebase\JWT\JWT;
use Forchange\Wechat\QrcodeLogin\QrcodeLoginHandler;
use Forchange\Wechat\QrcodeLogin\QrcodeLoginHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Webpatser\Uuid\Uuid;

class QrcodeLoginController extends BaseController
{
    /**
     * @return array
     * @throws \Exception
     */
    public function create()
    {
        /** @var Application $app */
        $app = app('wechat.official_account');
        $uuid = (string)Uuid::generate();
        $expire_seconds = 1800;
        $prefix = config('wechat.qr-login-prefix',"qr-login");
        $result = $app->qrcode->temporary("{$prefix}:{$uuid}", $expire_seconds);
        $ticket = $result['ticket'] ?? '';
        $url = $app->qrcode->url($ticket);
        Cache::put("{$prefix}:{$uuid}", [
            'uuid' => $uuid
        ], $expire_seconds / 60);
        return [
            'qrcode_id'  => $uuid,
            'qrcode_url' => $url,
            'timeout'    => $expire_seconds
        ];
    }

    public function status()
    {
        $uuid = Request::input('qrcode_id');
        $prefix = config('wechat.qr-login-prefix',"qr-login");
        $data = Cache::get("{$prefix}:{$uuid}");
        if (!$data) {
            return ['errcode' => -2, 'errmsg' => "二维码已超时"];
        }
        $wx_info = $data['wx_info'] ?? [];
        if (!$wx_info) {
            return ['errcode' => -1, 'errmsg' => '等待扫码'];
        }
        $handler = config('wechat.qrcode_login_handler', QrcodeLoginHandler::class);
        $jwt_data = [
            'oid' => $wx_info['openid'] ?? '',
            'exp' => Carbon::now()->addMinutes(config('jwt.ttl', 60))->timestamp,
        ];
        if (is_subclass_of($handler, QrcodeLoginHandlerInterface::class)) {
            /** @var QrcodeLoginHandlerInterface $handler_instance */
            $handler_instance = app($handler);
            $jwt_data = array_merge($jwt_data, $handler_instance->generateJwtPayload($wx_info));
        }
        $jwt = JWT::encode($jwt_data, config('jwt.secret', 'secret_key'));
        return [
            'errcode' => 0,
            'jwt'     => $jwt,
            'errmsg'  => 'ok'
        ];
    }
}