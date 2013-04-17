<?php
use \Slim\Slim;
use \Slim\Extras\Views\Twig;

/** I18n stuff */
// Set language to French
putenv('LC_ALL=fr_FR.utf8');
setlocale(LC_ALL, 'fr_FR.utf8');

//Specify the location of the translation tables
bindtextdomain('BachViewer', APP_DIR . '/locale');
bind_textdomain_codeset('BachViewer', 'UTF-8');

//Choose domain
textdomain('BachViewer');
/** /I18n stuff */

require '../vendor/autoload.php';

$app = new Slim(
    array(
        'debug'             => APP_DEBUG,
        'view'              => new Twig(),
        'templates.path'    => APP_DIR . '/views'
    )
);

Twig::$twigExtensions = array(
    new Twig_Extensions_Extension_I18n()
);

if ( defined('APP_CACHE') && APP_CACHE !== false ) {
    Twig::$twigOptions = array(
        'cache'         => APP_CACHE,
        'auto_reload'   => true
    );
}

//TODO: parametize
define('APP_ROOTS', '/var/www/photos/');
define('DEFAULT_PICTURE', 'main.jpg');

//main route
$app->get('/', function () use ($app) {
    $app->redirect('/viewer/' . DEFAULT_PICTURE);
});

$app->get('/viewer/:image', function($img) use($app) {
    $img_path = APP_ROOTS . $img;
    if ( $img === DEFAULT_PICTURE ) {
        $img_path = APP_DIR . '/web/images/main.jpg';
    }
    $path = '/images/main.jpg';
    $app->render(
        'index.html.twig',
        array(
            'img'   => $img,
            'path'  => $path
        )
    );
});

$app->run();
