<?php
// =========================================================
// オリジナルログ出力(error_log)
// =========================================================
function log_writer($pgname,$msg){
	$log = print_r($msg,true);
	file_put_contents("error_log","[".date("Y/m/d H:i:s")."] ORG_LOG from [".$_SERVER["PHP_SELF"]." -> ".$pgname."] => ".$log."\n",FILE_APPEND);
}
function log_writer2($pgname,$msg,$kankyo){
	//$kankyo:lv0=全環境+メール通知 lv1=全環境 lv2=本番以外 lv3=テスト・ローカル環境のみ
	
	if($kankyo==="lv0"){
			log_writer($pgname,$msg);
			$log = print_r($msg,true);
			
			//send_mail(SYSTEM_NOTICE_MAIL,"【重要】".TITLE."でシステムエラー発生",$log);
	}else if($kankyo==="lv1"){
			log_writer($pgname,$msg);
	}else if($kankyo==="lv2" && EXEC_MODE!=="Product"){
			log_writer($pgname,$msg);
	}else if($kankyo==="lv3" && (EXEC_MODE==="Test" || EXEC_MODE==="local")){
			log_writer($pgname,$msg);
	}else{
			return;
	}
}

// =========================================================
// 不可逆暗号化
// =========================================================
function passEx($str,$uid){
	if(strlen($str)<=8 and !empty($uid)){
		$rtn = crypt($str,key);
		for($i = 0; $i < 1000; $i++){
			$rtn = substr(crypt($rtn.$uid,key),2);
		}
	}else{
		$rtn = $str;
	}
	return $rtn;
}

// =========================================================
// MAXウェイト算出
// =========================================================
function max_r($wt, $rep){
	if($rep == 1){
		return (double)$wt;
	}elseif($rep == 2){
		return (double)$wt / 0.95;
	}elseif($rep == 3){
		return (double)$wt / 0.93;
	}elseif($rep == 4){
		return (double)$wt / 0.90;
	}elseif($rep == 5){
		return (double)$wt / 0.87;
	}elseif($rep == 6){
		return (double)$wt / 0.85;
	}elseif($rep == 7){
		return (double)$wt / 0.82;
	}elseif($rep == 8){
		return (double)$wt / 0.80;
	}elseif($rep == 9){
		return (double)$wt / 0.77;
	}elseif($rep == 10){
		return (double)$wt / 0.75;
	}else{
		return (double)$wt;
	}
	
}


 
//---------------------------------------------------------------------------//
//自動ログイン処理
//--------------------------------------------------------------------------//
 function check_auto_login($token):bool {
	global $db;
  //2週間前の日付を取得
	$date = new DateTime("- 14 days");
 	$datetime = $date->format('Y-m-d H:i:s');

	$sql = "SELECT * FROM AUTO_LOGIN WHERE TOKEN = :TOKEN AND REGISTRATED_TIME >= :REGISTRATED_TIME;";
	$row =$db->SELECT($sql,[":TOKEN" => $token,":REGISTRATED_TIME" => $datetime]);
	$row_cnt = count($row);
	if ($row_cnt == 1) {
     	$_SESSION['USER_ID']  = $row[0]['USER_ID'];
  } else {
	 	//自動ログイン失敗
  	//Cookie のトークンを削除
  	setCookie("token", '', -1, "/", "", true, true);

	  //古くなったトークンを削除
  	delete_old_token($token);
  	return false;
	}
  return true;
}
 
//---------------------------------------------------------------------------//
//トークンの登録
//---------------------------------------------------------------------------//
 function register_token($id, $token) {
	global $db;
	$db->INSERT("AUTO_LOGIN",["USER_ID"=>$id,"TOKEN"=>$token,"REGISTRATED_TIME"=>date('Y-m-d')]);
 	return 0;
 
 }
 
//---------------------------------------------------------------------------//
//トークンの削除
//---------------------------------------------------------------------------//
function delete_old_token($token) {
  //DB接続
	global $db;

  //プレースホルダで SQL 作成
  $sql = "DELETE  FROM AUTO_LOGIN WHERE TOKEN = :TOKEN";
	$db->UP_DEL_EXEC($sql,[":TOKEN" => $token]);

 	return 0;
}
 
//---------------------------------------------------------------------------//
// トークンを作成
//---------------------------------------------------------------------------//
function get_token() {
  $TOKEN_LENGTH = 16;//16*2=32桁
  $bytes = openssl_random_pseudo_bytes($TOKEN_LENGTH);
	$_SESSION["token"] = bin2hex($bytes);
  return $_SESSION["token"];
}

// =========================================================
// PDO の接続オプション取得
// =========================================================
function get_pdo_options() {
  return array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,   //sqlの複文禁止 "select * from hoge;delete from hoge"みたいなの
               PDO::ATTR_EMULATE_PREPARES => false);        //同上
}
function get_device_type() {
    $ua = $_SERVER['HTTP_USER_AGENT'];

    if (strpos($ua, 'iPhone') !== false || (strpos($ua, 'Android') !== false && strpos($ua, 'Mobile') !== false)) {
        return 'モバイル';
    } elseif (strpos($ua, 'iPad') !== false || strpos($ua, 'Android') !== false) {
        return 'タブレット';
    } else {
        return 'PC';
    }
}


?>