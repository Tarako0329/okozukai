<?php
require_once "./vendor/autoload.php";
//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
// DBとの接続
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["M_DBUSER"]);
define("PASSWORD", $_ENV["M_PASS"]);
$pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

function get_pdo_options() {
    return array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,   //sqlの複文禁止 "select * from hoge;delete from hoge"みたいなの
                 PDO::ATTR_EMULATE_PREPARES => false);        //同上
}

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    /*
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'production_db',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'development_db',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'testing_db',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    */
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'local', // 名前は何でもOK
        'local' => [
            'name' => $_ENV["DBNAME"],
            'connection' => $pdo_h,
        ],
    ],
    'version_order' => 'creation',
    'templates' => [
        'file' => '%%PHINX_CONFIG_DIR%%/db/migrations/migration_template.txt'
    ],
];
