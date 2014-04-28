<?php
/**
 * Bach's viewer ajax routes
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Anaphore
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3)The name of the author may not be used to
 *    endorse or promote products derived from this software without
 *    specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Routes
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
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
            $start = null;
            $end = null;

            if ( $series !== null ) {
                $start = $series->getStart();
                $end = $series->getEnd();
            }

            Analog::debug('SET NEW SERIES');
            $series = new Series(
                $conf,
                $series_path,
                $app_base_url,
                $start,
                $end
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
                $series = null;
                if ( isset($session['series']) ) {
                    $series = unserialize($session['series']);
                }

                if ( !$series || $series->getPath() !== $series_path ) {
                    //check if series path are the same form params and from session
                    $series = new Series(
                        $conf,
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
            $conf,
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
