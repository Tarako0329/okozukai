<?php
// dashboard.php
require_once 'config.php';
$user = require_login();
$db   = get_db();

$family_id = $user['family_id'];
$is_parent = ($user['role'] === 'parent');

// 子供一覧（家族内）
$stmt = $db->prepare('SELECT user_id, display_name, avatar_color FROM users WHERE family_id = ? AND role = "child" ORDER BY user_id');
$stmt->execute([$family_id]);
$children = $stmt->fetchAll();

// 子供ごとのポイント
$child_points = [];
foreach ($children as $child) {
    $child_points[$child['user_id']] = get_user_points($db, (int)$child['user_id']);
}

// ログインユーザーが子供なら自分のポイントのみ
if (!$is_parent) {
    $my_points = get_user_points($db, (int)$user['user_id']);
}

// 最近の履歴（家族全体）
$stmt = $db->prepare(
    'SELECT pl.*, u.display_name, u.avatar_color
     FROM point_logs pl
     JOIN users u ON u.user_id = pl.user_id
     WHERE pl.family_id = ?
     ORDER BY pl.created_at DESC
     LIMIT 10'
);
$stmt->execute([$family_id]);
$recent_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ダッシュボード | <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kaisei+Decol:wght@400;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="apple-touch-icon" type="image/png" href="img/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="img/icon-192x192.png">
<link rel='manifest' href='site.webmanifest?<?php echo $time;?>'>
<style>
:root {
  --c-sky:    #d6eef8;
  --c-mint:   #c8f0e0;
  --c-peach:  #fce4d6;
  --c-lemon:  #fdf5c0;
  --c-lilac:  #e8d8f8;
  --c-ink:    #4a4a6a;
  --c-muted:  #9999bb;
  --c-white:  #fffef8;
  --c-accent: #7bb8d4;
  --c-nav:    rgba(255,254,248,.92);
  --font-h:   'Kaisei Decol', serif;
  --font-b:   'Zen Maru Gothic', sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  font-family: var(--font-b);
  background: var(--c-white);
  color: var(--c-ink);
  min-height: 100vh;
}
body::before {
  content: '';
  position: fixed; inset: 0; z-index: -1;
  background:
    radial-gradient(ellipse 70% 50% at 5%  15%, rgba(214,238,248,.6) 0%, transparent 55%),
    radial-gradient(ellipse 50% 70% at 95% 85%, rgba(200,240,224,.5) 0%, transparent 50%),
    radial-gradient(ellipse 40% 40% at 70% 20%, rgba(232,216,248,.4) 0%, transparent 50%),
    radial-gradient(ellipse 60% 40% at 30% 80%, rgba(253,245,192,.4) 0%, transparent 50%),
    #fffef8;
}

/* === NAVBAR === */
.navbar-custom {
  background: var(--c-nav);
  backdrop-filter: blur(10px);
  border-bottom: 1.5px solid rgba(123,184,212,.2);
  padding: .6rem 1.2rem;
}
.navbar-brand {
  font-family: var(--font-h);
  font-size: 1.25rem;
  color: var(--c-ink) !important;
  letter-spacing: .05em;
}
.nav-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; font-weight: 700;
  color: var(--c-ink);
  border: 2px solid rgba(255,255,255,.8);
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.role-badge {
  font-size: .7rem; padding: .2rem .55rem;
  border-radius: 50px;
  font-weight: 700;
}
.role-badge.parent { background: rgba(232,216,248,.8); color: #6a4a9a; }
.role-badge.child  { background: rgba(200,240,224,.8); color: #2a7a5a; }

/* === CARDS === */
.washi-card {
  background: rgba(255,254,248,.82);
  backdrop-filter: blur(8px);
  border: 1.5px solid rgba(255,255,255,.8);
  border-radius: 24px;
  box-shadow: 0 4px 24px rgba(74,74,106,.07);
  padding: 1.4rem 1.6rem;
  margin-bottom: 1.2rem;
  transition: transform .2s, box-shadow .2s;
}
.washi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(74,74,106,.1); }

.point-big {
  font-family: var(--font-h);
  font-size: 3rem;
  font-weight: 700;
  color: var(--c-ink);
  line-height: 1;
}
.point-unit { font-size: 1rem; color: var(--c-muted); margin-left: .3rem; }

.child-card {
  border-radius: 20px;
  padding: 1.2rem 1.4rem;
  margin-bottom: .8rem;
  border: 1.5px solid rgba(255,255,255,.9);
  box-shadow: 0 3px 16px rgba(74,74,106,.07);
  display: flex; align-items: center; gap: 1rem;
  text-decoration: none; color: inherit;
  transition: transform .18s, box-shadow .18s;
}
.child-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(74,74,106,.12); color: inherit; }
.child-avatar {
  width: 52px; height: 52px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; font-weight: 700;
  border: 2.5px solid rgba(255,255,255,.9);
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  flex-shrink: 0;
}
.child-name { font-weight: 700; font-size: 1rem; }
.child-pts  { font-family: var(--font-h); font-size: 1.6rem; font-weight: 700; }
.child-yen  { font-size: .8rem; color: var(--c-muted); }

