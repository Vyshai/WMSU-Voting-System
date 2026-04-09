<?php
$root_dir = dirname(__FILE__);
$session_path = $root_dir . '/sessions';

if (!is_dir($session_path)) {
    @mkdir($session_path, 0700, true);
}

ini_set('session.save_path', $session_path);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_path', '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}