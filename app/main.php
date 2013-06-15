<?php
/**
 * Bach's viewer app
 *
 * PHP version 5
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

use \Slim\Slim;
use \Slim\Route;
use \Slim\Extras\Views\Twig;
use \Bach\Viewer\Conf;
use \Analog\Analog;

session_start();
$session = &$_SESSION['bachviewer'];

require APP_DIR . '/../vendor/autoload.php';
$logger = null;
if ( defined('APP_TESTS') ) {
    $viewer_log = '';
    $logger = \Analog\Handler\Variable::init($viewer_log);
} else {
    $log_dir = APP_DIR . '/logs/';
    if ( !file_exists($log_dir) ) {
        throw new \RuntimeException(
            'Log directory (' . $log_dir  . ') does not exists!'
        );
    } else if ( !is_writeable($log_dir)  ) {
        throw new \RuntimeException(
            'Log directory (' . $log_dir . ') is not writeable!'
        );
    }
    $log_file = $log_dir . 'viewer.log';
    $logger = \Analog\Handler\File::init($log_file);
}
Analog::handler($logger);

$conf = new Conf();

/** I18n stuff */
$lang = null;
$langs = array(
    'en-US',
    'fr-FR'
);

/** Try to detect user language */
if ( function_exists('http_negotiate_language') ) {
    $nego = http_negotiate_language($langs);
    switch ( strtolower($nego) ) {
    case 'en-us':
        $lang = 'en_US';
        break;
    case 'fr-fr':
        $lang = 'fr_FR.utf8';
        break;
    }
} else {
    $langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    if ( substr($langs, 0, 2) == 'fr' ) {
        $lang = 'fr_FR.utf8';
    } else {
        $lang = 'en_US';
    }
}

putenv('LC_ALL=' . $lang);
setlocale(LC_ALL, $lang);

//Specify the location of the translation tables
bindtextdomain('BachViewer', APP_DIR . '/locale');
bind_textdomain_codeset('BachViewer', 'UTF-8');

//Choose domain
textdomain('BachViewer');
/** /I18n stuff */

$app = new Slim(
    array(
        'debug'             => APP_DEBUG,
        'view'              => new Twig(),
        'templates.path'    => APP_DIR . '/views'
    )
);

$app_base_url = '';
if ( $_SERVER['PHP_SELF'] != '/index.php' && $_SERVER['PHP_SELF'] != '/debug.php' ) {
    $app_base_url = '/private/anaphore/visionneuse-local/web/index.php';
}

Twig::$twigExtensions = array(
    new Twig_Extensions_Extension_I18n()
);

if ( defined('APP_CACHE') && APP_CACHE !== false ) {
    Twig::$twigOptions = array(
        'cache'         => APP_CACHE,
        'auto_reload'   => true
    );
}

define('DEFAULT_PICTURE', 'main.jpg');

$app->hook(
    'slim.before.dispatch',
    function () use ($app, $conf, $lang, $app_base_url) {
        //let's send view parameters before dispatching
        $v = $app->view();
        $ui = $conf->getUI();
        $v->setData('app_base_url', $app_base_url);
        $v->setData(
            'app_web_url',
            str_replace(
                array('/index.php', '/debug.php'),
                array('', ''),
                $app_base_url
            )
        );
        $v->setData(
            'enable_right_click',
            $ui['enable_right_click']
        );
        $v->setData('lang', $lang);
    }
);

//set default conditions
Route::setDefaultConditions(
    array(
        'image' => '.+\.[a-zA-Z]{3,4}'
    )
);

$app->notFound(
    function () use ($app) {
        $app->render('404.html.twig');
    }
);

//main route
$app->get(
    '/',
    function () use ($app, $app_base_url) {
        $app->redirect($app_base_url . '/viewer/' . DEFAULT_PICTURE);
    }
);

//include routes files
require_once 'routes/images.routes.php';
require_once 'routes/series.routes.php';
require_once 'routes/ajax.routes.php';
if ( APP_DEBUG === true ) {
    include_once 'routes/debug.routes.php';
}

if ( !defined('APP_TESTS') ) {
    $app->run();
}
