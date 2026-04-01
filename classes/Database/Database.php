<?php
declare(strict_types=1);
namespace classes\Database;
use PDO,PDOException,Exception;

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
        $this->log = "-- New call from Source: {$file} " . date('Y-m-d H:i:s') . "\n";
        
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

      $stmt = $this->connect()->prepare($sql);
      $stmt -> execute($params);
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
      /*
      引数サンプル
      $table = "TableName";
      $params = ["col" => "test"]
      */
      $columns = "`".implode('`, `', array_keys($data))."`";  //項目名をカンマ区切りで取得
      $placeholders = ':' . implode(', :', array_keys($data));
      $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
      //$this->logに実行できるSQL文を書き込む
      $log = $sql;
      $log = str_replace(["\r\n", "\r", "\n","\t"], " ", $log); //$log内の改行コードを半角スペースに変換
      foreach ($data as $key => $value) {
        $value = ($value === "")? "NULL":$value;  //$valueが""の場合はNULLに変換する
        $log = str_replace([":$key,"], (is_string($value) ? "'$value'," : (string)$value.","), $log);
        $log = str_replace([":$key)"], (is_string($value) ? "'$value')" : (string)$value.")"), $log);
      }
      $log = str_replace(["'NULL'"], "NULL", $log);
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1); //メソッドをコールしたphpファイル名を取得
      
      $this->log .=  "-- Source: ".basename($backtrace[0]['file']).": ".date('Y-m-d H:i:s') . "\n";
      $this->log .= $log.";\n";
      $this->sql = $log;  //Exceptionロールバックログ用にロールバック前に投げたSQLを記録
      
      //$dataの空文字をにNULL変換
      foreach($data as $key => $value){
        if($value === ""){
          $data[$key] = null;
        }
      }
      //log_writer2("\$data",$data,"lv3");
      $stmt = $this->connect()->prepare($sql);
      return $stmt -> execute($data);
    }

    public function UP_DEL_EXEC(string $sql,array $data=[]):bool{
      /*
      引数サンプル
      $sql = "Update TableName set col1 = :col1 where col2 = :col2";
      $data = ["col1" => "test","col2" => "test"]
      */
      //ログ用SQLの作成
      $log = $sql;
      $log = str_replace(["\t"], "", $log);                 //$log内のタブを削除
      $log = str_replace(["\r\n", "\r", "\n"], " ", $log);  //$log内の改行コードを半角スペースに変換
      foreach ($data as $key => $value) { //":key" を "value" に変換
        $key = (strpos($key, ':') !== 0)?':'. $key:$key;  //keyが":"から始まってない場合は先頭に":"を付与する
        $value = ($value === "")? "NULL":$value; //valueが""の場合はNULLに変換する
        $log = str_replace($key, (is_string($value) ? "'$value'" : (string)$value), $log);
      }
      $log = str_replace(["'NULL'"], "NULL", $log);        //$log内の'NULL'をNULLに変換
      
      //メソッドをコールしたphpファイル名を取得
      $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
      
      $this->log .=  "-- Source: ".basename($backtrace[0]['file']).": ".date('Y-m-d H:i:s') . "\n";
      $this->log .= $log.";\n"; //$this->logに実行できるSQL文を書き込む
      $this->sql = $sql;        //Exceptionロールバックログ用にロールバック前に投げたSQLを記録

      //$dataの空文字をにNULL変換
      foreach($data as $key => $value){
        if($value === ""){
          $data[$key] = null;
        }
      }
      //SQL実行
      $stmt = $this->connect()->prepare($sql);
      return $stmt -> execute($data);
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
    public function rollback_tran(string $msg=""):void{
      $this->log .= "rollback;\n";
      $this->log .= "/*ERROR SQL:[".$this->sql.";]*/\n";
      $this->log .= "/*".$msg."*/\n";
      $this->connect()->rollback();
      $this->exec_log();
    }

    public function exec_log():void{
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