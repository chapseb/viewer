<?php
/**
 * Bach's viewer ajax routes
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
use \Bach\Viewer\Series;

$app->get(
    '/ajax/img(/:series)/:image',
    function ($series_path = null, $image) use ($app, $conf, &$session, $app_base_url) {
        $series = null;
        if ( isset($session['series']) ) {
            $series = unserialize($session['series']);
        }

        if ( $series_path !== null ) {
            $series = new Series(
                $conf->getRoots(),
                $series_path,
                $app_base_url
            );
        }
        $series->setImage($image);
        $picture = new Picture($conf, $image, $app_base_url, $series->getFullPath());

        $session['series'] = serialize($series);
        $session['picture'] = serialize($picture);

        $app->redirect($picture->getUrl());
    }
);

$app->get(
    '/ajax/img(/:series)/:image/format/:format',
    function ($series_path = null, $image, $format) use ($app, $conf, $session, $app_base_url) {
        $picture = unserialize($session['picture']);

        if ( $image !== 'undefined'
            && $picture
            && substr($picture->getName(), strlen($image)) !== $image
            || !$picture
        ) {
            if ( $series_path !== null && $series_path !== '' ) {
                //names differs, load image
                $series = unserialize($session['series']);

                if ( !$series || $series->getPath() !== $series_path ) {
                    //check if series path are the same form params and from session
                    $series = new Series(
                        $conf->getRoots(),
                        $series_path,
                        $app_base_url
                    );
                }

                $picture = new Picture(
                    $conf,
                    $image,
                    $app_base_url,
                    $series->getFullPath()
                );
            } else {
                if ( $image === DEFAULT_PICTURE ) {
                    $picture = new Picture(
                        $conf,
                        $image,
                        $app_base_url,
                        WEB_DIR . '/images/'
                    );
                } else {
                    $picture = new Picture(
                        $conf,
                        $image,
                        $app_base_url
                    );
                }
            }
        }
        $app->redirect($picture->getUrl($format));
    }
);

$app->get(
    '/ajax/representative/:series/format/:format',
    function ($series_path = null, $format) use ($app, $conf, $session, $app_base_url) {

        $series = new Series(
            $conf->getRoots(),
            $series_path,
            $app_base_url
        );

        $picture = new Picture(
            $conf,
            $series->getRepresentative(),
            $app_base_url,
            $series->getFullPath()
        );
        $app->redirect($picture->getUrl($format));
    }
);

$app->get(
    '/ajax/series/infos(/:image)',
    function ($img = null) use ($app, $session) {
        $series = unserialize($session['series']);
        if ( $img !== null ) {
            $series->setImage($img);
            $session['series'] = serialize($series);
        }
        $infos = $series->getInfos();
        echo json_encode($infos);
    }
);

$app->get(
    '/ajax/series/thumbs',
    function () use ($app, $conf, $session) {
        $series = unserialize($session['series']);
        $formats = $conf->getFormats();
        $fmt = $formats['thumb'];
        $thumbs = $series->getThumbs($fmt);
        echo json_encode($thumbs);
    }
);
