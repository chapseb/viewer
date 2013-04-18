<?php
//Main application entry for debuging purposes
define('APP_DIR', realpath(__DIR__  . '/../app'));
define('WEB_DIR', realpath(__DIR__  . '/../web'));
define('APP_DEBUG', true);
define('APP_CACHE', false);

require_once APP_DIR . '/main.php';
