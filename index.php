<?php
session_start();
require_once 'dbconnect.php';
ini_set("display_errors", 1);
error_reporting(E_ALL);

// セッションにログイン情報がない場合はログインページにリダイレクト
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['email'];
$username = $_SESSION['username']; // ユーザー名を取得

//====================================//
//==============bbs関係===============//
//====================================//

// スレッドを作成する
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['thread_name']) && !empty($_POST['thread_name']) && isset($_POST['comment'])) {
    $threadName = $_POST['thread_name'];
    $comment = $_POST['comment'];

    // 新しいテーブルを作成
    $sql = "CREATE TABLE IF NOT EXISTS thread_{$threadName} (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), comment TEXT, created_at DATETIME)";
    $stmt = $dbh->query($sql);
    if ($stmt) {
      // スレッド作成成功したら1番目の書き込みを追加
      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO thread_{$threadName} (username, comment, created_at) VALUES (?, ?, ?)";
      $stmt = $dbh->prepare($sql);
      $stmt->execute([$username, $comment, $now]);

      echo 'スレッドが作成されました。';
      // リダイレクトによりGETリクエストを行う
      header('Location: thread.php?name=' . urlencode($threadName));
      exit;
    } else {
      echo 'スレッドの作成に失敗しました。';
    }/*else {
      echo 'スレッド名とコメントを入力してください。';
    }*/
  }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BBS｜home</title>
  <link rel="stylesheet" href="style.css">
  <!--<link rel="stylesheet" href="https://21emon.wjg.jp/SystemFolder/CssData/Style-1.css">-->
</head>
<body>
  <header class="header">
    <h2>Mrs <?php echo $username; ?> WELCOME</h2>
    <nav>
      <ul>
        <li><a href="./bbs-rule.html">BBS OF RULE</a></li>
        <li><a href="./logout.php">LOGOUT</a></li>
      </ul>
    </nav>
  </header>
  <main class="main">
  <div class="flex-box">
    <div class="form_design-1">
      <h2>NEW CREATE THREAD</h2>
      <form action="index.php" method="POST">
        <input type="text" id="thread_name" name="thread_name" required placeholder="THREAD NAME">
        <textarea id="comment" name="comment" required placeholder="COMMENT"></textarea>
        <button type="submit">CREATE</button>
      </form>
      <h2>IMAGE UPLOADER</h2>
      <form action="img-upload.php" method="post" enctype="multipart/form-data">
        <input type="file" id="image" name="image" accept="image/*" required>
        <button type="submit">IMAGE UPLOAD</button>
      </form>
    </div>
  </div>
  <h2>THREAD LIST</h2>
  <!--ここはすべてChatGPTが書きました　動かないからって私にモンク言わないで-->
  <form action="" method="get">
    <p>表示順：
    <select name="sort" onchange="this.form.submit()">
      <option value="new" <?php if (isset($_GET['sort']) && $_GET['sort'] === 'new') echo 'selected'; ?>>新しい順</option>
      <option value="old" <?php if (isset($_GET['sort']) && $_GET['sort'] === 'old') echo 'selected'; ?>>古い順</option>
      <option value="popular" <?php if (isset($_GET['sort']) && $_GET['sort'] === 'popular') echo 'selected'; ?>>人気順</option>
    </select>
    </p>
  </form>
  <ul id="threadList">
    <?php
    // スレッド一覧の取得
    $sql = "SHOW TABLES LIKE 'thread_%'";
    $stmt = $dbh->query($sql);
    $threads = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // ソート方法に応じてスレッド一覧を並び替える
    if (isset($_GET['sort'])) {
      $sort = $_GET['sort'];
      if ($sort === 'old') {
        // 古い順はスレッドの最初の書き込み時間でソート
        usort($threads, function ($a, $b) use ($dbh) {
          $sql = "SELECT MIN(created_at) AS first_created_at FROM {$a}";
          $stmt = $dbh->query($sql);
          $firstCreatedAtA = $stmt->fetchColumn();

          $sql = "SELECT MIN(created_at) AS first_created_at FROM {$b}";
          $stmt = $dbh->query($sql);
          $firstCreatedAtB = $stmt->fetchColumn();

          return strtotime($firstCreatedAtA) - strtotime($firstCreatedAtB);
        });
      } elseif ($sort === 'new') {
        // 新しい順はスレッドの最初の書き込み時間でソート
        usort($threads, function ($a, $b) use ($dbh) {
          $sql = "SELECT MIN(created_at) AS first_created_at FROM {$a}";
          $stmt = $dbh->query($sql);
          $firstCreatedAtA = $stmt->fetchColumn();

          $sql = "SELECT MIN(created_at) AS first_created_at FROM {$b}";
          $stmt = $dbh->query($sql);
          $firstCreatedAtB = $stmt->fetchColumn();

          return strtotime($firstCreatedAtB) - strtotime($firstCreatedAtA);
        });
      } elseif ($sort === 'popular') {
        // 人気順の場合はスレッドの最後のidの値でソート
        usort($threads, function ($a, $b) use ($dbh) {
          $sql = "SELECT MAX(id) AS last_id FROM {$a}";
          $stmt = $dbh->query($sql);
          $lastIdA = $stmt->fetchColumn();

          $sql = "SELECT MAX(id) AS last_id FROM {$b}";
          $stmt = $dbh->query($sql);
          $lastIdB = $stmt->fetchColumn();

          return $lastIdB - $lastIdA;
        });
      }
    }

    // スレッド一覧を表示
    foreach ($threads as $thread) {
      $sql = "SELECT MAX(id) AS last_id, MIN(created_at) AS first_created_at FROM {$thread}";
      $stmt = $dbh->query($sql);
      $threadInfo = $stmt->fetch(PDO::FETCH_ASSOC);
      $threadName = htmlspecialchars(substr($thread, 7));
      $lastId = $threadInfo['last_id'];
      $firstCreatedAt = $threadInfo['first_created_at'];

      echo '<li><a href="javascript:void(0);" onclick="loadThread(\'' . $threadName . '\')">' . $threadName . '（' . $lastId . '件の書き込み' . '）</a></li>';
    }

    ?>
  </ul>
  <script>
  // 表示順を切り替えるJavaScript関数
  function changeSort() {
    const sort = document.querySelector('select[name="sort"]').value;
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', sort);
    window.location.href = currentUrl.href;
  }
  // スレッドをiframeに読み込むJavaScript関数
  function loadThread(threadName) {
    const iframe = document.getElementById('threadIframe');
    const url = 'thread.php?name=' + encodeURIComponent(threadName);
    iframe.src = url;
  }
  </script>
  </main>
  <footer class="footer">
    <h2>THREAD VIEW</h2>
    <iframe width="100%" height="500px" id="threadIframe" src="" frameborder="1px"></iframe>
  </footer>
</body>
</html>