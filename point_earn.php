<?php
// point_earn.php - 子供がお手伝いポイントを登録する
require_once 'config.php';
$user = require_login();

if ($user['role'] !== 'child') {
    header('Location: dashboard.php'); exit;
}

$db        = get_db();
$family_id = $user['family_id'];
$success   = false;
$error     = '';

// ポイントマスタ取得
$stmt = $db->prepare(
    'SELECT * FROM point_master WHERE family_id = ? AND is_active = 1 ORDER BY sort_order, master_id'
);
$stmt->execute([$family_id]);
$masters = $stmt->fetchAll();

// POSTで登録
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $master_id = (int)($_POST['master_id'] ?? 0);
    $point     = (float)($_POST['point'] ?? 0);
    $memo      = trim($_POST['memo'] ?? '');

    // マスタの存在チェック
    $stmt = $db->prepare('SELECT * FROM point_master WHERE master_id = ? AND family_id = ?');
    $stmt->execute([$master_id, $family_id]);
    $master = $stmt->fetch();

    if (!$master) {
        $error = 'おてつだいを選んでください。';
    } elseif ($point == 0) {
        $error = 'ポイントは0にできません。';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO point_logs (family_id, user_id, master_id, task_name, point, memo, log_type,earn_at)
             VALUES (?, ?, ?, ?, ?, ?, "earn", ?)'
        );
        $stmt->execute([$family_id, $user['user_id'], $master_id, $master['task_name'], $point, $memo ?: null, $_POST['date'] ?? date('Y-m-d')]);
        $success = true;
    }
}

$my_points = get_user_points($db, (int)$user['user_id']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>おてつだいする | <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kaisei+Decol:wght@400;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="apple-touch-icon" type="image/png" href="img/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="img/icon-192x192.png">
<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="favicon.ico">
<link rel='manifest' href='site.webmanifest?<?php echo $time;?>'>
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
    radial-gradient(ellipse 70% 50% at 10% 20%,rgba(200,240,224,.55) 0%,transparent 55%),
    radial-gradient(ellipse 50% 60% at 90% 80%,rgba(214,238,248,.5) 0%,transparent 55%),
    radial-gradient(ellipse 40% 40% at 60% 10%,rgba(253,245,192,.4) 0%,transparent 50%),
    #fffef8;
}
.navbar-custom{background:rgba(255,254,248,.92);backdrop-filter:blur(10px);
  border-bottom:1.5px solid rgba(123,184,212,.2);padding:.6rem 1.2rem;}
.navbar-brand{font-family:var(--font-h);font-size:1.1rem;color:var(--c-ink)!important;}
.page-wrap{max-width:680px;margin:0 auto;padding:1.5rem 1rem 4rem;}

.points-bar{
  background:linear-gradient(135deg,rgba(200,240,224,.7),rgba(214,238,248,.7));
  border-radius:20px;padding:1rem 1.4rem;margin-bottom:1.4rem;
  display:flex;align-items:center;gap:1rem;
  border:1.5px solid rgba(255,255,255,.9);
  box-shadow:0 3px 16px rgba(74,74,106,.07);
}
.pt-label{font-size:.82rem;color:var(--c-muted);}
.pt-val{font-family:var(--font-h);font-size:1.8rem;font-weight:700;line-height:1;}

/* マスタカード選択グリッド */
.master-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.8rem;margin-bottom:1.2rem;}
.master-btn{
  position:relative;
  background:rgba(255,254,248,.85);
  border:2px solid rgba(200,200,220,.35);
  border-radius:18px;padding:1rem .8rem;
  text-align:center;cursor:pointer;
  transition:all .18s;
  box-shadow:0 2px 10px rgba(74,74,106,.06);
}
.master-btn:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(74,74,106,.12);}
.master-btn.selected{
  border-color:var(--c-accent);
  background:rgba(123,184,212,.12);
  box-shadow:0 0 0 3px rgba(123,184,212,.25),0 6px 20px rgba(74,74,106,.1);
}
.master-icon{font-size:1.8rem;margin-bottom:.3rem;}
.master-name{font-size:.82rem;font-weight:700;color:var(--c-ink);}
.master-pt{font-family:var(--font-h);font-size:1.1rem;font-weight:700;color:var(--c-accent);}

.washi-card{
  background:rgba(255,254,248,.85);backdrop-filter:blur(6px);
  border:1.5px solid rgba(255,255,255,.9);border-radius:22px;
  box-shadow:0 4px 20px rgba(74,74,106,.07);padding:1.4rem 1.6rem;margin-bottom:1.2rem;
}
.sec-title{font-family:var(--font-h);font-size:1.05rem;color:var(--c-ink);
  border-left:4px solid var(--c-accent);padding-left:.7rem;margin-bottom:1rem;}
.form-control,.form-select{
  border:1.5px solid rgba(123,184,212,.35);border-radius:14px;
  background:rgba(255,254,248,.9);color:var(--c-ink);font-family:var(--font-b);
  padding:.6rem 1rem;}
.form-control:focus,.form-select:focus{
  border-color:var(--c-accent);box-shadow:0 0 0 3px rgba(123,184,212,.2);outline:none;}
.form-label{font-weight:700;font-size:.88rem;color:var(--c-ink);}

