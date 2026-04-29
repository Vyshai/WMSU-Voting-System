<?php
require_once "session_config.php";
require_once "Vote.php";
if (isset($_SESSION['user_id'])) {
    $voteObj = new Vote();
    $voteObj->logAction($_SESSION['user_id'], 'LOGOUT', 'User logged out', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
}
session_destroy();
header("Location: login.php");
exit();
