<?php 
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;

// gateway
$gateway = new Gateway("Websocket://0.0.0.0:3232");

$gateway->name = 'SenderGateway';

$gateway->count = 4;

$gateway->lanIp = '127.0.0.1';

$gateway->startPort = 48000;

$gateway->pingInterval = 10;

$gateway->pingData = '{"type":"ping"}';

/*
 // 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
$gateway->onConnect = function($connection)
{
    $connection->onWebSocketConnect = function($connection , $http_header)
    {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['SERVER_NAME']标识来自哪个站点的页面发起的websocket链接
        if($_SERVER['SERVER_NAME'] != 'chat.workerman.net')
        {
            $connection->close();
        }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
    };
};
*/

// #### 内部推送端口(假设内网ip为192.168.100.100) ####
$internal_gateway = new Gateway("Text://127.0.0.1:7273");
$internal_gateway->name='internalGateway';
$internal_gateway->startProt = 2800;
// #### 内部推送端口设置完毕 ####

// bussinessWorker
$worker = new BusinessWorker();

$worker->name = 'SenderBusinessWorker';

$worker->count = 4;


// WebServer
$web = new WebServer("http://0.0.0.0:3333");

$web->count = 2;

$web->addRoot('www.your_domain.com', __DIR__.'/Web');
