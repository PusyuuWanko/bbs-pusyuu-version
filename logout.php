<?php
session_start();
session_unset();
session_destroy();
echo "ログアウト完了";
header("Location: index.php");
exit;
?>