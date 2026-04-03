<?php
// admin.php - 家族・ユーザー管理（親のみ）
require_once 'config.php';
use classes\Security\Security;

$user = require_parent();

$db   = get_db();

$family_id = $user['family_id'];
$success = $error = '';
$security = new Security($family_id,KEY);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ---- 家族情報更新 ----
    if ($action === 'update_family') {
        $family_name = trim($_POST['family_name'] ?? '');
        $point_rate  = (float)($_POST['point_rate'] ?? 1);
        if (!$family_name) { $error = '家族名を入力してください。'; }
        elseif ($point_rate <= 0) { $error = 'ポイントレートは0より大きい値にしてください。'; }
        else {
            $db->prepare('UPDATE families SET family_name=?,point_rate=? WHERE family_id=?')
               ->execute([$family_name, $point_rate, $family_id]);
            $_SESSION['user']['family_name'] = $family_name;
            $_SESSION['user']['point_rate']  = $point_rate;
            $user = $_SESSION['user'];
            $success = '家族情報を更新しました！';
        }
    }

    // ---- ユーザー追加 ----
    elseif ($action === 'add_user') {
        $username     = trim($_POST['username'] ?? '');
        $display_name = trim($_POST['display_name'] ?? '');
        $password     = $_POST['password'] ?? '';
        $role         = in_array($_POST['role']??'', ['parent','child']) ? $_POST['role'] : 'child';
        $avatar_color = $_POST['avatar_color'] ?? '#A4F8C8';

        if (!$username || !$display_name || !$password) {
            $error = '必須項目を入力してください。';
        } elseif (strlen($password) < 4) {
            $error = 'パスワードは4文字以上にしてください。';
        } else {
            // 重複チェック
            $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE family_id=? AND username=?');
            $stmt->execute([$family_id, $username]);
            if ($stmt->fetchColumn()) {
                $error = 'そのユーザー名はすでに使われています。';
            } else {
                $hash = $security->passEx($password);
                $db->prepare(
                    'INSERT INTO users (family_id,username,password_hash,display_name,role,avatar_color)
                     VALUES (?,?,?,?,?,?)'
                )->execute([$family_id,$username,$hash,$display_name,$role,$avatar_color]);
                $success = h($display_name) . ' を追加しました！';
            }
        }
    }

    // ---- ユーザー削除 ----
    elseif ($action === 'delete_user') {
        $del_id = (int)($_POST['del_user_id'] ?? 0);
        if ($del_id === (int)$user['user_id']) {
            $error = '自分自身は削除できません。';
        } else {
            $db->prepare('DELETE FROM users WHERE user_id=? AND family_id=?')->execute([$del_id,$family_id]);
            $success = 'ユーザーを削除しました。';
        }
    }

    // ---- パスワード変更 ----
    elseif ($action === 'change_pw') {
        $target_uid = (int)($_POST['pw_user_id'] ?? 0);
        $new_pw     = $_POST['new_password'] ?? '';
        if (strlen($new_pw) < 4) {
            $error = 'パスワードは4文字以上にしてください。';
        } else {
            $hash = $security->passEx($new_pw);
            $db->prepare('UPDATE users SET password_hash=? WHERE user_id=? AND family_id=?')
               ->execute([$hash, $target_uid, $family_id]);
            $success = 'パスワードを変更しました。';
        }
    }
}

// 家族情報再取得
$family = $db->prepare('SELECT * FROM families WHERE family_id=?');
$family->execute([$family_id]);
$family = $family->fetch();

// ユーザー一覧
$stmt = $db->prepare('SELECT * FROM users WHERE family_id=? ORDER BY role DESC, user_id');
$stmt->execute([$family_id]);
$members = $stmt->fetchAll();

