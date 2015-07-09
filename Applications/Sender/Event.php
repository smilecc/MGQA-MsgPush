<?php
/**
 * 
 * 主逻辑
 * 主要是处理 onMessage onClose 方法
 * @author walkor <walkor@workerman.net>
 * 
 */

use \GatewayWorker\Lib\Gateway;

class Event
{
   /**
    * 有消息时
    * @param int $client_id
    * @param string $message
    */
   public static function onMessage($client_id, $message)
   {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        // redis
        $redis = new Redis();
        $redis->connect("127.0.0.1","6379");    
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        
        // 根据类型做相应的业务逻辑
        switch($message_data['type'])
        {
            case 'login':
                $redis->sadd($message_data['name'],$client_id); // use to Push
                $redis->set($client_id,$message_data['name']); // use to Close
            break;
            // 发送数据给用户 message: {type:send, to_client_id:xx, content:xx}
            case 'new-msg':
                $new_message = array(
                            'type'=>'new-msg',
                            'tousname'=>$message_data['tousname'],
                            'numb'=>$message_data['numb'],
                            'time'=>date('Y-m-d :i:s'),
                    );
                print_r($redis->sMembers($message_data['tousname']));
                return Gateway::sendToAll(json_encode($new_message),$redis->sMembers($message_data['tousname']));
            break;
            case 'new-info':
                $new_message = array(
                            'type'=>'new-info',
                            'time'=>date('Y-m-d :i:s'),
                    );
                // 取得发送用户集合
                $send_user_arr = $redis->sMembers('info-'.$message_data['lock']);
                print_r($redis->sMembers('info-'.$message_data['lock']));
                // 取得发送用户客户端总集合
                $send_arr = array();
                foreach ($send_user_arr as $value) {
                    $send_arr = array_merge_recursive($send_arr,$redis->sMembers($value));
                }
                // 发送
                $send_result = Gateway::sendToAll(json_encode($new_message),$send_arr);
                // 解锁
                $redis->delete('info-lock-'.$message_data['lock']);
                return $send_result;
            break;
            case 'send':
                // 向某个浏览器窗口发送消息
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                            'type'=>'send',
                            'from_client_id'=>$client_id,
                            'to_client_id'=>$message_data['to_client_id'],
                            'content'=>nl2br($message_data['content']),
                            'time'=>date('Y-m-d h:i:s'),
                    );
                    return Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
                }
                // 向所有浏览器发送消息
                $new_message = array(
                        'type'=>'send',
                        'from_client_id'=>$client_id,
                        'to_client_id'=>'all',
                        'content'=>nl2br($message_data['content']),
                        'time'=>date('Y-m-d :i:s'),
                );
                return Gateway::sendToAll(json_encode($new_message));
        }
   }
   
   /**
    * 当用户断开连接时
    * @param integer $client_id 用户id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
                $redis = new Redis();
                $redis->connect("127.0.0.1","6379");
                $redis->srem($redis->get($client_id),$client_id); // remove Push sets
                $redis->del($client_id);
   }
}
