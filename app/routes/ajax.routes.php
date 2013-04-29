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
    function ($series_path = null, $image) use ($app, $formats, $conf, &$session) {
        $series = null;
        if ( isset($session['series']) ) {
            $series = unserialize($session['series']);
        }

        if ( $series_path !== null ) {
            $series = new Series(
                $conf->getRoots(),
                $series_path
            );
        }
        $series->setImage($image);
        $picture = new Picture($image, $series->getFullPath());

        $session['series'] = serialize($series);
        $session['picture'] = serialize($picture);

        $app->redirect($picture->getUrl());
    }
)->conditions(
    array(
        'image' => '.+\.[a-zA-Z]{3,4}'
    )
);

$app->get(
    '/ajax/series/infos',
    function () use ($app, $session) {
        $series = unserialize($session['series']);
        $infos = $series->getInfos();
        echo json_encode($infos);
    }
);
