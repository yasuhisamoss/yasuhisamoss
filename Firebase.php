<?php

class Prcm_Service_Firebase
{

	// URL
	const NOTIFICATION_URL = 'https://fcm.googleapis.com/fcm/send';
	// アクセスキー
	const API_ACCESS_KEY	= 'アクセスキー書く';

	// レスポンス内のエラーの文字列表現
	const ERROR_RESPONSE_MISSING_REGISTRATION	= 'MissingRegistration';
	const ERROR_RESPONSE_INVALID_REGISTRATION	= 'InvalidRegistration';
	const ERROR_RESPONSE_INVALID_PACKAGE_NAME   = 'InvalidPackageName';	
	const ERROR_RESPONSE_INVALID_ID_TTL			= 'InvalidTtl';
	const ERROR_RESPONSE_INVALID_DATA_KEY		= 'InvalidDataKey';
	const ERROR_RESPONSE_MESSAGE_TOO_BIG        = 'MessageTooBig';
	const ERROR_RESPONSE_NOT_REGISTERED         = 'NotRegistered';
	const ERROR_RESPONSE_OTHERS					= 'others';

	// 文字列表現の配列
	private static $_error_status = array(
		self::ERROR_RESPONSE_MISSING_REGISTRATION => 1,
        self::ERROR_RESPONSE_INVALID_REGISTRATION => 2,
        self::ERROR_RESPONSE_INVALID_PACKAGE_NAME => 3,
        self::ERROR_RESPONSE_INVALID_ID_TTL		=> 4,
        self::ERROR_RESPONSE_INVALID_DATA_KEY	=> 5,
        self::ERROR_RESPONSE_MESSAGE_TOO_BIG	=> 6,
        self::ERROR_RESPONSE_NOT_REGISTERED		=> 7,
        self::ERROR_RESPONSE_OTHERS				=> 9,
	);

	private  $_target_token = array();

	// construct内でトークンをセットする
	public function __construct($target_tokens=array())
	{
	
		if (!$target_tokens || empty($target_tokens) ) 
		{
			return false;
		}
		else if (!is_array($target_tokens))
		{
			$target_token[] = $target_tokens;
			$target_tokens = $target_token;
		}
		$this->_target_token = $target_tokens;
	}

	// Push送信
	public  function send($title="defaultTitle", $body="defaultBody", $data=array())
	{
		$msg		= self::_set_message($title, $body);
		$responce	= self::_curl_request(self::_set_headers(), self::_set_fields($this->_target_token, $msg, $data));

		// レスポンスと送信トークンのマージ
		$res = self::_marge_response($responce, $this->_target_token);
		// debug
		$this->_outputFile($res);
		return $res;
	}

	// ここでレスポンスとトークンリストマージ。
	private static function _marge_response($responce=null, $tokens)
	{

		if (!$responce) return false;
		$margeResponse	= array();

		$responseArray	= json_decode($responce);
		if (count($responseArray->results)) 
		{
			foreach ($responseArray->results as $key => $res) 
			{
				$result	= array();
				if (isset($res->error)) 
				{
					if (isset(self::$_error_status[$res->error])) 
					{
						$result["error_status"] = self::$_error_status[$res->error];
					}
					else 
					{
						$result["error_status"] = self::$_error_status[self::ERROR_RESPONSE_OTHERS];
					}

					$result["message_id"] = 0;
				}
				else if ($res->message_id) 
				{
					$result["error_status"]	= 0;
					$result["message_id"]	= $res->message_id;
				}

				$result["token"] = $tokens[$key];
				$margeResponse[] = $result;
			}
		}
		return $margeResponse;
	}

	// メッセージ等のフィールドをセット
	private static function _set_fields($registrationIds, $msg, $data=array())
	{

		$push_notification_data = array(
									'registration_ids'  => $registrationIds,
				                    'notification'      => $msg,
								    'priority'          => 'high',
				                    'content_available' => true,
								  );

		if (!empty($data)) $push_notification_data["data"] = $data;

		return $push_notification_data;
	}

	// curl headerをセット
	private static function _set_headers()
	{
		return  array
				(
				    'Authorization: key=' . self::API_ACCESS_KEY,
				    'Content-Type: application/json',
				);
	}

	// fields内のnotificationの配列をセット
	private static function _set_message($title, $body)
	{
		return  array
				(
				    'title' => $title,
				    'body'  => $body,
//				    'badge' => 1,
				);
	}

	// リクエスト
	private function _curl_request($headers, $fields)
	{
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, self::NOTIFICATION_URL);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));

		$result = curl_exec($ch);
		curl_close($ch);
		return  $result;
	}

    // debug用のアウトプットファイル関数
    private function _outputFile($data=array("データ無いです。"), $controller=null, $action=null)
    {

        $file = "LoggingFilePath";

        ob_start();
        echo "Controller:".$controller."\n";
        echo "Action:".$action."\n";
        var_dump($data);
        $out = ob_get_contents();
        ob_end_clean();
        file_put_contents($file,$out,FILE_APPEND);
    }

}
