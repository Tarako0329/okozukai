<?php
// point_redeem.php - 親がポイントを換金する
require_once 'config.php';
$user = require_parent();
$db   = get_db();

$family_id  = $user['family_id'];
$success    = '';
$error      = '';

// 家族内の子供一覧
$stmt = $db->prepare('SELECT user_id, display_name, avatar_color FROM users WHERE family_id = ? AND role = "child" ORDER BY user_id');
$stmt->execute([$family_id]);
$children = $stmt->fetchAll();

$child_points = [];
foreach ($children as $c) {
    $child_points[$c['user_id']] = get_user_points($db, (int)$c['user_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);
    $redeem_point   = (float)($_POST['redeem_point'] ?? 0);
    $memo           = trim($_POST['memo'] ?? '');

    // 対象チェック
    $stmt = $db->prepare('SELECT * FROM users WHERE user_id = ? AND family_id = ? AND role = "child"');
    $stmt->execute([$target_user_id, $family_id]);
    $target = $stmt->fetch();

    $current = $child_points[$target_user_id] ?? 0;

    if (!$target) {
        $error = '対象の子供を選んでください。';
    } elseif ($redeem_point <= 0) {
        $error = '換金ポイントは1以上にしてください。';
    } elseif ($redeem_point > $current) {
        $error = '持っているポイントをこえています。';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO point_logs (family_id, user_id, master_id, task_name, point, memo, log_type, redeemed_by, redeemed_at)
             VALUES (?, ?, NULL, "ポイント換金", ?, ?, "redeem", ?, NOW())'
        );
        $stmt->execute([$family_id, $target_user_id, -abs($redeem_point), $memo ?: null, $user['user_id']]);

        // 換金後のポイント更新
        $child_points[$target_user_id] = get_user_points($db, $target_user_id);
        $success = sprintf(
            '%s の %s pt を換金しました（%s 円）',
            $target['display_name'],
            number_format($redeem_point, 1),
            number_format($redeem_point * $user['point_rate'])
        );
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ポイント換金 | <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kaisei+Decol:wght@400;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{
  --c-sky:#d6eef8;--c-mint:#c8f0e0;--c-peach:#fce4d6;--c-lemon:#fdf5c0;
  --c-lilac:#e8d8f8;--c-ink:#4a4a6a;--c-muted:#9999bb;--c-white:#fffef8;
  --c-accent:#7bb8d4;--font-h:'Kaisei Decol',serif;--font-b:'Zen Maru Gothic',sans-serif;
}
*{box-sizing:border-box;}
body{font-family:var(--font-b);background:var(--c-white);color:var(--c-ink);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;z-index:-1;
  background:
    radial-gradient(ellipse 60% 50% at 80% 10%,rgba(252,228,214,.6) 0%,transparent 55%),
    radial-gradient(ellipse 50% 60% at 10% 90%,rgba(214,238,248,.5) 0%,transparent 55%),
    radial-gradient(ellipse 40% 40% at 50% 50%,rgba(253,245,192,.4) 0%,transparent 50%),
    #fffef8;
}
.navbar-custom{background:rgba(255,254,248,.92);backdrop-filter:blur(10px);
  border-bottom:1.5px solid rgba(123,184,212,.2);padding:.6rem 1.2rem;}
.navbar-brand{font-family:var(--font-h);font-size:1.1rem;color:var(--c-ink)!important;}
.page-wrap{max-width:700px;margin:0 auto;padding:1.5rem 1rem 4rem;}
.btn-back{display:inline-flex;align-items:center;gap:.4rem;color:var(--c-muted);
  text-decoration:none;font-size:.88rem;margin-bottom:1.2rem;font-weight:700;}
.btn-back:hover{color:var(--c-ink);}

.washi-card{
  background:rgba(255,254,248,.85);backdrop-filter:blur(6px);
  border:1.5px solid rgba(255,255,255,.9);border-radius:22px;
  box-shadow:0 4px 20px rgba(74,74,106,.07);padding:1.4rem 1.6rem;margin-bottom:1.2rem;
}
.sec-title{font-family:var(--font-h);font-size:1.05rem;color:var(--c-ink);
  border-left:4px solid #e8a0a0;padding-left:.7rem;margin-bottom:1rem;}

/* 子供選択カード */
.child-select{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.7rem;margin-bottom:.5rem;}
.child-sel-btn{
  border:2px solid rgba(200,200,220,.35);border-radius:18px;
  background:rgba(255,254,248,.85);padding:.9rem .7rem;text-align:center;
  cursor:pointer;transition:all .18s;
}
.child-sel-btn:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(74,74,106,.1);}
.child-sel-btn.selected{
  border-color:#e8a0a0;background:rgba(252,228,214,.3);
  box-shadow:0 0 0 3px rgba(232,160,160,.25),0 6px 18px rgba(74,74,106,.08);
}
.c-avatar{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:1.1rem;font-weight:700;margin:0 auto .4rem;
  border:2.5px solid rgba(255,255,255,.9);box-shadow:0 2px 8px rgba(0,0,0,.08);}
.c-name{font-size:.82rem;font-weight:700;}
.c-pts{font-family:var(--font-h);font-size:1.1rem;font-weight:700;color:var(--c-accent);}
.c-yen{font-size:.72rem;color:var(--c-muted);}

.form-control,.form-select{
  border:1.5px solid rgba(123,184,212,.35);border-radius:14px;
  background:rgba(255,254,248,.9);color:var(--c-ink);font-family:var(--font-b);
  padding:.6rem 1rem;}
.form-control:focus,.form-select:focus{border-color:#e8a0a0;box-shadow:0 0 0 3px rgba(232,160,160,.2);outline:none;}
.form-label{font-weight:700;font-size:.88rem;color:var(--c-ink);}

.yen-preview{
  background:rgba(253,245,192,.7);border:1.5px solid rgba(200,180,80,.25);
  border-radius:12px;padding:.5rem 1rem;font-weight:700;font-size:1rem;color:#7a6a2a;
  display:none;margin-top:.5rem;
}

.btn-submit{
  width:100%;background:linear-gradient(135deg,#f8c4c4,#fce4d6);
  border:none;border-radius:50px;color:#8a3030;
  font-family:var(--font-h);font-weight:700;font-size:1.1rem;
  padding:.8rem;cursor:pointer;
  box-shadow:0 4px 16px rgba(240,160,160,.3);
  transition:transform .15s,box-shadow .15s;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(240,160,160,.4);}

.alert-success-custom{
  background:rgba(200,240,224,.7);border:1.5px solid rgba(80,180,120,.3);
  border-radius:16px;color:#2a7a4a;padding:1rem 1.4rem;margin-bottom:1rem;
  font-weight:700;text-align:center;
}
.alert-err{
  background:rgba(252,228,214,.7);border:1.5px solid rgba(240,140,100,.3);
  border-radius:12px;color:#8a4a2a;padding:.6rem 1rem;font-size:.88rem;margin-bottom:.8rem;
}
.warn-box{
  background:rgba(253,245,192,.7);border:1.5px solid rgba(200,180,80,.25);
  border-radius:12px;padding:.7rem 1rem;font-size:.85rem;color:#7a6a2a;margin-bottom:1rem;
}
</style>
</head>
<body>
<nav class="navbar navbar-custom sticky-top">
  <div class="container-lg px-3 d-flex align-items-center gap-2">
    <a href="dashboard.php" class="btn-back">← もどる</a>
    <span class="navbar-brand ms-2">💴 ポイントを換金する</span>
  </div>
</nav>

<div class="page-wrap">

  <?php if ($success): ?>
  <div class="alert-success-custom">✅ <?= h($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert-err">⚠️ <?= h($error) ?></div>
  <?php endif; ?>

  <div class="warn-box">
    ⚠️ 換金したポイントは <strong>消えます</strong>。確認してから換金してください。<br>
    1ポイント = <?= h(number_format($user['point_rate'])) ?> 円
  </div>

  <form method="post" action="point_redeem.php" id="redeemForm">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="target_user_id" id="target_user_id" value="">

    <!-- 子供選択 -->
    <div class="washi-card">
      <div class="sec-title">👧 だれのポイントをかえる？</div>
      <?php if (empty($children)): ?>
      <p style="color:var(--c-muted);font-size:.9rem">こどもがいません。</p>
      <?php else: ?>
      <div class="child-select">
        <?php foreach ($children as $c): ?>
        <?php $pts = $child_points[$c['user_id']]; ?>
        <div class="child-sel-btn"
             data-id="<?= h($c['user_id']) ?>"
             data-pts="<?= h($pts) ?>"
             onclick="selectChild(this)">
          <div class="c-avatar" style="background:<?= h($c['avatar_color']) ?>">
            <?= mb_substr($c['display_name'],0,1,'UTF-8') ?>
          </div>
          <div class="c-name"><?= h($c['display_name']) ?></div>
          <div class="c-pts"><?= h(number_format($pts,1)) ?>pt</div>
          <div class="c-yen">💴<?= h(number_format($pts*$user['point_rate'])) ?>円</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- 換金ポイント -->
    <div class="washi-card">
      <div class="sec-title">💴 なんポイントかえる？</div>
      <div class="mb-3">
        <label class="form-label">換金ポイント数</label>
        <input type="number" name="redeem_point" id="redeemPoint" class="form-control"
               step="0.5" min="0.5" placeholder="例：10" required
               oninput="updatePreview()">
        <div class="yen-preview" id="yenPreview">≒ 0 円</div>
      </div>
      <div class="mb-3">
        <label class="form-label">💬 メモ（なくてもいい）</label>
        <textarea name="memo" class="form-control" rows="2" placeholder="メモ"></textarea>
      </div>
      <button type="submit" class="btn-submit" onclick="return confirmRedeem()">💴 換金する</button>
    </div>

  </form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const rate = <?= (float)$user['point_rate'] ?>;
let selectedPts = 0;

function selectChild(el) {
  document.querySelectorAll('.child-sel-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('target_user_id').value = el.dataset.id;
  selectedPts = parseFloat(el.dataset.pts);
  updatePreview();
}

function updatePreview() {
  const pt = parseFloat(document.getElementById('redeemPoint').value) || 0;
  const preview = document.getElementById('yenPreview');
  if (pt > 0) {
    preview.style.display = 'block';
    preview.textContent = '≒ ' + Math.round(pt * rate).toLocaleString() + ' 円';
  } else {
    preview.style.display = 'none';
  }
}

function confirmRedeem() {
  const id  = document.getElementById('target_user_id').value;
  const pt  = parseFloat(document.getElementById('redeemPoint').value) || 0;
  if (!id)  { alert('こどもを選んでください。'); return false; }
  if (pt<=0){ alert('ポイントを入力してください。'); return false; }
  if (pt > selectedPts){ alert('もっているポイントをこえています。'); return false; }
  return confirm(`${pt}ポイントを換金しますか？\nかんきんしたポイントは消えます。`);
}
</script>
</body>
</html>
