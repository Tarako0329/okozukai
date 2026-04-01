<?php
// child_detail.php - 親が子供の詳細を見る / my_history.php と兼用
require_once 'config.php';
$user = require_login();
$db   = get_db();

$family_id = $user['family_id'];
$is_parent = ($user['role'] === 'parent');

if ($is_parent) {
    $target_id = (int)($_GET['user_id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM users WHERE user_id=? AND family_id=? AND role="child"');
    $stmt->execute([$target_id, $family_id]);
    $target = $stmt->fetch();
    if (!$target) { header('Location: dashboard.php'); exit; }
} else {
    $target_id = (int)$user['user_id'];
    $target    = ['display_name'=>$user['display_name'],'avatar_color'=>$user['avatar_color']];
}

$current_points = get_user_points($db, $target_id);

// ページング
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare('SELECT COUNT(*) FROM point_logs WHERE user_id=?');
$stmt->execute([$target_id]);
$total = (int)$stmt->fetchColumn();
$pages = (int)ceil($total / $limit);

$stmt = $db->prepare(
    'SELECT * FROM point_logs WHERE user_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?'
);
$stmt->execute([$target_id, $limit, $offset]);
$logs = $stmt->fetchAll();

// 月次集計
$stmt = $db->prepare(
    'SELECT DATE_FORMAT(created_at,"%Y-%m") AS ym,
            SUM(CASE WHEN log_type="earn" THEN point ELSE 0 END) AS earned,
            SUM(CASE WHEN log_type="redeem" THEN ABS(point) ELSE 0 END) AS redeemed
     FROM point_logs WHERE user_id=?
     GROUP BY ym ORDER BY ym DESC LIMIT 6'
);
$stmt->execute([$target_id]);
$monthly = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($target['display_name']) ?> のきろく | <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kaisei+Decol:wght@400;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{
  --c-sky:#d6eef8;--c-mint:#c8f0e0;--c-peach:#fce4d6;--c-lemon:#fdf5c0;
  --c-ink:#4a4a6a;--c-muted:#9999bb;--c-white:#fffef8;--c-accent:#7bb8d4;
  --font-h:'Kaisei Decol',serif;--font-b:'Zen Maru Gothic',sans-serif;
}
*{box-sizing:border-box;}
body{font-family:var(--font-b);background:var(--c-white);color:var(--c-ink);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;z-index:-1;
  background:
    radial-gradient(ellipse 70% 50% at 5% 20%,rgba(214,238,248,.55) 0%,transparent 55%),
    radial-gradient(ellipse 50% 60% at 95% 80%,rgba(200,240,224,.5) 0%,transparent 55%),
    #fffef8;
}
.navbar-custom{background:rgba(255,254,248,.92);backdrop-filter:blur(10px);
  border-bottom:1.5px solid rgba(123,184,212,.2);padding:.6rem 1.2rem;}
.navbar-brand{font-family:var(--font-h);font-size:1.1rem;color:var(--c-ink)!important;}
.page-wrap{max-width:800px;margin:0 auto;padding:1.5rem 1rem 4rem;}
.btn-back{display:inline-flex;align-items:center;gap:.4rem;color:var(--c-muted);
  text-decoration:none;font-size:.88rem;margin-bottom:1.2rem;font-weight:700;}
.btn-back:hover{color:var(--c-ink);}
.washi-card{
  background:rgba(255,254,248,.85);backdrop-filter:blur(6px);
  border:1.5px solid rgba(255,255,255,.9);border-radius:22px;
  box-shadow:0 4px 20px rgba(74,74,106,.07);padding:1.4rem 1.6rem;margin-bottom:1.2rem;
}
.sec-title{font-family:var(--font-h);font-size:1.05rem;color:var(--c-ink);
  border-left:4px solid var(--c-accent);padding-left:.7rem;margin-bottom:1rem;}

.hero{
  background:linear-gradient(135deg,rgba(200,240,224,.6),rgba(214,238,248,.6));
  border-radius:24px;padding:1.4rem 1.6rem;margin-bottom:1.2rem;
  border:1.5px solid rgba(255,255,255,.9);
  box-shadow:0 4px 20px rgba(74,74,106,.07);
  display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;
}
.hero-avatar{
  width:64px;height:64px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:1.5rem;font-weight:700;
  border:3px solid rgba(255,255,255,.9);box-shadow:0 2px 12px rgba(0,0,0,.1);flex-shrink:0;
}
.pt-big{font-family:var(--font-h);font-size:2.5rem;font-weight:700;line-height:1;}

/* 月次グラフ */
.monthly-row{display:flex;align-items:center;gap:.8rem;margin-bottom:.6rem;}
.month-label{font-size:.78rem;color:var(--c-muted);width:55px;flex-shrink:0;}
.bar-wrap{flex:1;height:22px;border-radius:50px;background:rgba(200,200,220,.15);overflow:hidden;position:relative;}
.bar-earn{height:100%;border-radius:50px;background:linear-gradient(90deg,#a8e6c8,#c8f0e0);transition:width .6s;}
.month-val{font-size:.82rem;font-weight:700;color:var(--c-accent);white-space:nowrap;}

/* ログ */
.log-item{padding:.75rem 0;border-bottom:1px dashed rgba(123,184,212,.2);display:flex;align-items:center;gap:.8rem;}
.log-item:last-child{border-bottom:none;}
.log-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:1rem;flex-shrink:0;}
.log-task{font-weight:700;font-size:.9rem;}
.log-meta{font-size:.76rem;color:var(--c-muted);}
.log-pt-earn  {font-family:var(--font-h);font-size:1.1rem;font-weight:700;color:#2a9a6a;white-space:nowrap;}
.log-pt-redeem{font-family:var(--font-h);font-size:1.1rem;font-weight:700;color:#c05050;white-space:nowrap;}

.pagination .page-link{border-radius:50px;border:1.5px solid rgba(123,184,212,.3);color:var(--c-ink);
  font-family:var(--font-b);font-size:.85rem;padding:.35rem .8rem;}
.pagination .page-item.active .page-link{background:var(--c-accent);border-color:var(--c-accent);color:#fff;}
</style>
</head>
<body>
<nav class="navbar navbar-custom sticky-top">
  <div class="container-lg px-3 d-flex align-items-center gap-2">
    <a href="dashboard.php" class="btn-back">← もどる</a>
    <span class="navbar-brand ms-2">📖 <?= h($target['display_name']) ?> のきろく</span>
  </div>
</nav>

<div class="page-wrap">

  <!-- ヒーロー -->
  <div class="hero">
    <div class="hero-avatar" style="background:<?= h($target['avatar_color']) ?>">
      <?= mb_substr($target['display_name'],0,1,'UTF-8') ?>
    </div>
    <div>
      <div style="font-size:.82rem;color:var(--c-muted)">いまのポイント</div>
      <div class="pt-big"><?= h(number_format($current_points,1)) ?> <span style="font-size:1rem;color:var(--c-muted)">pt</span></div>
      <div style="font-size:.82rem;font-weight:700">💴 <?= h(number_format($current_points * $user['point_rate'])) ?> 円ぶん</div>
    </div>
    <div class="ms-auto text-end d-none d-sm-block">
      <div style="font-size:.78rem;color:var(--c-muted)">ぜんぶで <?= h($total) ?> けん</div>
    </div>
  </div>

  <!-- 月次 -->
  <?php if (!empty($monthly)): ?>
  <div class="washi-card">
    <div class="sec-title">📊 つきごとのきろく</div>
    <?php
    $max_earn = max(array_column($monthly, 'earned') ?: [1]);
    foreach ($monthly as $mo):
      [$y, $m] = explode('-', $mo['ym']);
      $pct = $max_earn > 0 ? min(100, round($mo['earned'] / $max_earn * 100)) : 0;
    ?>
    <div class="monthly-row">
      <div class="month-label"><?= h($y) ?>/<br><?= h($m) ?></div>
      <div class="bar-wrap"><div class="bar-earn" style="width:<?= $pct ?>%"></div></div>
      <div class="month-val">+<?= h(number_format($mo['earned'],1)) ?>pt</div>
      <?php if ($mo['redeemed'] > 0): ?>
      <div style="font-size:.78rem;color:#c05050;white-space:nowrap">-<?= h(number_format($mo['redeemed'],1)) ?>pt換金</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- 履歴 -->
  <div class="washi-card">
    <div class="sec-title">📋 ポイントのきろく</div>
    <?php if (empty($logs)): ?>
    <p style="color:var(--c-muted);text-align:center;padding:1rem;font-size:.9rem">まだきろくがありません 🌱</p>
    <?php endif; ?>
    <?php foreach ($logs as $log): ?>
    <div class="log-item">
      <div class="log-icon" style="background:<?= $log['log_type']==='earn' ? 'rgba(200,240,224,.7)' : 'rgba(252,228,214,.7)' ?>">
        <?= $log['log_type']==='earn' ? '✋' : '💴' ?>
      </div>
      <div style="flex:1;min-width:0">
        <div class="log-task"><?= h($log['task_name']) ?></div>
        <div class="log-meta">
          <?= h(date('Y/m/d H:i', strtotime($log['created_at']))) ?>
          <?php if ($log['memo']): ?> · <?= h($log['memo']) ?><?php endif; ?>
        </div>
      </div>
      <?php if ($log['log_type']==='earn'): ?>
      <div class="log-pt-earn">+<?= h(number_format(abs($log['point']),1)) ?>pt</div>
      <?php else: ?>
      <div class="log-pt-redeem">-<?= h(number_format(abs($log['point']),1)) ?>pt</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- ページング -->
    <?php if ($pages > 1): ?>
    <nav class="mt-3">
      <ul class="pagination justify-content-center gap-1 flex-wrap">
        <?php for ($p=1; $p<=$pages; $p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>">
          <a class="page-link" href="?<?= $is_parent ? 'user_id='.h($target_id).'&' : '' ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
