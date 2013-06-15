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
    '/show(/:format)/:uri',
    function ($format, $uri) use ($app, $conf, &$session, $app_base_url) {
        $picture = new Picture(
            $conf,
            base64_decode($uri),
            $app_base_url
        );

        if ( !isset($session['picture']) ) {
            $session['picture'] = serialize($picture);
        } else {
            $sess_pic = unserialize($session['picture']);
            if ( $sess_pic->getFullPath() != $picture->getFullPath() ) {
                $session['picture'] = serialize($picture);
            }
        }
        //var_dump($picture);
        if ( $format == '' ) {
            $format = 'default';
        }
        $display = $picture->getDisplay($format);
        $response = $app->response();
        foreach ( $display['headers'] as $key=>$header ) {
            echo $key;
            $response[$key] = $header;
        }
        $response->body($display['content']);
    }
);

$app->get(
    '/viewer/:image_params+',
    function ($img_params) use ($app, $conf, $app_base_url) {
        $img = array_pop($img_params);
        $path = '/' . implode('/', $img_params);
        $picture = null;
        if ( $img === DEFAULT_PICTURE ) {
            $picture = new Picture($conf, 'main.jpg', $app_base_url, WEB_DIR . '/images/');
        } else {
            $picture = new Picture($conf, $img, $app_base_url, $path);
        }

        $args = array(
            'img'       => $img,
            'picture'   => $picture,
            'iip'       => $picture->isPyramidal()
        );

        if ( $picture->isPyramidal() ) {
            $iip = $conf->getIIP();
            $args['iipserver'] = $iip['server'];
        } else {
            $args['image_format'] = 'default';
        }

        $app->render(
            'index.html.twig',
            $args
        );
    }
);


