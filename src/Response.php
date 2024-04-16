<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use think\App;
use think\exception\HttpResponseException;
use think\Response as tpResponse;

trait Response
{

    /**
     * Request实例.
     *
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例.
     *
     * @var \think\App
     */
    protected $app;

    /**
     * 构造方法.
     *
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
    }

    /**
     * 操作成功跳转的快捷方法.
     *
     * @param mixed $msg 提示信息
     * @param string $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param int $wait 跳转等待时间
     * @param array $header 发送的Header信息
     *
     * @return tpResponse
     */
    protected function success($data = '', $msg = 'success', string $url = null, int $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string) $this->app->route->buildUrl($url);
        }

        $result = [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        return tpResponse::create($result, "json")->header($header);
    }

    /**
     * 操作错误跳转的快捷方法.
     *
     * @param mixed $msg 提示信息
     * @param string|null $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param int $wait 跳转等待时间
     * @param array $header 发送的Header信息
     *
     * @return tpResponse
     */
    protected function error($msg = '', string $url = null, $data = '', int $wait = 3, array $header = [])
    {
        if (is_null($url)) {
            $url = $this->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string) $this->app->route->buildUrl($url);
        }
        $result = [
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        return tpResponse::create($result, "json")->header($header);
    }

    /**
     * 返回封装后的API数据到客户端.
     *
     * @param mixed $data 要返回的数据
     * @param int $code 返回的code
     * @param mixed $msg 提示信息
     * @param string $type 返回数据格式
     * @param array $header 发送的Header信息
     *
     * @return tpResponse
     */
    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ];
        $type = $type ?: "json";

        return tpResponse::create($result, $type)->header($header);

    }

    /**
     * URL重定向.
     *
     * @param string $url 跳转的URL表达式
     * @param int $code http code
     * @param array $with 隐式传参
     *
     * @return void
     */
    protected function redirect($url, $code = 302, $with = [])
    {
        $response = tpResponse::create($url, 'redirect');
        $response->code($code)->with($with);
        throw new HttpResponseException($response);
    }
}
