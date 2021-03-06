<?php
/**
 * Created by PhpStorm.
 * User: shenzhe
 * Date: 2016/10/14
 * Time: 11:06
 */

namespace ZPHP\Client\Rpc;

use ZPHP\Core\Config;

use ZPHP\Protocol\Request;

use ZPHP\Client\Sync\Http as HttpClient;

abstract class Http
{
    protected static $clients = [];
    protected static $configs = [];
    protected $uri;
    protected $timeOut;
    protected $api = '';
    protected $method = '';
    protected $sendParams = [];
    protected $startTime = 0;
    protected $sync = 1;

    protected $config = [];

    /**
     * Tcp constructor.
     * @param $host
     * @param $port
     * @param int $timeOut
     * @param array $config
     * @throws \Exception
     */
    public function __construct($host, $port = 80, $timeOut = 500, $config = array())
    {
        if (empty($timeOut) || $timeOut < 1) {
            $timeOut = 500;
        }
        $this->uri = $host . ':' . $port;
        $this->timeOut = $timeOut;
        if (empty($config)) {
            $config = [
                'ctrl_name' => 'a',
                'method_name' => 'm',
            ];
        } else {
            $config = $config + [
                    'ctrl_name' => 'a',
                    'method_name' => 'm',
                ];
        }
        $this->api = Config::getField('project', 'default_ctrl_name');
        $this->config = $config;
        return true;
    }

    public function setApi($api)
    {
        $this->api = $api;
        return $this;
    }

    public function noSync()
    {
        $this->sync = 0;
        return $this;
    }

    public function getClient()
    {
        return null;
    }

    public function isConnected()
    {
        return true;
    }

    abstract function pack($sendArr);

    abstract function unpack($result);

    /**
     * @param $method
     * @param array $params
     * @return string
     * @desc 远程rpc调用
     */
    public function call($method, $params = [])
    {
        Request::setRequestId();
        $this->startTime = microtime(true);
        $this->method = $method;
        $this->sendParams = [
            '_recv' => $this->sync,
            $this->config['method_name'] => $method,
        ];
        if ($this->api) {
            $this->sendParams[$this->config['ctrl_name']] = $this->api;
        }
        $this->sendParams += $params;
        $result = $this->rawCall($this->pack($this->sendParams));
        return $this->unpack($result);
    }

    /**
     * @param $sendData
     * @return string
     * @throws \Exception
     * @desc 直接发送原始远程rpc调用
     */
    public function rawCall($sendData)
    {
        return HttpClient::getByUrl($this->uri, null, 'GET', $sendData, $this->timeOut, Request::getHeaders(), 1);
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments[0])) {
            $arguments[0] = [];
        } elseif (!is_array($arguments[0])) {
            throw new \Exception('arguments[0] must array');
        }
        return $this->call($name, $arguments[0]);
    }
}