.btn-submit{
  width:100%;background:linear-gradient(135deg,#a8e6c8,#c8f0e0);
  border:none;border-radius:50px;color:#2a6a4a;
  font-family:var(--font-h);font-weight:700;font-size:1.1rem;
  padding:.8rem;cursor:pointer;
  box-shadow:0 4px 16px rgba(100,200,160,.3);
  transition:transform .15s,box-shadow .15s;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(100,200,160,.4);}
.btn-back{
  display:inline-flex;align-items:center;gap:.4rem;
  color:var(--c-muted);text-decoration:none;font-size:.88rem;margin-bottom:1.2rem;
  font-weight:700;
}
.btn-back:hover{color:var(--c-ink);}

.alert-success-custom{
  background:rgba(200,240,224,.7);border:1.5px solid rgba(80,180,120,.3);
  border-radius:16px;color:#2a7a4a;padding:1rem 1.4rem;margin-bottom:1rem;
  font-weight:700;text-align:center;font-size:1rem;
}
.alert-err{
  background:rgba(252,228,214,.7);border:1.5px solid rgba(240,140,100,.3);
  border-radius:12px;color:#8a4a2a;padding:.6rem 1rem;font-size:.88rem;
}

/* 成功アニメーション */
@keyframes pop{0%{transform:scale(.5);opacity:0}70%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}
.pop-anim{animation:pop .4s ease both;}
</style>
</head>
<body>
<nav class="navbar navbar-custom sticky-top">
  <div class="container-lg px-3 d-flex align-items-center gap-2">
    <a href="dashboard.php" class="btn-back">← もどる</a>
    <span class="navbar-brand ms-2">✋ おてつだいする</span>
  </div>
</nav>

<div class="page-wrap">

  <?php if ($success): ?>
  <div class="alert-success-custom pop-anim">
    🎉 ポイントをきろくしたよ！<br>
    <span style="font-family:var(--font-h);font-size:1.5rem"><?= h(number_format($my_points, 1)) ?> pt</span> になったよ！
    <div class="mt-2">
      <a href="point_earn.php" style="color:#2a7a4a;font-size:.9rem">もう1つ登録する</a>
      &nbsp;|&nbsp;
      <a href="dashboard.php" style="color:#2a7a4a;font-size:.9rem">ホームにもどる</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- 現在ポイント -->
  <div class="points-bar">
    <div style="font-size:1.8rem">⭐</div>
    <div>
      <div class="pt-label">いまのポイント</div>
      <div class="pt-val"><?= h(number_format($my_points, 1)) ?> <span style="font-size:.85rem;color:var(--c-muted)">pt</span></div>
    </div>
    <div class="ms-auto text-end">
      <div style="font-size:.75rem;color:var(--c-muted)">1pt = <?= h($user['point_rate']) ?> 円</div>
      <div style="font-weight:700;font-size:.95rem">💴 <?= h(number_format($my_points * $user['point_rate'])) ?> 円ぶん</div>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="alert-err mb-3"><?= h($error) ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post" action="point_earn.php" id="earnForm">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="master_id" id="master_id" value="">

    <!-- マスタ選択 -->
    <div class="washi-card">
      <div class="sec-title">🌟 どのおてつだいをしたの？</div>
      <?php if (empty($masters)): ?>
      <p style="color:var(--c-muted);font-size:.9rem">おてつだいリストがありません。おやさんに追加してもらおう！</p>
      <?php else: ?>
      <div class="master-grid">
        <?php foreach ($masters as $m): ?>
        <div class="master-btn" data-id="<?= h($m['master_id']) ?>" data-pt="<?= h($m['default_point']) ?>"
             onclick="selectMaster(this)">
          <div class="master-icon"><?= h($m['icon']) ?></div>
          <div class="master-name"><?= h($m['task_name']) ?></div>
          <div class="master-pt"><?= h(number_format($m['default_point'],1)) ?>pt</div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ポイント・メモ -->
    <div class="washi-card">
      <div class="sec-title">📝 ポイントとメモ</div>
      <div class="mb-3">
        <label class="form-label">⭐ 日付</label>
        <input type="date" name="date" id="dateInput" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">⭐ ポイント（かえてもいいよ）</label>
        <input type="number" name="point" id="pointInput" class="form-control"
               step="1" min="-999" max="9999" placeholder="ポイントをにゅうりょく" required>
      </div>
      <div class="mb-3">
        <label class="form-label">💬 メモ（なくてもいい）</label>
        <textarea name="memo" class="form-control" rows="2"
                  placeholder="どんなふうにしたか書いてね"></textarea>
      </div>
      <button type="submit" class="btn-submit">✨ きろくする！</button>
    </div>
  </form>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectMaster(el) {
  document.querySelectorAll('.master-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('master_id').value  = el.dataset.id;
  document.getElementById('pointInput').value = el.dataset.pt;
  // スクロール
  document.getElementById('pointInput').scrollIntoView({behavior:'smooth', block:'center'});
}
</script>
<script>
	if ('serviceWorker' in navigator) {
		// ページ読み込み完了後に登録を実行（初期表示の速度を落とさないため）
		window.addEventListener('load', function() {
			navigator.serviceWorker.register('serviceworker.js')
				.then(registration => {
					// 登録成功
					console.log("Service Worker の登録に成功しました！");

					// 更新が見つかった時の処理
					registration.onupdatefound = function() {
						console.log('新しい Service Worker を検知しました。更新中...');
					};
				})
				.catch(err => {
					// 登録失敗
					console.error("Service Worker の登録に失敗しました:", err);
				});
		});
	}

	// PWA環境（インストール済みアプリとして起動）の判定
	if (window.matchMedia('(display-mode: standalone)').matches) {
		console.log("PWA環境で実行されています。");
		// ここにアプリ専用の処理（例：戻るボタンの表示調整など）を記述
	}
</script>
</body>
</html>
