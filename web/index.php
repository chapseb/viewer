<?php
//Main application entry for production
define('APP_DIR', realpath(__DIR__  . '/../app'));
define('APP_DEBUG', false);
define('APP_CACHE', APP_DIR . '/cache/');

require_once APP_DIR . '/main.php';
