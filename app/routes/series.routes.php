<?php
/**
 * Bach's viewer series routes
 *
 * PHP version 5
 *
 * @category Routes
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

use \Bach\Viewer\Series;
use \Bach\Viewer\Picture;

$app->get(
    '/series/:path+',
    function ($path) use ($app, $conf, $formats, &$session) {
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
            $series->getFullPath(),
            $formats
        );


        $session['series'] = serialize($series);
        $session['picture'] = serialize($picture);

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
