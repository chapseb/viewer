<?php
/**
 * Bach's viewer images routes
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
use \Bach\Viewer\Pdf;

$app->get(
    '/show/:format(/:series)/:image',
    function ($format = null, $series_path = null, $image) use ($app, $viewer) {
        $picture = $viewer->getImage($series_path, $image);

        if ( $format == '' ) {
            $format = 'default';
        }
        $display = $picture->getDisplay($format);
        $response = $app->response();
        foreach ( $display['headers'] as $key=>$header ) {
            $response[$key] = $header;
        }
        $response->body($display['content']);
    }
);

$app->get(
    '/transform/:method(/:angle)/:format(/:series)/:image',
    function ($method, $angle, $format, $series_path, $image) use ($app, $viewer) {
        $picture = $viewer->getImage($series_path, $image);

        if ( $angle === '' ) {
            $angle = null;
        }

        $negate = false;
        if ( $method === 'negate' ) {
            $negate = true;
        }

        $display = $picture->getDisplay($format, $angle, $negate);
        $response = $app->response();
        foreach ( $display['headers'] as $key=>$header ) {
            $response[$key] = $header;
        }
        $response->body($display['content']);
    }
)->conditions(
    array(
        'method'    => '(rotate|negate)',
        'angle'     => '(([0-2]?[0-9]?[0-9])|([3][0-5][0-9])|(360))'
    )
);

$app->get(
    '/viewer/:image_params+',
    function ($img_params) use ($app, $conf, $app_base_url) {
        $img = array_pop($img_params);
        $path = null;
        if ( count($img_params) > 0 ) {
            $path = '/' . implode('/', $img_params);
        }
        $picture = null;
        if ( $img === DEFAULT_PICTURE ) {
            $picture = new Picture(
                $conf,
                DEFAULT_PICTURE,
                $app_base_url,
                WEB_DIR . '/images/'
            );
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

$app->get(
    '/print/:x/:y/:width/:height/:format/:series/:image(/:show)',
    function ($x, $y, $width, $height, $format, $series_path, $image, $show = false) use ($app, $conf, $viewer) {
        $picture = $viewer->getImage($series_path, $image);

        $params = array(
            'x'         => $x,
            'y'         => $y,
            'width'     => $width,
            'height'    => $height
        );
        if ( $format == '' ) {
            $format = 'default';
        }

        $pdf = new Pdf($conf, $picture, $params, $format);

        $app->response->headers->set('Content-Type', 'application/pdf');

        if ( $show === 'true' ) {
            $content = $pdf->getContent();
            $app->response->body($content);
        } else {
            $pdf->download();
        }
    }
)->conditions(
    array(
        'x'         => '\d+',
        'y'         => '\d+',
        'width'     => '\d+',
        'height'    => '\d+',
        'show'      => 'true|false'
    )
);
