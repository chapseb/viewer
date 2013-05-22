<?php
/**
 * Application index for debugging purposes
 *
 * PHP version 5
 *
 * @category Public
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

if ( version_compare(PHP_VERSION, '5.3', '<') ) {
    echo 'This application is NO compliant with your current PHP version.<br/>' .
        'Application requires PHP 5.3 minimum, and you current version is ' .
        PHP_VERSION;
    die();
}

$dbg_ips = array(
    '127.0.0.1'
);

if ( !in_array($_SERVER["REMOTE_ADDR"], $dbg_ips) ) {
    die('Your are not allowed to debug this application.');
}

//Main application entry for debuging purposes
define('APP_DIR', realpath(__DIR__  . '/../app'));
define('WEB_DIR', realpath(__DIR__  . '/../web'));
define('APP_DEBUG', true);
define('APP_CACHE', false);

require_once APP_DIR . '/main.php';
