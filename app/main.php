<?php
use \Slim\Slim;
use \Slim\Extras\Views\Twig;
use \Bach\Viewer\Picture;
use \Symfony\Component\Yaml\Parser;

//config file read
$config_file = APP_DIR . '/config/config.yml';

if ( !file_exists($config_file) ) {
    throw new \RuntimeException('Missing configuration file.');
}

require '../vendor/autoload.php';
$yaml = new Parser();
$conf = $yaml->parse(file_get_contents(APP_DIR . '/config/config.yml'));
$formats = $conf['formats'];

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

$app->hook(
    'slim.before.dispatch',
    function () use ($app, $conf) {
        //let's send view parameters before dispatching
        $v = $app->view();
        $v->setData(
            'enable_right_click',
            $conf['ui']['enable_right_click']
        );
    }
);

//main route
$app->get(
    '/',
    function () use ($app) {
        $app->redirect('/viewer/' . DEFAULT_PICTURE);
    }
);

$app->get(
    '/show/:uri',
    function ($uri) use ($app, $formats) {
        $picture = new Picture(base64_decode($uri), null, $formats);
        //var_dump($picture);
        $picture->display();
    }
);

$app->get(
    '/viewer/:image',
    function ($img) use ($app, $formats) {
        $picture = null;
        //$img_path = APP_ROOTS . $img;
        if ( $img === DEFAULT_PICTURE ) {
            //$img_path = APP_DIR . '/web/images/main.jpg';
            $picture = new Picture('main.jpg', WEB_DIR . '/images/', $formats);
        } else {
            $picture = new Picture($img, null, $formats);
        }
        //$path = '/images/main.jpg';
        $app->render(
            'index.html.twig',
            array(
                'img'   => $img,
                //'path'  => $path
                'picture'   => $picture
            )
        );
    }
);

$app->run();
