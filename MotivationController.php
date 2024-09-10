<?php
session_start();

// OpenAI APIキーの設定
$apiKey = 'YOUR_OPENAI_API_KEY';

// OpenAI APIを呼び出す関数
function callOpenAI($prompt) {
    global $apiKey;

    $data = [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'あなたは話すGPTです。'],
            ['role' => 'user', 'content' => $prompt]
        ]
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// セッションステートの初期化
if (!isset($_SESSION['step'])) {
    $_SESSION['step'] = 0;
    $_SESSION['job_info'] = '';
    $_SESSION['interests'] = [];
    $_SESSION['additional_interests'] = [];
    $_SESSION['club_activities'] = '';
    $_SESSION['other_achievements'] = '';
    $_SESSION['motivation'] = '';
}

// 求人情報の入力処理
if ($_SESSION['step'] === 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['job_info'] = $_POST['job_info'];
        $_SESSION['step'] = 1;
    }
?>
    <h2>求人情報を入力してください</h2>
    <form method="POST">
        <textarea name="job_info" placeholder="求人情報をここに入力してください" required></textarea><br>
        <button type="submit">次へ</button>
    </form>
<?php
    exit();
}

// 興味を持った点の選択
if ($_SESSION['step'] === 1) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['interests'] = isset($_POST['interests']) ? $_POST['interests'] : [];
        $_SESSION['step'] = 2;
    }
    $interests_options = ['給料が良い', '会社の場所が良い', '自分がしたい仕事', 'すぐ働けそう', '得意なことが活かせそう', '生活スタイルに合ってる'];
?>
    <h2>どんなところに興味を持ちましたか？</h2>
    <form method="POST">
        <?php foreach ($interests_options as $option): ?>
            <label>
                <input type="checkbox" name="interests[]" value="<?= htmlspecialchars($option) ?>">
                <?= htmlspecialchars($option) ?>
            </label><br>
        <?php endforeach; ?>
        <button type="submit">次へ</button>
    </form>
<?php
    exit();
}

// 部活や習い事の入力
if ($_SESSION['step'] === 2) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['club_activities'] = $_POST['club_activities'];
        $_SESSION['step'] = 3;
    }
?>
    <h2>部活や習い事はしていますか？</h2>
    <form method="POST">
        <input type="text" name="club_activities" placeholder="部活や習い事を入力してください" required><br>
        <button type="submit">次へ</button>
    </form>
<?php
    exit();
}

// その他の頑張ったことの入力
if ($_SESSION['step'] === 3) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['other_achievements'] = $_POST['other_achievements'];
        $_SESSION['step'] = 4;
    }
?>
    <h2>勉強やアルバイト、資格など頑張ったことがありますか？</h2>
    <form method="POST">
        <input type="text" name="other_achievements" placeholder="頑張ったことを入力してください" required><br>
        <button type="submit">志望動機を生成する</button>
    </form>
<?php
    exit();
}

// 志望動機の生成
if ($_SESSION['step'] === 4) {
    if (empty($_SESSION['motivation'])) {
        $prompt = "
        あなたは高校生の志望動機作成をサポートするGPTです。
        次の情報を使って志望動機を作成してください。
        - 求人情報: {$_SESSION['job_info']}
        - 興味を持った点: " . implode(', ', $_SESSION['interests']) . "
        - 部活や習い事: {$_SESSION['club_activities']}
        - その他の頑張ったこと: {$_SESSION['other_achievements']}
        これは履歴書に記載する文章であるため、書き言葉で丁寧な文章で出力します。
        300〜400文字の間でお願いします。
        ";
        
        $response = callOpenAI($prompt);
        $_SESSION['motivation'] = $response['choices'][0]['message']['content'];
    }

    echo "<h2>生成された志望動機</h2>";
    echo "<p>" . nl2br(htmlspecialchars($_SESSION['motivation'])) . "</p>";

    // セッションをクリアして最初からやり直す
    session_destroy();
    echo "<a href=''>最初からやり直す</a>";
}
?>
