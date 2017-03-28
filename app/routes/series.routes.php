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
        $args = array(
            'cloudfront'          => $conf->getCloudfront(),
            'pathHD'              => $conf->getCloudfront().$series->getFullPath(),
            'series'              => $series,
            'default_src'         => $conf->getCloudfront().'prepared_images/default/'.$series->getFullPath().$series->getRepresentative(),
            'imageStrictName'     => substr($series->getRepresentative(), strrpos($series->getRepresentative(), '/')),
            'image_database_name' => '/'.$series->getPath() . $img
        );
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
            $conf->getRemoteInfos()['uri']."infosimage/". $path[0] . '/' . $img
        );

        $args['communicability'] = false;
        $current_date = new DateTime();
        $current_year = $current_date->format("Y");

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
                $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($rcontents['ead'])) {
            if ($rcontents['ead']['communicability_general'] == null
                || (isset($rcontents['ead']['communicability_general'])
                && $rcontents['ead']['communicability_general'] <= $current_year)
                || ($ip == $conf->getReadingroom()
                && isset($rcontents['ead']['communicability_sallelecture'])
                && $rcontents['ead']['communicability_sallelecture'] <= $current_year)
            ) {
                $args['communicability'] = true;
            }
        }

        if (!isset($rcontents['ead']) && !isset($rcontents['mat'])) {
            $args['communicability'] = true;
        } else {
            if (isset($rcontents['mat']['record'])) {
                $remoteInfosMat = $rcontents['mat']['record'];
                if (isset($remoteInfosMat->communicability_general)) {
                    $communicabilityGeneralMat = new DateTime($remoteInfosMat->communicability_general);
                    $communicabilitySallelectureMat = new DateTime($remoteInfosMat->communicability_sallelecture);
                    if ($communicabilityGeneralMat <= $current_date
                        || ($ip == $conf->getReadingroom()
                        && $communicabilitySallelectureMat <= $current_date)
                    ) {
                        $args['communicability'] = true;
                    }
                }

                if (!isset($remoteInfosMat->communicability_general)
                    && !isset($remoteInfosMat->communicability_sallelecture)
                ) {
                        $args['communicability'] = true;
                }
            }
        }

        $args['awsFlag'] = $conf->getAWSFlag();
        $app->render(
            'index.html.twig',
            $args
        );
    }
);
