<?php 
namespace mayunxuan\mailerqueue;
use Yii;
/**
* 
*/
class MailerQueue extends \yii\swiftmailer\Mailer
{
	//调用自己写的message类
	public $messageClass = "mayunxuan\mailerqueue\Message";

	public $key = "mail";

	public $db ='1';

	public function process(){
		//1判断redis是否存在
		$redis = Yii::$app->redis;
		if(empty($redis)){
            throw new \yii\base\InvalidConfigException("redis not found in cofing");            
		}
		//2.选择redis数据库，查队列，遍历发送邮件,出队
		if($redis->select($this->db) && $messages=$redis->lrange($this->key,0,-1)){
			$messageObj = new Message;
			foreach ($messages as $message) {
				$message = json_decode($message,true);
				if(empty($message)){
					throw new \yii\web\ServerErrorHttpException("send error");
				}
				$messsageObj=$this->setMessage($messageObj,$message);

				if($messsageObj !== false){
					if($messageObj->send()){
						//lrem移除类表中含有的值。第一个参数是key，第二个参数小于0从表头开始找，大于0从表尾找，第三个参数是要移除的值
						$redis->lrem($this->key,-1,json_encode($message));
				    }
				}
	
			}
		}
		return true;
	}

	public function setMessage($messageObj,$message){
		if(empty($messageObj)){
			return false;
		}
		if(!empty($message["from"]) && !empty($message["to"])){
			$messageObj->setFrom($message['from'])->setTo($message['to']);
			if(!empty($message["cc"])){
				$messageObj->setCc($message["cc"]);
			}
			if(!empty($message["bcc"])){
				$messageObj->setBcc($message["bcc"]);
			}
			if(!empty($message["reply_to"])){
				$messageObj->setReplyTo($message["reply_to"]);
			}
			if(!empty($message["charset"])){
				$messageObj->setCharset($message["charset"]);
			}
			if(!empty($message["subject"])){
				$messageObj->setSubject($message["subject"]);
			}
			if(!empty($message["html_body"])){
				$messageObj->setHtmlBody($message["html_body"]);
			}
			if(!empty($message["text_body"])){
				$messageObj->setTextBody($message["text_body"]);
			}
			return $messageObj;
		}
		return false;
		
	}
}