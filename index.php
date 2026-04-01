<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>おてつだいポイント</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --soft-blue: #e0f2f1; /* 水彩風の水色 */
            --soft-yellow: #fff9c4; /* 水彩風の黄色 */
            --soft-green: #e8f5e9; /* 水彩風の緑 */
            --text-color: #5d4037; /* 優しい茶色の文字 */
        }
        body {
            background-color: var(--soft-blue);
            color: var(--text-color);
            font-family: 'Kiwi Maru', sans-serif; /* 子供向けに丸いフォントを推奨 */
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.05);
            background: rgba(255, 255, 255, 0.8); /* 少し透けさせて水彩感を出す */
        }
        .btn-custom {
            background-color: var(--soft-green);
            border: 2px solid #a5d6a7;
            border-radius: 30px;
            color: var(--text-color);
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background-color: #c8e6c9;
            transform: scale(1.05);
        }
        .title-box {
            text-align: center;
            padding: 20px;
            background: var(--soft-yellow);
            border-radius: 0 0 50px 50px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<div class="title-box">
    <h1>✨ おてつだい ポイント ✨</h1>
</div>

<div class="container">
    <div class="card p-4">
        <form action="save_point.php" method="POST">
            <div class="mb-3">
                <label class="form-label">だれがおてつだいした？</label>
                <select name="user_id" class="form-select">
                    <option value="1">はなこ</option>
                    <option value="2">たろう</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">なにをした？</label>
                <select name="master_id" id="taskSelect" class="form-select">
                    <option value="1" data-point="10">おさらあらい (10pt)</option>
                    <option value="2" data-point="20">おふろそうじ (20pt)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">ポイント（かえることもできるよ）</label>
                <input type="number" name="points" id="pointInput" class="form-control" value="10">
            </div>

            <div class="mb-3">
                <label class="form-label">メモ（ひとこと）</label>
                <input type="text" name="memo" class="form-control" placeholder="がんばったことをかこう！">
            </div>

            <button type="submit" class="btn btn-custom w-100 py-3">ポイントを登録する！</button>
        </form>
    </div>
</div>

<script>
    // 選択したお手伝いに合わせて、ポイントの初期値を変えるJavaScript
    const taskSelect = document.getElementById('taskSelect');
    const pointInput = document.getElementById('pointInput');

    taskSelect.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        pointInput.value = selectedOption.getAttribute('data-point');
    });
</script>

</body>
</html>