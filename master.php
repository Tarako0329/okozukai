<?php
// master.php - ポイントマスタ管理（親のみ）
require_once 'config.php';
$user = require_parent();
$db   = get_db();

$family_id = $user['family_id'];
$success = $error = '';

// 追加・編集・削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $task_name     = trim($_POST['task_name'] ?? '');
        $default_point = (float)($_POST['default_point'] ?? 0);
        $description   = trim($_POST['description'] ?? '');
        $icon          = trim($_POST['icon'] ?? '⭐');
        $sort_order    = (int)($_POST['sort_order'] ?? 0);
        $is_active     = isset($_POST['is_active']) ? 1 : 0;

        if (!$task_name) {
            $error = 'おてつだい名を入力してください。';
        } elseif ($default_point == 0) {
            $error = 'ポイントは0にできません。';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare(
                    'INSERT INTO point_master (family_id,task_name,default_point,description,icon,sort_order,is_active)
                     VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([$family_id,$task_name,$default_point,$description?:null,$icon,$sort_order,$is_active]);
                $success = '「' . $task_name . '」を追加しました！';
            } else {
                $master_id = (int)($_POST['master_id'] ?? 0);
                $stmt = $db->prepare(
                    'UPDATE point_master SET task_name=?,default_point=?,description=?,icon=?,sort_order=?,is_active=?,updated_at=NOW()
                     WHERE master_id=? AND family_id=?'
                );
                $stmt->execute([$task_name,$default_point,$description?:null,$icon,$sort_order,$is_active,$master_id,$family_id]);
                $success = '「' . $task_name . '」を更新しました！';
            }
        }
    } elseif ($action === 'delete') {
        $master_id = (int)($_POST['master_id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM point_master WHERE master_id=? AND family_id=?');
        $stmt->execute([$master_id, $family_id]);
        $success = '削除しました。';
    }
}

// マスタ一覧取得
$stmt = $db->prepare('SELECT * FROM point_master WHERE family_id=? ORDER BY sort_order, master_id');
$stmt->execute([$family_id]);
$masters = $stmt->fetchAll();

// 編集対象
$edit_id     = (int)($_GET['edit'] ?? 0);
$edit_master = null;
if ($edit_id) {
    foreach ($masters as $m) {
        if ($m['master_id'] == $edit_id) { $edit_master = $m; break; }
    }
}

