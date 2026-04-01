<?php
	if (php_sapi_name() != 'cli') {
		exit('このスクリプトはCLIからのみ実行可能です。');
	}
	$mypath = dirname(__DIR__);
	chdir($mypath);
	require "config.php";

	use classes\SpreadSheet\SpreadSheet;
	use classes\Security\Security;

	define("GOOGLE_AUTH",$_ENV["GOOGLE_AUTH"]);
	define("GOOGLE_AUTH_SKEY",$_ENV["GOOGLE_AUTH_SKEY"]);	

	//リフレッシュトークンの取得
	$rows = $db->SELECT(
		"SELECT 
			t.SEQ,t.ymd,t.jun,t.shu,if(t.typ=2,'自重',t.weight) as weight,t.rep,t.sets,t.memo
			,u.google_refresh_token
			,u.spsfilename
			,u.id
		FROM users u
		INNER JOIN tr_log t ON u.id = t.id
		WHERE 
			IFNULL(google_refresh_token, '') != '' 
			and IFNULL(spsfilename, '') != ''
			and t.ymd between :fromdate and :todate
		"
		,[]);
	
	foreach($rows as $row){
		$SQ = new Security($row['USER_ID'],key);
		$refreshToken = $SQ->decrypt($row['google_refresh_token']);
		$db_spsfilename = $row['spsfilename'] ?? "";

		if(U::exist($db_spsfilename) && U::exist($refreshToken)){
			try{
				$SpreadSheet = new SpreadSheet($refreshToken, $db_spsfilename);
				$SpreadSheet->G_INSERT([[$row['SEQ'], $row['ymd'], $row['jun'], $row['shu'], $row['weight'], $row['rep'], $row['sets'], $row['memo']]], "ウェイトトレーニング");
			}catch(Exception $e){
				log_writer2("Error",$e->getMessage(),"lv1");
			}
		}
		
	}



	exit();
?>