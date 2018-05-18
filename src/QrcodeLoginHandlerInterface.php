<?php
/**
 * Created by PhpStorm.
 * User: kinso
 * Date: 2018/5/17
 * Time: 下午5:11
 */

namespace Forchange\Wechat\QrcodeLogin;

interface QrcodeLoginHandlerInterface
{
    /**
     * 用户扫码后执行的操作
     * @param $wx_info
     * @return mixed
     */
    public function scanned($wx_info);

    /**
     * 添加jwt payload的数据
     * @param $wx_info
     * @return array
     */
    public function generateJwtPayload($wx_info);

    public function statusDataAppend($wx_info);
}