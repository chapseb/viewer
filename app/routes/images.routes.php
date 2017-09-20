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
    function ($format = null, $series_path = null, $image) use ($app, $viewer, $conf) {
        if (substr($series_path, -1) == '/') {
            $series_path = substr($series_path, 0, -1);
        }

        $ruri = "infosimage/". $series_path . "/" . $image;
        $ruri = preg_replace('/(\/+)/', '/', $ruri);
        $rcontents = Picture::getRemoteInfos(
            $conf->getRemoteInfos(),
            $series_path,
            '',
            $conf->getRemoteInfos()['uri'].$ruri,
            $conf->getReadingroom(),
            $conf->getIpInternal()
        );

        $communicability = $rcontents['communicability'];

        if ($communicability == false && $rcontents['archivist']) {
            $communicability = true;
        }

        if ($communicability == true) {
            $picture = $viewer->getImage($series_path, $image);
        } else {
            $picture = $viewer->getImage(null, DEFAULT_PICTURE);
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
    '/transform/:format(/:series)/:image',
    function ($format, $series_path, $image) use ($app, $viewer) {
        $picture = $viewer->getImage($series_path, $image);
        $params = $viewer->bind($app->request);

        $display = $picture->getDisplay($format, $params);

        $response = $app->response();
        foreach ( $display['headers'] as $key=>$header ) {
            $response[$key] = $header;
        }
        $response->body($display['content']);
    }
);

$app->get(
    '/viewer/:image_params+',
    function ($img_params) use ($app, $conf, $app_base_url, $s3) {
        $img = array_pop($img_params);
        $path = null;
        if ( count($img_params) > 0 ) {
            $path = '/' . implode('/', $img_params);
        }

        ///////////////////////////////////////////////////////////////////////////////////
        if ($conf->getAWSFlag() == true) {
            $results = array();

            $roots = $conf->getRoots();

            foreach ($roots as $root) {
                if (substr($root, - 1) == '/') {
                    $root = substr($root, 0, -1);
                }
                $objects = $s3->getIterator(
                    'ListObjects',
                    array(
                        "Bucket" => $conf->getAWSBucket(),
                        "Prefix" => $root.$path.'/'.$img,
                        "Delimiter" => "/",
                    )
                );
                foreach ($objects as $object) {
                    array_push($results, $object['Key']);
                }

            }

        } else {
            $picture = null;

            $zoomify      = false;
            $zoomify_path = null;
            foreach ($conf->getPatternZoomify() as $pattern) {
                if (strstr($path, $pattern) == true) {
                    $zoomify      = true;
                    $zoomify_path = $path.'/'.$img;
                    $img = DEFAULT_PICTURE;
                    break;
                }
            }

            if ($img === DEFAULT_PICTURE) {
                $picture = new Picture(
                    $conf,
                    DEFAULT_PICTURE,
                    $app_base_url
                );
            } else {
                $picture = new Picture($conf, $img, $app_base_url, $path);
            }

            $args = array(
                'img'          => $img,
                'picture'      => $picture,
                'iip'          => $picture->isPyramidal(),
                'zoomify'      => $zoomify,
                'zoomify_path' => $zoomify_path
            );
            if ($args['zoomify']) {
                $args['iip'] = true;
                $args['img'] = $zoomify_path;
            }

            if ($picture->isPyramidal()) {
                $iip = $conf->getIIP();
                $args['iipserver'] = $iip['server'];
            } else {
                $args['image_format'] = 'default';
                if ($conf->getDisplayHD()) {
                    $args['image_format'] = 'full';
                }
            }
        }

        /////////////////////////////////////////////////////////////////////////////////////
        if (file_exists('../web/themes/styles/themes.css') ) {
            $args['themes'] = 'themes';
        }

        $rcontents = Picture::getRemoteInfos(
            $conf->getRemoteInfos(),
            $path,
            $img,
            $conf->getRemoteInfos()['uri']."infosimage". $path . '/' . $img,
            $conf->getReadingroom(),
            $conf->getIpInternal()
        );

        $args['communicability'] = $rcontents['communicability'];

        if ($args['communicability'] == false && !$args['zoomify']) {
            if ($conf->getAWSFlag()) {
                $results[0] = DEFAULT_PICTURE;
            } else {
                $args['remote_infos_url'] = $picture->getPath()
                    . '/' . $picture->getName();
                $args['picture'] = new Picture(
                    $conf,
                    DEFAULT_PICTURE,
                    $app_base_url
                );
            }
        }

        $flagResult = false;
        if (!isset($results[0])) {
            $results[0] = 'main.jpg';
            $flagResult = true;
        }
        if ($conf->getAWSFlag()) {
            $args = array(
                'cloudfront'          => $conf->getCloudfront(),
                'path'                => $results[0],
                'img'                 => $conf->getCloudfront().$results[0],
                'default_src'         => $conf->getCloudfront()
                                         .'prepared_images/default/'.$results[0],
                'thumb_src'           => $conf->getCloudfront()
                                         .'prepared_images/thumb/'.$results[0],
                'image_database_name' => $path .'/'. $img
            );

            $resultsSD = array();
            $objects = $s3->getIterator(
                'ListObjects',
                array(
                    "Bucket" => $conf->getAWSBucket(),
                    "Prefix" => 'prepared_images/default/'.$results[0],
                    "Delimiter" => "/",
                )
            );
            foreach ($objects as $object) {
                array_push($resultsSD, $object['Key']);
            }
            if (!isset($resultsSD[0])) {
                $results[0] = 'main.jpg';
                $args['default_src'] = $conf->getCloudfront()
                    .'prepared_images/default/'.$results[0];
                $flagResult = true;
            }
            $args['notGenerateImage'] = $flagResult;
            $args['awsFlag'] = $conf->getAWSFlag();
        }
        $args['notdownloadprint'] = $conf->getNotDownloadPrint();
        $args['displayHD'] = $conf->getDisplayHD();

        $app->render(
            'index.html.twig',
            $args
        );
    }
);

$app->get(
    '/print/:format(/:series)/:image(/:display)',
    function ($format, $series_path, $image, $display = false) use ($app,
        $conf, $viewer
    ) {
        $picture = $viewer->getImage($series_path, $image);
        if (substr($series_path, -1) == '/') {
            $series_path = substr($series_path, 0, -1);
        }
        $rcontents = Picture::getRemoteInfos(
            $conf->getRemoteInfos(),
            $series_path,
            '',
            $conf->getRemoteInfos()['uri']."infosimage/". $series_path . "/" . $image,
            $conf->getReadingroom(),
            $conf->getIpInternal()
        );
        $unitid = null;
        if (isset($rcontents['ead']['unitid'])) {
            $unitid = $rcontents['ead']['unitid'];
        }

        $params = $viewer->bind($app->request);

        $pdf = new Pdf($conf, $picture, $params, $format, $unitid);

        $app->response->headers->set('Content-Type', 'application/pdf');

        if ( $display === 'true'  || $conf->getNotDownloadPrint()) {
            $content = $pdf->getContent();
            $app->response->body($content);
        } else {
            $pdf->download();
        }
    }
)->conditions(
    array(
        'display' => 'true|false'
    )
);

$app->get(
    '/printAws/:format(/:series)/:image(/:display)',
    function ($format, $series_path, $image, $display = false) use ($app,
        $conf, $viewer, $app_base_url, $s3
    ) {
        $path_picture = $series_path . '/'. $image;
        $results = array();
        $roots = $conf->getRoots();

        if ($format == 'default') {
            foreach ($roots as $root) {
                if (substr($root, - 1) == '/') {
                    $root = substr($root, 0, -1);
                }
                $objects = $s3->getIterator(
                    'ListObjects',
                    array(
                        "Bucket" => $conf->getAWSBucket(),
                        "Prefix" => 'prepared_images/default/'. $root.'/'.$path_picture,
                        "Delimiter" => "/",
                    )
                );
                foreach ($objects as $object) {
                    array_push($results, $object['Key']);
                }
            }
        } else {
            foreach ($roots as $root) {
                if (substr($root, - 1) == '/') {
                    $root = substr($root, 0, -1);
                }
                $objects = $s3->getIterator(
                    'ListObjects',
                    array(
                        "Bucket" => $conf->getAWSBucket(),
                        "Prefix" => $root . '/' . $path_picture,
                        "Delimiter" => "/",
                    )
                );
                foreach ($objects as $object) {
                    array_push($results, $object['Key']);
                }
            }
        }

        if (file_exists('s3://'.$conf->getAWSBucket().'/'.$results[0])) {
            $uniqueFileName = uniqid();
            $pathDisk = __DIR__.'/../cache/';
            $ext = substr(strrchr($results[0], '.'), 1);
            $imageFilePath = $pathDisk.$uniqueFileName.'.'.$ext;
            $s3->getObject(
                array(
                    'Bucket' => $conf->getAWSBucket(),
                    'Key'    => $results[0],
                    'SaveAs' => $imageFilePath,
                )
            );
            $handle = fopen(
                $imageFilePath,
                'rb'
            );

            $picture = new Picture(
                $conf,
                $uniqueFileName.'.'.$ext,
                $app_base_url,
                $pathDisk
            );

            $params = $viewer->bind($app->request);
            $pdf = new Pdf($conf, $picture, $params, $format);

            $app->response->headers->set('Content-Type', 'application/pdf');

            if ($display === 'true') {
                $content = $pdf->getContent();
                $app->response->body($content);
            } else {
                $pdf->download();
            }
            unlink($imageFilePath);
        }
    }
)->conditions(
    array(
        'display' => 'true|false'
    )
);
