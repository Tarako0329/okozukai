<?php
// my_history.php - 子供が自分の履歴を見る（child_detail.phpに統合済み）
require_once 'config.php';
$user = require_login();
if ($user['role'] === 'parent') { header('Location: dashboard.php'); exit; }
header('Location: child_detail.php');
exit;