/* === LOG TABLE === */
.log-item {
  padding: .7rem 0;
  border-bottom: 1px dashed rgba(123,184,212,.25);
  display: flex; align-items: center; gap: .8rem;
}
.log-item:last-child { border-bottom: none; }
.log-icon-wrap {
  width: 38px; height: 38px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
}
.log-point.earn   { color: #2a9a6a; font-weight: 700; }
.log-point.redeem { color: #c05050; font-weight: 700; }
.log-task  { font-weight: 700; font-size: .9rem; }
.log-meta  { font-size: .78rem; color: var(--c-muted); }

/* === BUTTONS === */
.btn-washi {
  border-radius: 50px;
  font-family: var(--font-b);
  font-weight: 700;
  padding: .55rem 1.3rem;
  border: none;
  cursor: pointer;
  transition: transform .15s, box-shadow .15s;
  font-size: .9rem;
}
.btn-washi:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(74,74,106,.15); }
.btn-earn   { background: linear-gradient(135deg,#a8e6c8,#c8f0e0); color: #2a6a4a; }
.btn-redeem { background: linear-gradient(135deg,#f8d4d4,#fce4d6); color: #8a3030; }
.btn-master { background: linear-gradient(135deg,#d4d8f8,#e8d8f8); color: #4a3a8a; }
.btn-admin  { background: linear-gradient(135deg,#fdf5c0,#fce4d6); color: #8a6a2a; }

/* === SECTION TITLE === */
.sec-title {
  font-family: var(--font-h);
  font-size: 1.1rem;
  color: var(--c-ink);
  border-left: 4px solid var(--c-accent);
  padding-left: .7rem;
  margin-bottom: 1rem;
}

.rate-badge {
  background: rgba(253,245,192,.8);
  border: 1.5px solid rgba(200,180,80,.25);
  border-radius: 12px;
  padding: .3rem .8rem;
  font-size: .82rem;
  color: #7a6a2a;
  font-weight: 700;
}

.page-wrap {
  max-width: 900px;
  margin: 0 auto;
  padding: 1.5rem 1rem 4rem;
}

@media (max-width: 575px) {
  .point-big { font-size: 2.2rem; }
  .child-pts  { font-size: 1.3rem; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-custom sticky-top">
  <div class="container-lg px-3">
    <a class="navbar-brand" href="dashboard.php">⭐ <?= h(APP_NAME) ?></a>
    <div class="d-flex align-items-center gap-2">
      <span class="role-badge <?= h($user['role']) ?>"><?= $is_parent ? '👑 おやさん' : '🌟 こども' ?></span>
      <div class="nav-avatar" style="background:<?= h($user['avatar_color']) ?>">
        <?= mb_substr($user['display_name'], 0, 1, 'UTF-8') ?>
      </div>
      <span class="d-none d-sm-inline" style="font-weight:700;font-size:.9rem"><?= h($user['display_name']) ?></span>
      <a href="logout.php" class="btn btn-sm btn-outline-secondary rounded-pill ms-1" style="font-size:.8rem">ログアウト</a>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- ===== 子供ビュー ===== -->
  <?php if (!$is_parent): ?>
  <div class="washi-card" style="background:linear-gradient(135deg,rgba(200,240,224,.6),rgba(214,238,248,.6))">
    <div class="d-flex align-items-center gap-3 mb-2">
      <div style="width:56px;height:56px;border-radius:50%;background:<?= h($user['avatar_color']) ?>;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.1)">
        <?= mb_substr($user['display_name'], 0, 1, 'UTF-8') ?>
      </div>
      <div>
        <div style="font-size:.85rem;color:var(--c-muted)">こんにちは！</div>
        <div style="font-family:var(--font-h);font-size:1.2rem;font-weight:700"><?= h($user['display_name']) ?> さん ⭐</div>
      </div>
      <div class="ms-auto text-end">
        <div style="font-size:.78rem;color:var(--c-muted)">1pt = <?= h(number_format($user['point_rate'])) ?> 円</div>
        <span class="rate-badge">💴 <?= h(number_format($my_points * $user['point_rate'])) ?> 円ぶん</span>
      </div>
    </div>
    <div class="text-center py-2">
      <span class="point-big"><?= h(number_format($my_points, 1)) ?></span>
      <span class="point-unit">ポイント</span>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="point_earn.php" class="btn-washi btn-earn flex-grow-1 text-center text-decoration-none">✋ おてつだいする</a>
    <a href="my_history.php" class="btn-washi btn-master flex-grow-1 text-center text-decoration-none">📋 じぶんのきろく</a>
  </div>

  <!-- ===== 親ビュー ===== -->
  <?php else: ?>
  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <h2 style="font-family:var(--font-h);font-size:1.3rem;margin:0">
      🏠 <?= h($user['family_name']) ?> のダッシュボード
    </h2>
    <span class="rate-badge ms-auto">1pt = <?= h(number_format($user['point_rate'])) ?> 円</span>
  </div>

  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="point_redeem.php" class="btn-washi btn-redeem flex-grow-1 text-center text-decoration-none">💴 ポイントをかえる</a>
    <a href="master.php"       class="btn-washi btn-master flex-grow-1 text-center text-decoration-none">📝 おてつだいリスト</a>
    <a href="admin.php"        class="btn-washi btn-admin  flex-grow-1 text-center text-decoration-none">⚙️ かぞく管理</a>
  </div>

  <!-- 子供ポイント一覧 -->
  <div class="sec-title">👧 こどものポイント</div>
  <?php foreach ($children as $child): ?>
  <?php $pts = $child_points[$child['user_id']]; ?>
  <a href="child_detail.php?user_id=<?= h($child['user_id']) ?>" class="child-card" style="background:rgba(255,254,248,.85)">
    <div class="child-avatar" style="background:<?= h($child['avatar_color']) ?>">
      <?= mb_substr($child['display_name'], 0, 1, 'UTF-8') ?>
    </div>
    <div>
      <div class="child-name"><?= h($child['display_name']) ?></div>
      <div class="child-yen">💴 <?= h(number_format($pts * $user['point_rate'])) ?> 円ぶん</div>
    </div>
    <div class="ms-auto text-end">
      <div class="child-pts"><?= h(number_format($pts, 1)) ?></div>
      <div style="font-size:.78rem;color:var(--c-muted)">ポイント</div>
    </div>
    <i class="bi bi-chevron-right" style="color:var(--c-muted)"></i>
  </a>
  <?php endforeach; ?>
  <?php if (empty($children)): ?>
  <p style="color:var(--c-muted);font-size:.9rem">まだこどもがいません。かぞく管理から追加してください。</p>
  <?php endif; ?>
  <?php endif; ?>

  <!-- 最近のきろく -->
  <div class="washi-card mt-1">
    <div class="sec-title">📖 さいきんのきろく</div>
    <?php if (empty($recent_logs)): ?>
    <p style="color:var(--c-muted);font-size:.9rem;text-align:center;padding:1rem 0">まだきろくがありません 🌱</p>
    <?php endif; ?>
    <?php foreach ($recent_logs as $log): ?>
    <div class="log-item">
      <div class="log-icon-wrap" style="background:<?= $log['log_type']==='earn' ? 'rgba(200,240,224,.7)' : 'rgba(252,228,214,.7)' ?>">
        <?= $log['log_type']==='earn' ? '✋' : '💴' ?>
      </div>
      <div style="flex:1;min-width:0">
        <div class="log-task"><?= h($log['task_name']) ?></div>
        <div class="log-meta">
          <?= h($log['display_name']) ?> ·
          <?= h(date('m/d H:i', strtotime($log['created_at']))) ?>
          <?php if ($log['memo']): ?> · <?= h(mb_substr($log['memo'],0,20,'UTF-8')) ?><?php endif; ?>
        </div>
      </div>
      <div class="log-point <?= h($log['log_type']) ?>">
        <?= $log['log_type']==='earn' ? '+' : '-' ?><?= h(number_format(abs($log['point']),1)) ?>pt
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
