<?php
/**
 * Created by PhpStorm.
 * User: kinso
 * Date: 2018/5/17
 * Time: 下午11:32
 */

namespace Forchange\Wechat\QrcodeLogin;

use EasyWeChat\OfficialAccount\Server\Guard;

interface GuardAccessible
{
    /**
     * 必须返回Guard对象
     * @return Guard
     */
    public function getGuard();
}