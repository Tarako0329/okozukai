# ⭐ おてつだいポイント — セットアップガイド

## 動作環境
- PHP 8.3+
- MySQL 8.0+
- Webサーバー（Apache / Nginx）

---

## ファイル構成

```
otetsudai_app/
├── database.sql        ← DBスキーマ＋サンプルデータ
├── config.php          ← DB接続・共通関数
├── login.php           ← ログイン画面
├── logout.php          ← ログアウト
├── dashboard.php       ← ダッシュボード（親・子共通）
├── point_earn.php      ← ポイント登録（子のみ）
├── point_redeem.php    ← ポイント換金（親のみ）
├── master.php          ← ポイントマスタ管理（親のみ）
├── child_detail.php    ← 子供の履歴詳細
├── my_history.php      ← 自分の履歴（子用、child_detailへリダイレクト）
└── admin.php           ← 家族・ユーザー管理（親のみ）
```

---

## セットアップ手順

### 1. データベース作成

```bash
mysql -u root -p < database.sql
```

### 2. config.php を編集

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'otetsudai_db');
define('DB_USER', 'your_db_user');    // ← 変更
define('DB_PASS', 'your_db_password'); // ← 変更
```

### 3. ファイルをWebサーバーに配置

Apacheなら `/var/www/html/otetsudai/` などに配置。

### 4. サンプルユーザーのパスワード設定

`database.sql` のサンプルデータにはダミーのパスワードハッシュが入っています。
実際のユーザーは **管理画面（admin.php）から「パスワード変更」** で設定してください。

または以下のスクリプトで初期ハッシュを生成して直接DBに入れることもできます：

```php
<?php echo password_hash('your_password', PASSWORD_BCRYPT, ['cost'=>12]); ?>
```

---

## 利用方法

### 親アカウント
1. ログイン → ダッシュボードで子供のポイント一覧を確認
2. **おてつだいリスト**（master.php）でお手伝い項目を管理
3. **ポイントをかえる**（point_redeem.php）で換金処理
4. **かぞく管理**（admin.php）でメンバー追加・パスワード管理

### 子アカウント
1. ログイン → 自分のポイントを確認
2. **おてつだいする**（point_earn.php）でマスタからお手伝いを選んで登録
3. **じぶんのきろく**で履歴を確認

---

## セキュリティ注意事項

- `config.php` はWebルートの外に置くことを推奨
- HTTPS環境での運用を推奨
- 本番環境では `DB_PASS` を環境変数から読み込むことを推奨

---

## カスタマイズポイント

| 項目 | ファイル | 変更箇所 |
|------|---------|---------|
| 1ポイントの円レート | admin.php | 家族設定から変更可能 |
| お手伝い項目 | master.php（画面操作） | UIから追加・編集・削除 |
| アバターカラー | admin.php | カラーパレットから選択 |
| デザイン色調 | 各PHPファイル | `:root { }` のCSS変数 |
