<?php
// login.php
require_once 'config.php';
use classes\Security\Security;


// すでにログイン済みならダッシュボードへ
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $family_name = trim((string)($_POST['family_name'] ?? ""));
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';

    if($action === 'register'){
      try{
        $db_c->begin_tran();
        $db_c->INSERT('families', ['family_name' => $family_name]);
        $family_id = $db_c->SELECT('SELECT family_id FROM families WHERE family_name = :family_name', ['family_name' => $family_name]);
        $family_id = $family_id[0]['family_id'] ?? null;
        $security = new Security($family_id,KEY);
        $db_c->INSERT('users', [
          'family_id' => $family_id,
          'username' => $username,
          'password_hash' => $security->passEx($password),
          'display_name' => $username,
          'role' => 'parent',
          'avatar_color' => sprintf('#%06X', random_int(0, 0xFFFFFF)),
        ]);
        $db_c->commit_tran();
      }catch(Exception $e){
        $db_c->rollback_tran();
        $error = '家族の作成に失敗しました。';
      }
    }else{
      // ログイン処理は下で行う
      $family_id = $db_c->SELECT('SELECT family_id FROM families WHERE family_name = :family_name', ['family_name' => $family_name]);
      $family_id = $family_id[0]['family_id'] ?? null;
      $security = new Security($family_id,KEY);
    }

    if ($family_name && $username && $password) {
        $user = $db_c->SELECT('SELECT u.*, f.family_name, f.point_rate
             FROM users u
             JOIN families f ON f.family_id = u.family_id
             WHERE f.family_name = :family_name AND u.username = :username', ['family_name' => $family_name, 'username' => $username]);
        
        $user = $user[0] ?? null;


        if ($user && $security->verifyPassword($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id'     => $user['user_id'],
                'family_id'   => $user['family_id'],
                'family_name' => $user['family_name'],
                'point_rate'  => $user['point_rate'],
                'username'    => $user['username'],
                'display_name'=> $user['display_name'],
                'role'        => $user['role'],
                'avatar_color'=> $user['avatar_color'],
            ];
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = 'ユーザーIDまたはパスワードがちがいます。';
}

// 家族一覧取得
$db       = get_db();
$families = $db->query('SELECT family_id, family_name FROM families ORDER BY family_id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ログイン | <?= h(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kaisei+Decol:wght@400;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="script/function.js?<?php echo $time; ?>"></script>
  <link rel='manifest' href='site.webmanifest?<?php echo $time;?>'>
  <style>
    :root {
      --c-sky:    #d6eef8;
      --c-mint:   #c8f0e0;
      --c-peach:  #fce4d6;
      --c-lemon:  #fdf5c0;
      --c-lilac:  #e8d8f8;
      --c-ink:    #4a4a6a;
      --c-muted:  #8888aa;
      --c-white:  #fffef8;
      --c-accent: #7bb8d4;
      --font-h:   'Kaisei Decol', serif;
      --font-b:   'Zen Maru Gothic', sans-serif;
      }
  
    * { box-sizing: border-box; }
    body {
      font-family: var(--font-b);
      background: var(--c-white);
      color: var(--c-ink);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
      }
  
    /* 水彩風背景 */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 10% 20%, rgba(214,238,248,.7) 0%, transparent 60%),
        radial-gradient(ellipse 60% 80% at 90% 80%, rgba(200,240,224,.6) 0%, transparent 55%),
        radial-gradient(ellipse 50% 50% at 50% 50%, rgba(253,245,192,.5) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 80% 10%, rgba(232,216,248,.5) 0%, transparent 50%),
        #fffef8;
      z-index: -1;
      }
  
    /* 浮かぶ丸 */
    .bubble {
      position: fixed;
      border-radius: 50%;
      opacity: .18;
      animation: float 8s ease-in-out infinite;
    }
    .bubble:nth-child(1){ width:180px;height:180px;background:var(--c-sky);  top:-40px; left:-40px; animation-delay:0s;}
    .bubble:nth-child(2){ width:120px;height:120px;background:var(--c-mint); bottom:20%; right:-30px; animation-delay:2s;}
    .bubble:nth-child(3){ width:90px; height:90px; background:var(--c-peach);bottom:-20px;left:30%; animation-delay:4s;}
      .bubble:nth-child(4){ width:60px; height:60px; background:var(--c-lilac);top:30%; right:20%; animation-delay:1s;}
  
    @keyframes float {
      0%,100%{ transform: translateY(0) rotate(0deg); }
      50%     { transform: translateY(-20px) rotate(5deg); }
      }
  
    .login-card {
      background: rgba(255,254,248,.85);
      backdrop-filter: blur(12px);
      border: 2px solid rgba(123,184,212,.25);
      border-radius: 28px;
      padding: 2.5rem 2.8rem;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 8px 40px rgba(74,74,106,.08), 0 2px 8px rgba(123,184,212,.12);
      }
  
    .app-logo {
      font-family: var(--font-h);
      font-size: 2rem;
      font-weight: 700;
      color: var(--c-ink);
      text-align: center;
      margin-bottom: .2rem;
      letter-spacing: .05em;
    }
      .app-logo span { color: var(--c-accent); }
  
    .app-sub {
      text-align: center;
      font-size: .82rem;
      color: var(--c-muted);
      margin-bottom: 1.8rem;
      }
  
    .form-label {
      font-weight: 700;
      font-size: .88rem;
      color: var(--c-ink);
      margin-bottom: .3rem;
      }
  
    .form-control, .form-select {
      border: 1.5px solid rgba(123,184,212,.4);
      border-radius: 14px;
      background: rgba(255,254,248,.9);
      color: var(--c-ink);
      font-family: var(--font-b);
      padding: .6rem 1rem;
      transition: border-color .2s, box-shadow .2s;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--c-accent);
      box-shadow: 0 0 0 3px rgba(123,184,212,.2);
      background: #fff;
      outline: none;
      }
  
    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, #9dd4e8 0%, #b5e8d0 100%);
      border: none;
      border-radius: 50px;
      color: var(--c-ink);
      font-family: var(--font-h);
      font-weight: 700;
      font-size: 1.05rem;
      padding: .75rem;
      letter-spacing: .1em;
      cursor: pointer;
      box-shadow: 0 4px 16px rgba(123,184,212,.3);
      transition: transform .15s, box-shadow .15s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(123,184,212,.4);
    }
      .btn-login:active { transform: translateY(0); }
  
    .alert-soft {
      background: rgba(252,228,214,.6);
      border: 1.5px solid rgba(240,160,120,.3);
      border-radius: 12px;
      color: #8b5a3a;
      font-size: .88rem;
      padding: .6rem 1rem;
      }
  
    .deco-stars {
      text-align: center;
      font-size: 1.2rem;
      margin-bottom: 1.2rem;
      letter-spacing: .3em;
      opacity: .7;
    }
  </style>
