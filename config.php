<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', 'http://localhost/english_vocab');

define('DB_HOST', 'localhost');
define('DB_NAME', 'english_vocab');
define('DB_USER', 'root');
define('DB_PASS', '');