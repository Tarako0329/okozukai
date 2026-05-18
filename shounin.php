<?php
require_once 'config.php';
U::log("\$_GET",$_GET,4);
if (!isset($_GET['log_id']) || !isset($_GET['is_shounin'])) {
    http_response_code(400);
    die('不正なリクエストです。');
}
$log_id = (int)$_GET['log_id'];
$is_shounin = $_GET['is_shounin'] === '1';

if($is_shounin){
    $db_c->UP_DEL_EXEC('UPDATE point_logs SET shounin_date = NOW() WHERE log_id = :log_id',['log_id' => $log_id]);
    echo "承認しました。";
}else{
    $db_c->UP_DEL_EXEC('DELETE FROM point_logs WHERE log_id = :log_id',['log_id' => $log_id]);
    echo "却下しました。";
}

?>