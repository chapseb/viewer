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
    function ($path) use ($app, $conf, &$session, $app_base_url) {
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

        $series = null;
        try {
            $series = new Series(
                $conf->getRoots(),
                implode('/', $path),
                $app_base_url,
                $start,
                $end
            );
        } catch ( \RuntimeException $e ) {
            Analog::log(
                _('Cannot load series: ') . $e->getMessage(),
                Analog::ERROR
            );
            $app->pass();
        }

        //check if series has content, throw an error if not
        if ( $series->getCount() === 0 ) {
            throw new \RuntimeException(
                str_replace(
                    '%s',
                    $series->getPath(),
                    _('Series "%s" is empty!')
                )
            );
        }

        $img = null;
        if ( $req->get('img') !== null ) {
            //get image from its name
            if ( $series->setImage($req->get('img')) ) {
                $img = $series->getImage();
            }
        } else if ( $req->get('num') !== null ) {
            //get image from its position
            if ( $series->setNumberedImage($req->get('num')) ) {
                $img = $series->getImage();
            }
        }

        if ( $img === null ) {
            $img = $series->getRepresentative();
        }

        $picture = new Picture(
            $conf,
            $img,
            $app_base_url,
            $series->getFullPath()
        );

        $session['series'] = serialize($series);
        $session['picture'] = serialize($picture);

        $args = array(
            'img'       => $img,
            'picture'   => $picture,
            'iip'       => $picture->isPyramidal(),
            'series'    => $series
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