$avatar_colors = ['#FFB6C1','#F8A4C8','#A4C8F8','#A4F8C8','#F8D4A4','#D4A4F8','#A4F8F8','#F8F8A4','#C8F0A4','#F0C8A4'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>かぞく管理 | <?= h(APP_NAME) ?></title>
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
  --c-ink:#4a4a6a;--c-muted:#9999bb;--c-white:#fffef8;--c-accent:#7bb8d4;
  --font-h:'Kaisei Decol',serif;--font-b:'Zen Maru Gothic',sans-serif;
}
*{box-sizing:border-box;}
body{font-family:var(--font-b);background:var(--c-white);color:var(--c-ink);min-height:100vh;}
body::before{content:'';position:fixed;inset:0;z-index:-1;
  background:
    radial-gradient(ellipse 60% 50% at 20% 10%,rgba(253,245,192,.55) 0%,transparent 55%),
    radial-gradient(ellipse 50% 60% at 80% 85%,rgba(232,216,248,.5) 0%,transparent 55%),
    radial-gradient(ellipse 40% 40% at 50% 50%,rgba(200,240,224,.35) 0%,transparent 50%),
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
  border-left:4px solid #f0c060;padding-left:.7rem;margin-bottom:1rem;}
.form-control,.form-select{
  border:1.5px solid rgba(123,184,212,.35);border-radius:14px;
  background:rgba(255,254,248,.9);color:var(--c-ink);font-family:var(--font-b);padding:.6rem 1rem;}
.form-control:focus,.form-select:focus{
  border-color:#f0c060;box-shadow:0 0 0 3px rgba(240,192,96,.2);outline:none;}
.form-label{font-weight:700;font-size:.88rem;color:var(--c-ink);}

.member-row{display:flex;align-items:center;gap:.8rem;padding:.75rem .4rem;
  border-bottom:1px dashed rgba(123,184,212,.2);}
.member-row:last-child{border-bottom:none;}
.m-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:1rem;font-weight:700;border:2.5px solid rgba(255,255,255,.9);
  box-shadow:0 2px 8px rgba(0,0,0,.08);flex-shrink:0;}
.m-name{font-weight:700;font-size:.92rem;}
.m-sub{font-size:.76rem;color:var(--c-muted);}
.role-badge{font-size:.7rem;padding:.15rem .5rem;border-radius:50px;font-weight:700;}
.role-badge.parent{background:rgba(232,216,248,.8);color:#6a4a9a;}
.role-badge.child {background:rgba(200,240,224,.8);color:#2a7a5a;}

.btn-washi{border-radius:50px;font-family:var(--font-b);font-weight:700;
  padding:.4rem 1rem;border:none;cursor:pointer;font-size:.82rem;
  transition:transform .12s,box-shadow .12s;}
.btn-washi:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(74,74,106,.12);}
.btn-add {background:linear-gradient(135deg,#fdf5c0,#fce4d6);color:#8a6a2a;}
.btn-del {background:rgba(252,228,214,.8);color:#8a4a2a;}
.btn-pw  {background:rgba(214,238,248,.8);color:#2a4a8a;}

.color-picker{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.3rem;}
.color-dot{width:30px;height:30px;border-radius:50%;cursor:pointer;
  border:2px solid transparent;transition:all .15s;}
.color-dot.sel{border-color:var(--c-ink);transform:scale(1.2);}

.btn-submit{
  background:linear-gradient(135deg,#fdf5c0,#f8d4a4);border:none;border-radius:50px;
  color:#7a5a2a;font-family:var(--font-h);font-weight:700;font-size:1rem;
  padding:.65rem 2rem;cursor:pointer;
  box-shadow:0 4px 16px rgba(240,200,96,.2);transition:transform .15s,box-shadow .15s;
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(240,200,96,.3);}

.alert-ok {background:rgba(200,240,224,.7);border:1.5px solid rgba(80,180,120,.3);
  border-radius:12px;color:#2a7a4a;padding:.6rem 1rem;font-size:.9rem;margin-bottom:.8rem;font-weight:700;}
.alert-err{background:rgba(252,228,214,.7);border:1.5px solid rgba(240,140,100,.3);
  border-radius:12px;color:#8a4a2a;padding:.6rem 1rem;font-size:.88rem;margin-bottom:.8rem;}

/* パスワード変更モーダル */
.modal-content{border-radius:22px;border:1.5px solid rgba(123,184,212,.2);background:var(--c-white);}
.modal-header{border-bottom:1px dashed rgba(123,184,212,.2);}
</style>
</head>
<body>
<nav class="navbar navbar-custom sticky-top">
  <div class="container-lg px-3 d-flex align-items-center gap-2">
    <a href="dashboard.php" class="btn-back">← もどる</a>
    <span class="navbar-brand ms-2">⚙️ かぞく管理</span>
  </div>
</nav>

<div class="page-wrap">

  <?php if ($success): ?><div class="alert-ok">✅ <?= h($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert-err">⚠️ <?= h($error) ?></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-6">

      <!-- 家族設定 -->
      <div class="washi-card">
        <div class="sec-title">🏠 かぞくの設定</div>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="update_family">
          <div class="mb-3">
            <label class="form-label">家族名</label>
            <input type="text" name="family_name" class="form-control"
                   value="<?= h($family['family_name']) ?>" required maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label">1ポイント = 何円？</label>
            <input type="number" name="point_rate" class="form-control"
                   value="<?= h($family['point_rate']) ?>" step="0.01" min="0.01" required>
          </div>
          <button type="submit" class="btn-submit">💾 保存する</button>
        </form>
      </div>

      <!-- メンバー追加 -->
      <div class="washi-card">
        <div class="sec-title">➕ メンバーを追加</div>
        <form method="post" id="addForm">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="add_user">
          <input type="hidden" name="avatar_color" id="avatarColorInput" value="<?= h($avatar_colors[0]) ?>">

          <div class="mb-2">
            <label class="form-label">名前（表示名）*</label>
            <input type="text" name="display_name" class="form-control" placeholder="はな" required maxlength="100">
          </div>
          <div class="mb-2">
            <label class="form-label">ログイン名 *</label>
            <input type="text" name="username" class="form-control" placeholder="hana" required maxlength="50">
          </div>
          <div class="mb-2">
            <label class="form-label">パスワード *（4文字以上）</label>
            <input type="password" name="password" class="form-control" required minlength="4">
          </div>
          <div class="mb-2">
            <label class="form-label">役割</label>
            <select name="role" class="form-select">
              <option value="child">👧 こども</option>
              <option value="parent">👑 おやさん</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">アバターカラー</label>
            <div class="color-picker">
              <?php foreach ($avatar_colors as $i => $color): ?>
              <div class="color-dot <?= $i===0?'sel':'' ?>"
                   style="background:<?= h($color) ?>"
                   data-color="<?= h($color) ?>"
                   onclick="pickColor(this)"></div>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="submit" class="btn-submit">➕ 追加する</button>
        </form>
      </div>

    </div>

    <div class="col-lg-6">
      <!-- メンバー一覧 -->
      <div class="washi-card">
        <div class="sec-title">👨‍👩‍👧 かぞくメンバー（<?= count($members) ?>人）</div>
        <?php foreach ($members as $m): ?>
        <div class="member-row">
          <div class="m-avatar" style="background:<?= h($m['avatar_color']) ?>">
            <?= mb_substr($m['display_name'],0,1,'UTF-8') ?>
          </div>
          <div style="flex:1;min-width:0">
            <div class="d-flex align-items-center gap-1">
              <span class="m-name"><?= h($m['display_name']) ?></span>
              <span class="role-badge <?= h($m['role']) ?>"><?= $m['role']==='parent'?'👑 おや':'👧 こども' ?></span>
            </div>
            <div class="m-sub">@<?= h($m['username']) ?></div>
          </div>
          <div class="d-flex gap-1 flex-wrap justify-content-end">
            <button class="btn-washi btn-pw"
              onclick="openPwModal(<?= h($m['user_id']) ?>,'<?= h(addslashes($m['display_name'])) ?>')">🔑</button>
            <?php if ($m['user_id'] !== $user['user_id']): ?>
            <form method="post" onsubmit="return confirm('<?= h(addslashes($m['display_name'])) ?> を削除しますか？')">
              <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="del_user_id" value="<?= h($m['user_id']) ?>">
              <button type="submit" class="btn-washi btn-del">削除</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- パスワード変更モーダル -->
<div class="modal fade" id="pwModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-2">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-family:var(--font-h)">🔑 パスワード変更</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="action" value="change_pw">
          <input type="hidden" name="pw_user_id" id="pwUserId">
          <div class="mb-3">
            <label class="form-label"><span id="pwTargetName"></span> の新しいパスワード</label>
            <input type="password" name="new_password" class="form-control" minlength="4" required>
          </div>
          <button type="submit" class="btn-submit w-100">💾 変更する</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function pickColor(el){
  document.querySelectorAll('.color-dot').forEach(d=>d.classList.remove('sel'));
  el.classList.add('sel');
  document.getElementById('avatarColorInput').value=el.dataset.color;
}
function openPwModal(uid,name){
  document.getElementById('pwUserId').value=uid;
  document.getElementById('pwTargetName').textContent=name;
  new bootstrap.Modal(document.getElementById('pwModal')).show();
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
