<?php
/**
 * Application index
 *
 * PHP version 5
 *
 * @category Public
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
//Main application entry for production
define('APP_DIR', realpath(__DIR__  . '/../app'));
define('WEB_DIR', realpath(__DIR__  . '/../web'));

//include local config path
if ( file_exists(APP_DIR . '/config/config.inc.php') ) {
    include_once APP_DIR . '/config/config.inc.php';
}

if ( !defined('APP_DEBUG') ) {
    define('APP_DEBUG', false);
}
if ( !defined('APP_CACHE') ) {
    define('APP_CACHE', APP_DIR . '/cache/');
}

require_once APP_DIR . '/main.php';
