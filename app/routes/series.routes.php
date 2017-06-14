<?php
/**
 * Bach's viewer series routes
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

use \Bach\Viewer\Series;
use \Bach\Viewer\Picture;

$app->get(
    '/series/:path+',
    function ($path) use ($app, $conf, $app_base_url) {
        $request = $app->request();
        $start = $request->params('s');
        if ( trim($start) === '' ) {
            $start = null;
        }
        $end = $request->params('e');
        if ( trim($end) === '' ) {
            $end = null;
        }

        if ( $start === null && $end !== null || $start !== null && $end === null ) {
            $start = null;
            $end = null;
            throw new \RuntimeException(
                _('Sub series cannot be instancied; missing one of start or end param!')
            );
        }

        $series = null;
        try {
            $series = new Series(
                $conf,
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
        if ( $request->params('img') !== null ) {
            //get image from its name
            if ( $series->setImage($request->params('img')) ) {
                $img = $series->getImage();
            }
        } else if ( $request->get('num') !== null ) {
            //get image from its position
            if ( $series->setNumberedImage($request->params('num')) ) {
                $img = $series->getImage();
            }
        }

        if ( $img === null ) {
            $img = $series->getRepresentative();
        }

        /*$picture = new Picture(
            $conf,
            $img,
            $app_base_url,
            $series->getFullPath()
        );*/

        $s3 = new \Aws\S3\S3Client(
            [
                'version' => $conf->getAWSVersion(),
                'region'  => $conf->getAWSRegion(),
                'credentials' => array(
                    'key' => $conf->getAWSKey(),
                    'secret' =>$conf->getAWSSecret()
                )
            ]
        );

        $results = array();
        $objects = $s3->getIterator(
            'ListObjects',
            array(
                "Bucket" => $conf->getAWSBucket(),
                "Prefix" => 'prepared_images/default/'.$series->getFullPath().$series->getRepresentative(),
                "Delimiter" => "/",
            )
        );
        foreach ($objects as $object) {
            array_push($results, $object['Key']);
        }

        if (!empty($results)) {
            $default_src = $conf->getCloudfront().'prepared_images/default/'.$series->getFullPath().$series->getRepresentative();
        } else {
            $default_src = $conf->getCloudfront().'prepared_images/default/main.jpg';
        }

        $args = array(
            'cloudfront'          => $conf->getCloudfront(),
            'pathHD'              => $conf->getCloudfront().$series->getFullPath(),
            'series'              => $series,
            'default_src'         => $default_src,
            'imageStrictName'     => substr($series->getRepresentative(), strrpos($series->getRepresentative(), '/')),
            'image_database_name' => '/'.$series->getPath() . $img
        );

        if (substr($series->getPath(), -1) != '/') {
            $args['image_database_name'] = '/'.$series->getPath().'/'.$img;
        }
        /*if ( $picture->isPyramidal() ) {
            $iip = $conf->getIIP();
            $args['iipserver'] = $iip['server'];
        } else {*/
            $args['image_format'] = 'default';
        //}

        if (file_exists('../web/themes/styles/themes.css') ) {
            $args['themes'] = 'themes';
        }

        $rcontents = Picture::getRemoteInfos(
            $conf->getRemoteInfos(),
            $path[0],
            $img,
            $conf->getRemoteInfos()['uri']."infosimage/". implode('/', $path) . $img,
            $conf->getReadingroom()
        );
        $args['communicability'] = $rcontents['communicability'];
        $resultsSD = array();

        $objects = $s3->getIterator(
            'ListObjects',
            array(
                "Bucket" => $conf->getAWSBucket(),
                "Prefix" => 'prepared_images/default/'.$series->getFullPath().$series->getRepresentative(),
                "Delimiter" => "/",
            )
        );
        foreach ($objects as $object) {
            array_push($resultsSD, $object['Key']);
        }
        $flagResult = false;
        if (!isset($resultsSD[0]) || $args['communicability'] == false) {
            $results[0] = 'main.jpg';
            $args['default_src'] = $conf->getCloudfront()
                .'prepared_images/default/'.$results[0];
            $args['series']->setFullPath('');
            if (!isset($resultsSD[0])) {
                $flagResult = true;
            }
            $contentSize = count($args['series']->getContent());
            $arrayTmp = array();
            if ($args['communicability'] == false) {
                for ($i=0; $i < $contentSize; $i++ ) {
                    array_push($arrayTmp, 'main.jpg');
                }
                $args['series']->setContent($arrayTmp);
                $args['series']->setImage('main.jpg');
                $args['pathHD'] = $conf->getCloudfront()
                    . $args['series']->getFullPath();
            }
        }

        $args['notGenerateImage'] = $flagResult;
        $args['awsFlag'] = $conf->getAWSFlag();
        $app->render(
            'index.html.twig',
            $args
        );
    }
);
