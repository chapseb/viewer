<?php
use \Slim\Slim;
use \Slim\Extras\Views\Twig;
use \Bach\Viewer\Conf;
use \Bach\Viewer\Picture;
use \Bach\Viewer\Series;
use \Analog\Analog;

//config file read
define('CONFIG_FILE', APP_DIR . '/config/config.yml');

if ( !file_exists(CONFIG_FILE) ) {
    throw new \RuntimeException('Missing configuration file.');
}

require '../vendor/autoload.php';
$log_file = APP_DIR . '/logs/viewer.log';
Analog::handler(\Analog\Handler\File::init($log_file));

$conf = new Conf();
$formats = $conf->getFormats();

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
define('DEFAULT_PICTURE', 'main.jpg');

$app->hook(
    'slim.before.dispatch',
    function () use ($app, $conf) {
        //let's send view parameters before dispatching
        $v = $app->view();
        $v->setData(
            'enable_right_click',
            $conf->getUI()['enable_right_click']
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

$app->get(
    '/series/:path+',
    function ($path) use ($app, $conf, $formats) {
        $req = $app->request();
        $start = $req->get('s');
        if ( trim($start) === '' ) {
            $start = null;
        }
        $end = $req->get('e');
        if ( trim($end) === '' ) {
            $end = null;
        }

        if ( $start === null && $end !== null || $start !== null && $end === null ) {
            $start = null;
            $end = null;
            //FIXME: show a warning or throw an exception?
            throw new \RuntimeException(
                _('Sub series cannot be instancied; missing one of start or end param!')
            );
        }
        $series = new Series(
            $conf->getRoots(),
            implode('/', $path),
            $start,
            $end
        );

        $img = null;
        if ( $req->get('img') !== null ) {
            if ( !$series->setImage($req->get('img')) ) {
                $img = $series->getRepresentative();
            } else {
                $img = $req->get('img');
            }
        } else {
            $img = $series->getRepresentative();
        }

        $picture = new Picture(
            $img,
            $series->getPath(),
            $formats
        );

        $app->render(
            'index.html.twig',
            array(
                'img'       => $img,
                'picture'   => $picture,
                'iip'       => $picture->isPyramidal(),
                'series'    => $series
            )
        );

    }
);

$app->run();
