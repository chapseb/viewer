<?php
use \Slim\Slim;
use \Slim\Extras\Views\Twig;
use \Bach\Viewer\Picture;
use \Symfony\Component\Yaml\Parser;
use \Analog\Analog;

//config file read
define('CONFIG_FILE', APP_DIR . '/config/config.yml');

if ( !file_exists(CONFIG_FILE) ) {
    throw new \RuntimeException('Missing configuration file.');
}

require '../vendor/autoload.php';
$yaml = new Parser();
$conf = $yaml->parse(file_get_contents(APP_DIR . '/config/config.yml'));
$formats = $conf['formats'];
$log_file = APP_DIR . '/logs/viewer.log';
Analog::handler(\Analog\Handler\File::init($log_file));

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
    '/viewer/:image+',
    function ($img_params) use ($app, $formats) {
        $img = array_pop($img_params);
        $path = '/' . implode('/', $img_params);
        $picture = null;
        if ( $img === DEFAULT_PICTURE ) {
            $picture = new Picture('main.jpg', WEB_DIR . '/images/', $formats);
        } else {
            $picture = new Picture($img, $path, $formats);
        }
        $app->render(
            'index.html.twig',
            array(
                'img'       => $img,
                'picture'   => $picture,
                'iip'       => $picture->isPyramidal()
            )
        );
    }
);

$app->run();
