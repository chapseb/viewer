<?php
/**
 * Tests bootstrap
 *
 * PHP version 5
 *
 * @category  Tests
 * @package   Viewer
 *
 * @author    Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @copyright 2013 Anaphore
 * @license   Unknown http://unknonw.com
 * @link      http://anaphore.eu
 */

//Main application entry for production
define('APP_DIR', realpath(__DIR__  . '/../app'));
define('WEB_DIR', realpath(__DIR__  . '/../web'));
define('APP_DEBUG', true);
define('APP_CACHE', APP_DIR . '/cache/');
define('APP_TESTS', true);

require_once APP_DIR . '/main.php';
