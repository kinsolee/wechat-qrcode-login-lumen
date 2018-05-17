<?php
/**
 * Created by PhpStorm.
 * User: kinso
 * Date: 2018/5/16
 * Time: 下午10:35
 */

namespace Forchange\Wechat\QrcodeLogin;
use Illuminate\Support\ServiceProvider;

class WechatQrcodeLoginServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        $events = app('events');
        $events->listen('');
    }
}