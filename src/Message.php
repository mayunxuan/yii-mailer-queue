<?php 
namespace mayunxuan\mailerqueue;
use Yii;
/**
* 
*/
class Message extends \yii\swiftmailer\Message
{
	/**
	 *思路：把发送邮件的信息保存到redis队列当中
	 * 
	 */
	public function queue(){
		//1判断redis是否存在
		$redis = Yii::$app->redis;
		if(empty($redis)){
            throw new \yii\base\InvalidConfigException("redis not found in cofing");            
		}
		//2.redis数据库 0-15 检查数据库是否存在
		$mailer = Yii::$app->mailer;
		if(empty($mailer) || !$redis->select($mailer->db)){
			throw new \yii\base\InvalidConfigException("redis db not set ");   
		}
		//3.收集邮件信息
		$message = [];
		$message["from"] = array_keys($this->getFrom());
		$message["to"] = array_keys($this->getTo());
		$message["cc"] = array_keys($this->getCc());
		$message["bcc"] = array_keys($this->getBcc());
		$message["reply_to"] = array_keys($this->getReplyTo());
		$message["charset"] = array_keys($this->getCharset());
		$message["subject"] = array_keys($this->getSubject());
		$parts = $this->getSwiftMessage()->getChildren();

		if(!is_array($parts) || !sizeof($parts)){
			$parts = [$this->getSwiftMessage()];
		}
		foreach ($parts as $part) {
			if(!$part instanceof \Swift_Mime_Attachment){
				switch ($part->getContentType()) {
					case 'text/html':
						$message["html_body"] = $part->getBody();
						break;
					
					case 'text/plain':
						$message["text_body"] = $part->getBody();
						break;
				}
				if(!$message["charset"]){
					$message["charset"] = $part->getCharset();
				}
			}
		}
		//4.写入redis队列
		return Yii::$app->redis->rpush($mailer->key,json_encode($message));
	}

	// public function send($message)
 //    {
 //        if (!$this->beforeSend($message)) {
 //            return false;
 //        }

 //        $address = $message->getTo();
 //        if (is_array($address)) {
 //            $address = implode(', ', array_keys($address));
 //        }
 //        Yii::info('Sending email "' . $message->getSubject() . '" to "' . $address . '"', __METHOD__);

 //        if ($this->useFileTransport) {
 //            $isSuccessful = $this->saveMessage($message);
 //        } else {
 //            $isSuccessful = $this->sendMessage($message);
 //        }
 //        $this->afterSend($message, $isSuccessful);

 //        return $isSuccessful;
 //    }
}

?>