$icons = ['⭐','🍽️','🌀','👕','🛍️','🛁','🗑️','✨','👟','🐶','🌸','🧹','🪣','📚','🍱','💪','🎯','🌈'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>おてつだいリスト管理 | <?= h(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Kaisei+Decol:wght@400;700&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="apple-touch-icon" type="image/png" href="img/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="img/icon-192x192.png">
<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="favicon.ico">
<link rel='manifest' href='site.webmanifest?<?php echo $time;?>'>
<style>
:root{
  --c-sky:#d6eef8;--c-mint:#c8f0e0;--c-peach:#fce4d6;--c-lilac:#e8d8f8;
  --c-ink:#4a4a6a;--c-muted:#9999bb;--c-white:#fffef8;--c-accent:#7bb8d4;
  --font-h:'Kaisei Decol',serif;--font-b:'Zen Maru Gothic',sans-serif;
}
*{box-sizing:border-box;}
body{font-family:var(--font-b);background:var(--c-white);color:var(--c-ink);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;z-index:-1;
  background:
    radial-gradient(ellipse 60% 50% at 15% 10%,rgba(232,216,248,.55) 0%,transparent 55%),
    radial-gradient(ellipse 50% 60% at 85% 90%,rgba(214,238,248,.5) 0%,transparent 55%),
    radial-gradient(ellipse 40% 40% at 50% 50%,rgba(200,240,224,.4) 0%,transparent 50%),
    #fffef8;
}
.navbar-custom{background:rgba(255,254,248,.92);backdrop-filter:blur(10px);
  border-bottom:1.5px solid rgba(123,184,212,.2);padding:.6rem 1.2rem;}
.navbar-brand{font-family:var(--font-h);font-size:1.1rem;color:var(--c-ink)!important;}
.page-wrap{max-width:860px;margin:0 auto;padding:1.5rem 1rem 4rem;}
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
.form-control,.form-select{
  border:1.5px solid rgba(123,184,212,.35);border-radius:14px;
  background:rgba(255,254,248,.9);color:var(--c-ink);font-family:var(--font-b);padding:.6rem 1rem;}
.form-control:focus,.form-select:focus{
  border-color:var(--c-accent);box-shadow:0 0 0 3px rgba(123,184,212,.2);outline:none;}
.form-label{font-weight:700;font-size:.88rem;color:var(--c-ink);}

.master-row{
  display:flex;align-items:center;gap:.8rem;
  padding:.75rem .4rem;border-bottom:1px dashed rgba(123,184,212,.2);
}
.master-row:last-child{border-bottom:none;}
.m-icon{font-size:1.5rem;width:38px;text-align:center;flex-shrink:0;}
.m-name{font-weight:700;font-size:.92rem;}
.m-desc{font-size:.78rem;color:var(--c-muted);}
.m-pt{font-family:var(--font-h);font-size:1.2rem;font-weight:700;color:var(--c-accent);white-space:nowrap;}
.m-inactive{opacity:.45;}
.badge-inactive{background:rgba(200,200,220,.4);color:var(--c-muted);font-size:.7rem;padding:.15rem .5rem;border-radius:50px;}

.btn-edit{background:rgba(214,238,248,.8);border:none;border-radius:50px;
  color:var(--c-ink);font-size:.78rem;font-weight:700;padding:.3rem .8rem;cursor:pointer;}
.btn-edit:hover{background:rgba(123,184,212,.3);}
.btn-del{background:rgba(252,228,214,.8);border:none;border-radius:50px;
  color:#8a4a2a;font-size:.78rem;font-weight:700;padding:.3rem .8rem;cursor:pointer;}
.btn-del:hover{background:rgba(240,160,120,.3);}

.icon-picker{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.4rem;}
.icon-opt{font-size:1.4rem;width:38px;height:38px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  border:2px solid transparent;transition:all .15s;}
.icon-opt:hover{background:rgba(123,184,212,.15);}
.icon-opt.sel{border-color:var(--c-accent);background:rgba(123,184,212,.2);}

.btn-submit{
  background:linear-gradient(135deg,#c4d8f8,#d8c4f8);border:none;border-radius:50px;
  color:#3a3a8a;font-family:var(--font-h);font-weight:700;font-size:1rem;
  padding:.7rem 2rem;cursor:pointer;
  box-shadow:0 4px 16px rgba(140,140,240,.2);
  transition:transform .15s,box-shadow .15s;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(140,140,240,.3);}
.alert-ok{background:rgba(200,240,224,.7);border:1.5px solid rgba(80,180,120,.3);
  border-radius:12px;color:#2a7a4a;padding:.6rem 1rem;font-size:.9rem;margin-bottom:.8rem;font-weight:700;}
.alert-err{background:rgba(252,228,214,.7);border:1.5px solid rgba(240,140,100,.3);
  border-radius:12px;color:#8a4a2a;padding:.6rem 1rem;font-size:.88rem;margin-bottom:.8rem;}
</style>
</head>
<body>
<nav class="navbar navbar-custom sticky-top">
  <div class="container-lg px-3 d-flex align-items-center gap-2">
    <a href="dashboard.php" class="btn-back">← もどる</a>
    <span class="navbar-brand ms-2">📝 おてつだいリスト管理</span>
  </div>
</nav>

<div class="page-wrap">

  <?php if ($success): ?><div class="alert-ok">✅ <?= h($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert-err">⚠️ <?= h($error) ?></div><?php endif; ?>

  <div class="row g-3">
    <!-- 左：追加・編集フォーム -->
    <div class="col-lg-5">
      <div class="washi-card">
        <div class="sec-title"><?= $edit_master ? '✏️ 編集中' : '➕ 新しいおてつだいを追加' ?></div>
        <form method="post" action="master.php">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="<?= $edit_master ? 'edit' : 'add' ?>">
          <?php if ($edit_master): ?>
          <input type="hidden" name="master_id" value="<?= h($edit_master['master_id']) ?>">
          <?php endif; ?>

          <div class="mb-2">
            <label class="form-label">🌟 おてつだい名 *</label>
            <input type="text" name="task_name" class="form-control"
                   value="<?= h($edit_master['task_name'] ?? '') ?>"
                   placeholder="例：おさらあらい" required maxlength="100">
          </div>

          <div class="mb-2">
            <label class="form-label">⭐ ポイント *</label>
            <input type="number" name="default_point" class="form-control"
                   value="<?= h($edit_master['default_point'] ?? '3') ?>"
                   step="0.5" required>
          </div>

          <div class="mb-2">
            <label class="form-label">💬 せつめい</label>
            <input type="text" name="description" class="form-control"
                   value="<?= h($edit_master['description'] ?? '') ?>"
                   placeholder="どんなおてつだいか説明" maxlength="255">
          </div>

          <div class="mb-2">
            <label class="form-label">🎨 アイコン</label>
            <input type="hidden" name="icon" id="iconInput"
                   value="<?= h($edit_master['icon'] ?? '⭐') ?>">
            <div class="icon-picker" id="iconPicker">
              <?php foreach ($icons as $ic): ?>
              <div class="icon-opt <?= ($edit_master['icon'] ?? '⭐') === $ic ? 'sel' : '' ?>"
                   data-icon="<?= h($ic) ?>" onclick="pickIcon(this)"><?= h($ic) ?></div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label">🔢 表示順</label>
            <input type="number" name="sort_order" class="form-control"
                   value="<?= h($edit_master['sort_order'] ?? '0') ?>" min="0">
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" id="isActive" class="form-check-input"
                   <?= ($edit_master['is_active'] ?? 1) ? 'checked' : '' ?>>
            <label for="isActive" class="form-check-label" style="font-weight:700;font-size:.88rem">有効にする</label>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn-submit flex-grow-1">
              <?= $edit_master ? '💾 更新する' : '➕ 追加する' ?>
            </button>
            <?php if ($edit_master): ?>
            <a href="master.php" class="btn-submit text-center text-decoration-none"
               style="background:rgba(200,200,220,.5);color:var(--c-ink)">✕ キャンセル</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- 右：マスタ一覧 -->
    <div class="col-lg-7">
      <div class="washi-card">
        <div class="sec-title">📋 おてつだいリスト（<?= count($masters) ?>件）</div>
        <?php if (empty($masters)): ?>
        <p style="color:var(--c-muted);font-size:.9rem;text-align:center;padding:1rem">まだありません。左から追加してね！</p>
        <?php endif; ?>
        <?php foreach ($masters as $m): ?>
        <div class="master-row <?= $m['is_active'] ? '' : 'm-inactive' ?>">
          <div class="m-icon"><?= h($m['icon']) ?></div>
          <div style="flex:1;min-width:0">
            <div class="m-name">
              <?= h($m['task_name']) ?>
              <?php if (!$m['is_active']): ?><span class="badge-inactive ms-1">非表示</span><?php endif; ?>
            </div>
            <?php if ($m['description']): ?>
            <div class="m-desc"><?= h($m['description']) ?></div>
            <?php endif; ?>
          </div>
          <div class="m-pt"><?= h(number_format($m['default_point'],1)) ?>pt</div>
          <div class="d-flex gap-1">
            <a href="master.php?edit=<?= h($m['master_id']) ?>" class="btn-edit">編集</a>
            <form method="post" action="master.php" onsubmit="return confirm('「<?= h($m['task_name']) ?>」を削除しますか？')">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="master_id" value="<?= h($m['master_id']) ?>">
              <button type="submit" class="btn-del">削除</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function pickIcon(el) {
  document.querySelectorAll('.icon-opt').forEach(e => e.classList.remove('sel'));
  el.classList.add('sel');
  document.getElementById('iconInput').value = el.dataset.icon;
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
