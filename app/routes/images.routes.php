<?php
/**
 * Bach's viewer images routes
 *
 * PHP version 5
 *
 * @category Routes
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

use \Bach\Viewer\Picture;

$app->get(
    '/show/:uri',
    function ($uri) use ($app, $conf) {
        $picture = new Picture(
            base64_decode($uri),
            null,
            $conf->getFormats(),
            $conf->getRoots()
        );
        //var_dump($picture);
        $picture->display();
    }
);

$app->get(
    '/viewer/:image_params+',
    function ($img_params) use ($app, $conf) {
        $img = array_pop($img_params);
        $path = '/' . implode('/', $img_params);
        $picture = null;
        if ( $img === DEFAULT_PICTURE ) {
            $picture = new Picture('main.jpg', WEB_DIR . '/images/', $conf->getFormats());
        } else {
            $picture = new Picture($img, $path, $conf->getFormats(), $conf->getRoots());
        }

        $args = array(
            'img'       => $img,
            'picture'   => $picture,
            'iip'       => $picture->isPyramidal()
        );

        if ( $picture->isPyramidal() ) {
            $args['iipserver'] = $conf->getIIP()['server'];
        }

        $app->render(
            'index.html.twig',
            $args
        );
    }
);


