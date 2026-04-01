<?php
declare(strict_types=1);
namespace classes\Security;
use classes\Database\Database;
class Security {
	private const AUTH_OPTIONS = [
		'cost' => 12, // 計算負荷を上げる（将来的に数値を増やす）
	];
  private string $key = "";
  private string $uid = "";

  public function __construct(string $p_uid, string $p_key) {
    $this->uid = $p_uid;
    $this->key = $p_key;
		log_writer2("\$p_uid",$p_uid,"lv3");
		log_writer2("\$p_key",$p_key,"lv3");
	}

	public function passEx(string $str): string {
		// =========================================================
		// パスワードハッシュ化
		// =========================================================
		$pwd_peppered = hash_hmac("sha256", $str, $this->key);
		log_writer2("\$pwd_peppered",$pwd_peppered,"lv3");
		return password_hash($pwd_peppered, PASSWORD_DEFAULT, self::AUTH_OPTIONS);
	}

	public function verifyPassword(string $password, string $hashedPassword): bool {//success,failure,update:パスワードは一致、かつパスワードロジック変更によるDB更新が必要な場合
		// 第2引数にはDBから取得したハッシュ値を渡します。
		$sts = "failure";
		$return = false;
		
		$p_hash_r = $this->passEx_rez($password,$this->uid,"bonBer");			//レジアプリのパスワードハッシュ
		$p_hash_t = $this->passEx_tore($password,$this->uid,"bonBer");		//肉体改造のパスワードハッシュ
		$p_hash_k = $this->passEx_kakei($password,$this->uid,"akgo8903");	//家計簿のパスワードハッシュ
		$pwd_peppered = hash_hmac("sha256", $password, $this->key);
		log_writer2("\$pwd_peppered","verifyPassword start","lv3");

		if(hash_equals($p_hash_r,$hashedPassword)){
			$sts = "update";
		}else if(hash_equals($p_hash_t,$hashedPassword)){
			$sts = "update";
		}else if(hash_equals($p_hash_k,$hashedPassword)){
			$sts = "update";
		}else if(password_verify($pwd_peppered, $hashedPassword)){
			$sts = "success";
			$return = true;
		}else if(password_verify($password, $hashedPassword)){
			if(password_needs_rehash($hashedPassword, PASSWORD_DEFAULT, self::AUTH_OPTIONS)){
				$sts = "update";
			}
		}else{
			$sts = "failure";
			log_writer2("\$p_hash_r",$p_hash_r,"lv3");
			log_writer2("\$p_hash_t",$p_hash_t,"lv3");
			log_writer2("\$p_hash_k",$p_hash_k,"lv3");
		}

		log_writer2("\$sts",$sts,"lv3");
		if($sts === "update"){
			log_writer2("func:verifyPassword","パスワード更新(今はスキップ)","lv3");
			$db = new Database();
			try{
				//$db->begin_tran();
				//$db->UP_DEL_EXEC("UPDATE Users SET `password`=:password WHERE `uid` = :uid", [":password" => $this->passEx($password),":uid" => $this->uid]);
				//$db->commit_tran();
				
				$return = true;
			}catch(\Exception $e){
				//$db->rollback_tran();
				log_writer2("func:verifyPassword","パスワード更新失敗","lv0");
				log_writer2("\$e",$e,"lv0");
			}
		}
		
		return $return;
	}

	//暗号化（可逆）
	public function encrypt(string $str): string {
		$method = "AES-256-CBC";
		$key = hash('sha256', $this->key);
		$iv = substr(hash('sha256', $this->uid), 0, 16);
		return openssl_encrypt($str, $method, $key, 0, $iv);
	}
	//復号化（可逆）
	public function decrypt(string $str): string {
		$method = "AES-256-CBC";
		$key = hash('sha256', $this->key);
		$iv = substr(hash('sha256', $this->uid), 0, 16);
		return openssl_decrypt($str, $method, $key, 0, $iv);
	}

	//tore
	private function passEx_tore(string $str,string $uid,string $key):string {
		if(strlen($str)<=8 and !empty($uid)){
			$rtn = crypt($str,$key);
			for($i = 0; $i < 1000; $i++){
				$rtn = substr(crypt($rtn.$uid,$key),2);
			}
		}else{
			$rtn = $str;
		}
		return $rtn;
	}

	//家計簿
	private function passEx_kakei(string $str,string $uid,string $key):string {
		if(strlen($str)>0 and !empty($uid)){
			$rtn = crypt($str.$uid,$key);
			for($i = 0; $i < 1000; $i++){
				$rtn = substr(crypt($rtn.$uid,$key),2);
			}
		}else{
			$rtn = $str;
		}
		return substr($rtn,0,20);
	}

	//レジ
	private function passEx_rez(string $str,string $uid,string $key):string {
		if(strlen($str)>0 and !empty($uid)){
			$rtn = crypt($str,$key);
			for($i = 0; $i < 1000; $i++){
				$rtn = substr(crypt($rtn.$uid,$key),2);
			}
		}else{
			$rtn = $str;
		}
		return $rtn;
	}



}
?>