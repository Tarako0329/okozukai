<?php
// config.php - アプリ共通設定
date_default_timezone_set('Asia/Tokyo');
require_once "./vendor/autoload.php";
require_once "functions.php";
//$time="ver1.21.0";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
//define('DB_HOST', $_ENV['SV']);
//define('DB_NAME', $_ENV['DBNAME']);
define('DB_USER', $_ENV['DBUSER']);
define('DB_PASS', $_ENV['PASS']);
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'おてつだいポイント');
define('SESSION_NAME', 'otetsudai_sess');

// セッション設定
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_name(SESSION_NAME);
session_start();

//DB接続関連
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);
define("DB_HOST", $_ENV["SV"]);
define("DB_NAME", $_ENV["DBNAME"]);

define("KEY", $_ENV["KEY"]);
define("EXEC_MODE", $_ENV["EXEC_MODE"]);

spl_autoload_register(function ($className) {
  // 1. 名前空間のバックスラッシュ '\' を、OS標準のパス区切り文字（通常は '/'）に置換
  $path = str_replace('\\', DIRECTORY_SEPARATOR, $className);
  // 2. クラスファイルを探すフルパスを組み立て
  $file = __DIR__.DIRECTORY_SEPARATOR.$path.'.php';
  //log_writer2("Autoloading class", $className . " (Path: " . $file . ")", "lv3");
  // 3. ファイルが存在すれば読み込む
  if (file_exists($file)) {
    require_once $file;
    //log_writer2("Autoloading success", "Class: " . $className . " (Expected Path: " . $file . ")", "lv3");
  }else{
    log_writer2("Autoloading failed", "Class: " . $className . " (Expected Path: " . $file . ")", "lv3");
  }
});

// DBとの接続
class_alias('classes\Utilities\Utilities','U');
use classes\Database\Database;
$db_c = new Database();
/**
 * PDO接続を返す（シングルトン）
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

/**
 * ログイン済みチェック。未ログインならlogin.phpへ
 */
function require_login(): array {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    return $_SESSION['user'];
}

/**
 * 親ロールのみ許可
 */
function require_parent(): array {
    $user = require_login();
    if ($user['role'] !== 'parent') {
        header('Location: dashboard.php');
        exit;
    }
    return $user;
}

/**
 * XSS対策エスケープ
 */
function h(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * CSRF トークン生成・検証
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('不正なリクエストです。');
    }
}

/**
 * ユーザーの現在ポイント合計を取得
 */
function get_user_points(PDO $db, int $user_id): float {
    $stmt = $db->prepare('SELECT COALESCE(SUM(point), 0) FROM point_logs WHERE user_id = ? AND log_type = "earn"');
    $stmt->execute([$user_id]);
    $earn = (float)$stmt->fetchColumn();

    $stmt = $db->prepare('SELECT COALESCE(SUM(ABS(point)), 0) FROM point_logs WHERE user_id = ? AND log_type = "redeem"');
    $stmt->execute([$user_id]);
    $redeem = (float)$stmt->fetchColumn();

    return $earn - $redeem;
}
