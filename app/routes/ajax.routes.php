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

/**
 * FIXME: this one is mainly the same as the "show" route".
 * The only difference is that "ajax/img" route will display a
 * default image if there is an error, while the "show" route
 * will display an error message (see 'slim.before.dispatch' in main.php)
 */
$app->get(
    '/ajax/img(/:series)/:image(/format/:format)',
    function ($series_path = null, $image, $format = 'default') use ($app, $viewer) {
        $picture = $viewer->getImage($series_path, $image);
        $display = $picture->getDisplay($format);
        $response = $app->response();
        foreach ( $display['headers'] as $key=>$header ) {
            $response[$key] = $header;
        }
        $response->body($display['content']);
    }
);

$app->get(
    '/ajax/representative/:series/format/:format',
    function ($series_path = null, $format) use ($app, $conf, $app_base_url) {
        $request = $app->request;
        $start = $request->params('s');
        $end = $request->params('e');

        $series = new Series(
            $conf,
            $series_path,
            $app_base_url,
            $start,
            $end
        );

        $picture = new Picture(
            $conf,
            $series->getRepresentative(),
            $app_base_url,
            $series->getFullPath()
        );
        $app->redirect($picture->getUrl($series, $format));
    }
);

$app->get(
    '/ajax/series/infos/:series/:image',
    function ($series_path, $img) use ($app, $conf, $app_base_url) {
        $request = $app->request;
        $start = $request->params('s');
        $end = $request->params('e');

        $series = new Series(
            $conf,
            $series_path,
            $app_base_url,
            $start,
            $end
        );

        if ( $img !== null ) {
            $series->setImage($img);
        }

        $infos = $series->getInfos();
        echo json_encode($infos);
    }
);

$app->get(
    '/ajax/image/infos/:image_params+',
    function ($img_params) use ($app, $conf) {
        $img = array_pop($img_params);
        $path = null;
        if ( count($img_params) > 0 ) {
            $path = implode('/', $img_params) .'/';
        }

        $rcontents = Picture::getRemoteInfos(
            $conf->getRemoteInfos(),
            $path,
            $img
        );

        $infos = array();

        if ( $rcontents !== null ) {
            $infos['remote'] = $rcontents;
        }

        echo json_encode($infos);
    }
);

$app->get(
    '/ajax/image/comments/:image_params+',
    function ($img_params) use ($app, $conf) {
        $img = array_pop($img_params);
        $path = null;
        if ( count($img_params) > 0 ) {
            $path = implode('/', $img_params) .'/';
        }

        $rcontents = Picture::getRemoteComments(
            $conf->getRemoteInfos(),
            $path,
            $img
        );

        echo json_encode($rcontents);
    }
);

$app->get(
    '/ajax/image/comment/bachURL',
    function () use ($app, $conf) {
        $bachURL = $conf->getRemoteInfos();
        echo $bachURL['uri'];
    }
);

$app->get(
    '/ajax/series/:series/thumbs',
    function ($series_path) use ($app, $conf, $app_base_url) {
        $request = $app->request;
        $start = $request->params('s');
        $end = $request->params('e');
        $communicability = $request->params('comm');
        $series = new Series(
            $conf,
            $series_path,
            $app_base_url,
            $start,
            $end
        );

        $formats = $conf->getFormats();
        $fmt = $formats['thumb'];

        $thumbs = $series->getThumbs($fmt, $series_path, $communicability);

        echo json_encode($thumbs);
    }
);
