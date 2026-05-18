<?php
declare(strict_types=1);
namespace classes\Database;
use PDO,PDOException,Exception;
use classes\Utilities\Utilities as U;

/**
 * Usage of defined constants required for this class:
 * - DB_HOST: Database host server address (e.g., localhost or IP address)
 * - DB_NAME: Name of the database to connect to
 * - USER_NAME: Username for database authentication
 * - PASSWORD: Password for database authentication
 */

class Database {
    // 接続情報をプロパティとして保持（.envから読み込む想定）
    private string $host;
    private string $db;
    private string $user;
    private string $pass;
    private string $charset = 'utf8';
    private ?PDO $pdo = null;
    private string $log = "";
    private string $sql = ""; //ロールバック時にエラーの原因となったSQLを記録するためのプロパティ

    public function __construct() {
        // 本来は config.php の定数などを使用します
        $this->host = DB_HOST;
        $this->db   = DB_NAME;
        $this->user = USER_NAME;
        $this->pass = PASSWORD;
        //newしたphpファイル名を$fileに取得
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = isset($backtrace[0]['file']) ? basename($backtrace[0]['file']) : 'unknown';
        //$this->log = "-- New call from Source: {$file} " . date('Y-m-d H:i:s') . "\n";
        
    }
    public function __destruct() {
      // スクリプトが終わる時に自動でこれが呼ばれる
      //echo "ログアウト処理や一時的なメモリの解放を行います。";
    }
    /**
     * PDOインスタンスを取得する（シングルトンパターンに近い形）
     */
    private function connect(): \PDO {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // エラー時に例外を投げる
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // デフォルトを連想配列に
            PDO::ATTR_EMULATE_PREPARES   => false,                   // 静的プレパレードステートメントを使用
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,               // マルチステートメントを使用しない
            PDO::ATTR_STRINGIFY_FETCHES  => false,                   // 数値を文字列に変換しない
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            return $this->pdo;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception("データベース接続に失敗しました。");
        }
    }

    public function SELECT(string $sql,array $params=[]):array{
      /*
      引数サンプル
      $sql = "select * from A where col = :col";
      $params = ["col" => "test"]
      */
      $this->sql = $sql;  //Exceptionロールバックログ用にロールバック前に投げたSQLを記録

      // プレースホルダに含まれるキーだけを残す
      $normalizedParams = [];
      foreach ($params as $key => $value) {
        $normalizedKey = ltrim((string)$key, ':');
        $normalizedParams[$normalizedKey] = $value;
      }
      $placeholders = [];
      if (preg_match_all('/(?<!:):([a-zA-Z0-9_]+)/', $sql, $matches)) {
        $placeholders = array_unique($matches[1]);
      }
      $filteredParams = [];
      foreach ($placeholders as $name) {
        if (array_key_exists($name, $normalizedParams)) {
          $filteredParams[$name] = $normalizedParams[$name];
        }
      }

      $stmt = $this->connect()->prepare($sql);
      $stmt -> execute($filteredParams);
      $result = $stmt -> fetchAll(PDO::FETCH_ASSOC);
      //log_writer2("\$result",$result,"lv3");
      //$resultのNULLを空文字に変換
      $result = array_map(function($row){
        return array_map(function($value){
          return $value === null ? "" : $value;
        }, $row);
      }, $result);
      //log_writer2("\$result Null変換後",$result,"lv3");
      return $result;
    }

    public function INSERT(string $table,array $data=[]):bool{
      /*引数サンプル
        $table = "TableName";
        $params = ["col" => "test"]
      */
      // テーブルのカラムを取得
      $stmt = $this->connect()->prepare("SHOW COLUMNS FROM `$table`");
      $stmt->execute();
      $columnsInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $tableColumns = array_column($columnsInfo, 'Field');

      // $dataのキーをチェックし、存在しないキーを警告ログに出力し、無視
      $filteredData = [];
      foreach ($data as $key => $value) {
        if (in_array($key, $tableColumns)) {
          $filteredData[$key] = $value;
        } else {
          U::log("","Warning: Column '$key' does not exist in table '$table'",4);
        }
      }

      if (empty($filteredData)) {
        U::log("","Warning: No valid columns provided for INSERT into table '$table'",4);
        return false;
      }

      $columns = "`".implode('`, `', array_keys($filteredData))."`";  //項目名をカンマ区切りで取得
      $placeholders = ':' . implode(', :', array_keys($filteredData));
      $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
      //$this->logに実行できるSQL文を書き込む
      $log = $sql;
      $log = str_replace(["\r\n", "\r", "\n","\t"], " ", $log); //$log内の改行コードを半角スペースに変換
      foreach ($filteredData as $key => $value) {
        $value = ($value === "")? "NULL":$value;  //$valueが""の場合はNULLに変換する
        //$valueの中の'を''に変換する
        if(is_string($value)){
          $value = str_replace("'", "''", $value);
        }
        $log = str_replace([":$key,"], (is_string($value) ? "'$value'," : (string)$value.","), $log);
        $log = str_replace([":$key)"], (is_string($value) ? "'$value')" : (string)$value.")"), $log);
      }
      $log = str_replace(["'NULL'"], "NULL", $log);
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1); //メソッドをコールしたphpファイル名を取得
      
      $this->log .=  "-- Source: ".basename($backtrace[0]['file']).": ".date('Y-m-d H:i:s') . "\n";
      $this->log .= $log.";\n";
      $this->sql = $log;  //Exceptionロールバックログ用にロールバック前に投げたSQLを記録
      
      //$filteredDataの空文字をにNULL変換
      foreach($filteredData as $key => $value){
        if($value === ""){
          $filteredData[$key] = null;
        }
      }
      //log_writer2("\$data",$data,"lv3");
      $stmt = $this->connect()->prepare($sql);
      return $stmt -> execute($filteredData);
    }

    public function UP_DEL_EXEC(string $sql,array $data=[]):bool{
      /*
      引数サンプル
      $sql = "Update TableName set col1 = :col1 where col2 = :col2";
      $data = ["col1" => "test","col2" => "test"]
      */
      // プレースホルダに含まれるキーだけを残す
      $normalizedData = [];
      foreach ($data as $key => $value) {
        $normalizedKey = ltrim($key, ':');
        $normalizedData[$normalizedKey] = $value;
      }
      $placeholders = [];
      if (preg_match_all('/(?<!:):([a-zA-Z0-9_]+)/', $sql, $matches)) {
        $placeholders = array_unique($matches[1]);
      }
      $filteredData = [];
      foreach ($placeholders as $name) {
        if (array_key_exists($name, $normalizedData)) {
          $filteredData[$name] = $normalizedData[$name];
        }
      }

      //ログ用SQLの作成
      $log = $sql;
      $log = str_replace(["\t"], "", $log);                 //$log内のタブを削除
      $log = str_replace(["\r\n", "\r", "\n"], " ", $log);  //$log内の改行コードを半角スペースに変換
      foreach ($filteredData as $key => $value) { //":key" を "value" に変換
        $placeholder = ':'.$key;
        $value = ($value === "")? "NULL":$value; //valueが""の場合はNULLに変換する
        $log = str_replace($placeholder, (is_string($value) ? "'$value'" : (string)$value), $log);
      }
      $log = str_replace(["'NULL'"], "NULL", $log);        //$log内の'NULL'をNULLに変換
      
      //メソッドをコールしたphpファイル名を取得
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
      
      $this->log .=  "-- Source: ".basename($backtrace[0]['file']).": ".date('Y-m-d H:i:s') . "\n";
      $this->log .= $log.";\n"; //$this->logに実行できるSQL文を書き込む
      $this->sql = $sql;        //Exceptionロールバックログ用にロールバック前に投げたSQLを記録

      //$filteredDataの空文字をNULL変換
      foreach($filteredData as $key => $value){
        if($value === ""){
          $filteredData[$key] = null;
        }
      }
      //SQL実行
      $stmt = $this->connect()->prepare($sql);
      return $stmt -> execute($filteredData);
    }

    public function begin_tran():void{
      $this->log .= "start transaction;\n";
      $this->connect()->beginTransaction();
    }
    public function commit_tran():void{
      $this->log .= "commit;\n";
      $this->connect()->commit();
      $this->exec_log();
    }
    public function rollback_tran($msg=""):void{
      $msg = var_export($msg, true); //配列やオブジェクトも文字列化してログに出力できるようにする
      $this->log .= "rollback;\n";
      $this->log .= "/*ERROR SQL:[".$this->sql.";]*/\n";
      $this->log .= "/*".$msg."*/\n";
      $this->connect()->rollback();
      $this->exec_log();
    }
    public function Exception_rollback(\Throwable $e,String $msg=""):void{
      //例外が発生した場合のロールバック処理と管理者への通知を行うメソッド
      $msg = (U::exist($msg) ? "$msg\n" : "");
      $this->rollback_tran($msg."Exception Message:".$e->getMessage());
      //メソッドをコールしたファイルを取得
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
      $file = isset($backtrace[0]['file']) ? basename($backtrace[0]['file']) : 'unknown';
      U::send_E($e,"【".EXEC_MODE."】[$file]でExceptionロールバック発生", $msg."ErrorSQL:".$this->sql."\nログ:\n".$this->log);
    }

    private function exec_log():void{
      //sqllog/日付.sql ファイルに$msgを追記
      $dir = 'sqllog';
      if (!is_dir($dir)) {
          mkdir($dir, 0777, true);
      }
      $filename = $dir . '/' . date('Ymd') . '.sql';
      file_put_contents($filename, $this->log . PHP_EOL, FILE_APPEND);
      $this->log = "";
    }
    
 
}
?>