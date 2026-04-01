<?php
declare(strict_types=1);
namespace classes\Utilities;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
class Utilities {
	/*private const AUTH_OPTIONS = [
		'cost' => 12, // 計算負荷を上げる（将来的に数値を増やす）
	];*/
	//private string $key = "";

	private function __construct() {}

	public static function exist($value): bool {
		// =========================================================
		// 半角/全角スペース、0を含め、$valueに何かしら値がセットされてればtrueを返す。それ以外はfalse
		// =========================================================
		return $value !== null && $value !== "";
	}

	public static function send_line(string $to,string $body):bool{
		if(EXEC_MODE==="Local"){
			log_writer2("Util::send_line - \$to",$to,"lv3");
			log_writer2("Util::send_line - \$body",$body,"lv3");
			return true;
		}
		if(EXEC_MODE!=="Product"){
			$body = "[TEST] \r\n".$body;
		}	
		$url = ROOT_URL.'line_push_msg.php';

		$data = array(
			'LINE_USER_ID' => $to,
			'MSG' => $body,
		);

		$context = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => implode("\r\n", array('Content-Type: application/x-www-form-urlencoded',)),
				'content' => http_build_query($data)
			)
		);

		$response = file_get_contents($url, false, stream_context_create($context));
		//$returnを連想配列に変換
		$response = json_decode($response, true);
		if($response["status"] === "success"){
			$return = true;
		}else{
			log_writer2("Util::send_line - \$response",$response,"lv0");
			self::send_mail(SYSTEM_NOTICE_MAIL,"LINE通知失敗","LINE通知に失敗しました。\r\n宛先:".$to."\r\n内容:\r\n".$body,TITLE." onLineShop");
			$return = false;
		}

		return $return;
	}

	public static function send_mail(
		string $to
		,string $subject
		,string $body
		,string $fromname
		,string $bcc = ""
		,string $cc = ""
		,string $from = FROM
		):bool{
		if(EXEC_MODE==="Local"){
			log_writer2("Util::send_mail - \$to",$to,"lv3");
			log_writer2("Util::send_mail - \$body",$body,"lv3");
			return true;
		}	
		if(EXEC_MODE!=="Product"){
			$subject = "[TEST] ".$subject;
		}	
		//phpmailerを使ってメール送信 $to,$subject,$body,$fromname,$bcc
		$mail = new PHPMailer(true); // true: 例外を有効にする

		try {
			// --- サーバー設定 (SMTP) ---
			//$mail->SMTPDebug = 2;               // デバッグ用（疎通確認時は 2 にすると詳細が出ます）
			$mail->isSMTP();                       // SMTPを使用
			$mail->Host       = HOST;  // ★会社のSMTPサーバーアドレス
			$mail->SMTPAuth   = true;                // ★SMTP認証が必要な場合
			$mail->Username   = POP_USER; // ★ユーザー名
			$mail->Password   = POP_PASS;     // ★パスワード
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ★暗号化 (TLS推奨)
			$mail->Port       = PORT;                 // ★ポート番号 (587 または 465)
			$mail->CharSet    = 'UTF-8';             // 文字化け防止
			// --- 送信元・宛先設定 ---
			$mail->setFrom($from, $fromname); // ★送信元
			$mail->addAddress($to);                        // 宛先
			if ($cc)  $mail->addCC($cc);
			if ($bcc) $mail->addBCC($bcc);
			// --- 内容設定 ---
			$mail->isHTML(false);                          // テキスト形式
			$mail->Subject = $subject; // 件名
			$mail->Body    = $body;
			$mail->send();
			return true;
		} catch (PHPMailerException $e) {
			// エラーが発生した場合は、後で Monolog などで記録できるようにしておく
			//error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
			log_writer2("Utilities::send_mail - Mailer Error",$mail->ErrorInfo,"lv1");
			log_writer2("Utilities::send_mail - Mailer Error \$e",$e,"lv1");
			return false;
		} catch (\Exception $e) {
			// エラーが発生した場合は、後で Monolog などで記録できるようにしておく
			//error_log("Message could not be sent. Error: {$e->getMessage()}");
			log_writer2("Utilities::send_mail - Error",$e->getMessage(),"lv1");
			log_writer2("Utilities::send_mail - Error \$e",$e,"lv1");
			return false;
		}
	}

}
?>