</head>
<body>
<div class="bubble"></div>
<div class="bubble"></div>
<div class="bubble"></div>
<div class="bubble"></div>

<div class="login-card">
  <div class="app-logo">⭐ おてつだい<span>ポイント</span></div>
  <p class="app-sub">家族みんなでポイントをためよう！</p>
  <div class="deco-stars">🌸 🌼 🌈</div>

  <?php if ($error): ?>
  <div class="alert-soft mb-3"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div class="mb-3">
      <label class="form-label">🏠 かぞく</label>
      <!--<select name="family_id" class="form-select" required>
        <option value="">えらんでね</option>
        <?php foreach ($families as $f): ?>
        <option value="<?= h($f['family_id']) ?>"
          <?= isset($_POST['family_id']) && $_POST['family_id'] == $f['family_id'] ? 'selected' : '' ?>>
          <?= h($f['family_name']) ?>
        </option>
        <?php endforeach; ?>
        </select>-->
        <input type="text" name="family_name" class="form-control"
             value="<?= h($_POST['family_name'] ?? '') ?>"
             placeholder="家族名" required autocomplete="family_name">
    </div>

    <div class="mb-3">
      <label class="form-label">👤 なまえ</label>
      <input type="text" name="username" class="form-control"
             value="<?= h($_POST['username'] ?? '') ?>"
             placeholder="ユーザー名" required autocomplete="username">
    </div>

    <div class="mb-4">
      <label class="form-label">🔑 パスワード</label>
      <input type="password" name="password" class="form-control"
             placeholder="パスワード"  autocomplete="current-password">
    </div>

    <button type="submit" class="btn-login mb-3" name="action" value="login">ログイン ✨</button>
    <button type="submit" class="btn-login" name="action" value="register">はじめて ✨</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
