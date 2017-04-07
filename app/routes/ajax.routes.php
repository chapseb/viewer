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

use \Analog\Analog;
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
    '/ajax/imgAws(/:series)/:image(/format/:format)',
    function ($series_path = null, $image, $format = 'default') use ($app, $conf, $viewer) {

        $s3 = new Aws\S3\S3Client(
            [
                'version' => $conf->getAWSVersion(),
                'region'  => $conf->getAWSRegion(),
                'credentials' => array(
                    'key' => $conf->getAWSKey(),
                    'secret' =>$conf->getAWSSecret()
                )
            ]
        );

        $roots = $conf->getRoots();
        $results = array();
        foreach ($roots as $root) {
            $objects = $s3->getIterator(
                'ListObjects',
                array(
                    "Bucket" => $conf->getAWSBucket(),
                    "Prefix" => $root . $series_path .'/'. $image,
                    "Delimiter" => "/",
                )
            );
            foreach ($objects as $object) {
                array_push($results, $object['Key']);
            }
        }
        if (!empty($results)) {
            $srcUrl = $conf->getCloudfront() . 'prepared_images/'
                . $format . '/' . $results[0];
            if (!file_exists(
                's3://'. $conf->getAWSBucket().'/'.'prepared_images/'.$format.'/'.$results[0]
            )
            ) {
                $srcUrl = $conf->getCloudfront().'prepared_images/'
                    . $format . '/main.jpg';
            }
        } else {
            $srcUrl = $conf->getCloudfront().'prepared_images/'.$format.'/main.jpg';
        }
        echo $srcUrl;
    }
);

$app->get(
    '/ajax/representativeAws/:series/format/:format',
    function ($series_path = null, $format) use ($app, $conf, $app_base_url) {

        $s3 = new Aws\S3\S3Client(
            [
                'version' => $conf->getAWSVersion(),
                'region'  => $conf->getAWSRegion(),
                'credentials' => array(
                    'key' => $conf->getAWSKey(),
                    'secret' =>$conf->getAWSSecret()
                )
            ]
        );
        if (strrpos($series_path, '.') == '') {
            $series_path .= '/';
        }

        $roots = $conf->getRoots();
        $results = array();
        foreach ($roots as $root) {
            $objects = $s3->getIterator(
                'ListObjects',
                array(
                    "Bucket" => $conf->getAWSBucket(),
                    "Prefix" => $root . $series_path,
                    "Delimiter" => "/",
                )
            );
            foreach ($objects as $object) {
                array_push($results, $object['Key']);
            }
        }
        if (!empty($results)) {
            $srcUrl = $conf->getCloudfront() . 'prepared_images/'
                . $format . '/' . $results[0];
            if (!file_exists(
                's3://'. $conf->getAWSBucket().'/'.'prepared_images/'.$format.'/'.$results[0]
            )
            ) {
                $srcUrl = $conf->getCloudfront().'prepared_images/'
                    . $format . '/main.jpg';
            }
        } else {
            $srcUrl = $conf->getCloudfront().'prepared_images/'.$format.'/main.jpg';
        }
        echo $srcUrl;
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
            $series_path.'/',
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

        $series = new Series(
            $conf,
            $series_path,
            $app_base_url,
            $start,
            $end
        );

        $formats = $conf->getFormats();
        $fmt = $formats['thumb'];

        $thumbs = $series->getThumbs($fmt, $series_path);

        echo json_encode($thumbs);
    }
);

$app->post(
    '/ajax/generateimages',
    function () use ($app, $conf) {
        $s3 = new Aws\S3\S3Client(
            [
                'version' => $conf->getAWSVersion(),
                'region'  => $conf->getAWSRegion(),
                'credentials' => array(
                    'key' => $conf->getAWSKey(),
                    'secret' =>$conf->getAWSSecret()
                )
            ]
        );
        // get image need to be prepared
        $jsonPost = $app->request()->getBody();
        $datas = json_decode($jsonPost, true);
        $authorizedExtensions = array(
            'png', 'jpg', 'jpeg', 'tiff',
            'PNG', 'JPG', 'JPEG', 'TIFF'
        );

        $newData = array();
        $cpt = 0;
        foreach ($datas as $keyData => $data) {
            $imagesToTreat = stripslashes($data['href']);
            $imageEnd = stripslashes($data['end_dao']);
            $lastFile = stripslashes($data['last_file']);
            // get end image if it exists
            if (!empty($imageEnd)) {
                $imagePrefix = substr($imageEnd, 0, strrpos($imageEnd, '/')). '/';
            } else {
                $imagePrefix = $imagesToTreat;
            }
            $results = array();
            $roots = $conf->getRoots();
            if (strrpos($imagePrefix, '.') == ''
                && substr($imagePrefix, -1, 1) != '/'
            ) {
                $imagePrefix .= '/';
            }

            foreach ($roots as $root) {
                $objects = $s3->getIterator(
                    'ListObjects',
                    array(
                        "Bucket" => $conf->getAWSBucket(),
                        "Prefix" => $root . $imagePrefix,
                        "Delimiter" => "/",
                    )
                );
                foreach ($objects as $object) {

                    if (empty($imageEnd)
                        || (strcmp($object['Key'], $root.$imagesToTreat) >= 0
                        && strcmp($object['Key'], $root.$imageEnd) <= 0)
                    ) {
                        if (strcmp($object['Key'], $lastFile) >= 0) {
                            $testExtension = substr(
                                $object['Key'],
                                strrpos($object['Key'], '.') + 1
                            );
                            if (in_array($testExtension, $authorizedExtensions)) {
                                array_push($results, $object['Key']);
                            }
                        }
                    }
                }
            }

            // generate prepared images
            $fmts = $conf->getFormats();
            foreach ($results as $result) {
                $previousKey = '';
                foreach ($fmts as $key => $fmt) {
                    $ext = substr(strrchr($result, '.'), 1);

                    $h = $fmt['height'];
                    $w = $fmt['width'];
                    $time_start = microtime(true);

                    $cptBefore = $cpt;
                    if (!file_exists('s3://'.$conf->getAWSBucket().'/'.'prepared_images/'.$key.'/'.$result)) {
                        try {
                            $pathDisk = __DIR__.'/../cache/';
                            $ext = substr(strrchr($result, '.'), 1);
                            if ($key == 'default') {
                                $s3->getObject(
                                    array(
                                        'Bucket' => $conf->getAWSBucket(),
                                        'Key'    => $result,
                                        'SaveAs' =>  $pathDisk . 'tmp.'.$ext,
                                    )
                                );
                                $handle = fopen(
                                    $pathDisk. 'tmp.'.$ext,
                                    'rb'
                                );
                            } else {
                                $handle = fopen(
                                    $pathDisk . 'tmp_' . $previousKey . '.'.$ext,
                                    'rb'
                                );
                            }
                            $image = new Imagick();
                            $image->readImageFile($handle);

                            $extContentType = 'jpeg';
                            if ($ext == 'jpg' || $ext == 'jpeg'
                                || $ext == 'JPG' || $ext == 'JPEG'
                            ) {
                                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                            }
                            $image->setImageCompressionQuality(70);

                            $image->thumbnailImage($w, $h, true);
                            $image->writeImage($pathDisk . 'tmp_' . $key . '.'.$ext);
                            $image->clear();

                            if ($ext == 'png' || $ext == 'PNG') {
                                $extContentType = 'png';
                            }
                            $s3->putObject(
                                array(
                                    'Bucket'    => $conf->getAWSBucket(),
                                    'Key'       => 'prepared_images/' . $key . '/' . $result,
                                    'SourceFile'=> $pathDisk . 'tmp_' . $key . '.'.$ext,
                                    'ACL'       => 'public-read',
                                    'Metadata'  => array(
                                        "Content-Type"=>'image/'.$extContentType
                                    )
                                )
                            );
                            fclose($handle);
                        } catch ( \ImagickException $e ) {
                            $image->destroy();
                            Analog::log(
                                'Error image generation : '.
                                $key. ' ::::: '. $result
                            );
                            throw new \RuntimeException(
                                $key . ' :::: ' .
                                $result . ' ==== ' .
                                $e->getMessage()
                            );
                        }
                        $cpt += 1;
                    }
                    $previousKey = $key;
                }
                if ($cpt > $cptBefore) {
                    Analog::log(
                        ('Creation prepared image for '. $result)
                    );
                }
                if ($cpt >= ($conf->getNbImagesToPrepare() * 3)) {
                    $data['lastfile'] = $result;
                    array_push($newData, $data);
                    for ($i = $keyData+1;$i<sizeOf($datas); $i++) {
                        array_push($newData, $datas[$i]);
                    }
                    $jsonData = json_encode($newData);
                    $url = $conf->getRemoteInfos()['uri'] . 'deleteImage?'.uniqid();
                    $cmd = "curl -X POST -H 'Content-Type: application/json'";
                    $cmd.= " -d '" . $jsonData . "' " . "'" . $url . "'";
                    $out = exec($cmd, $output);
                    Analog::log(
                        ($out)
                    );
                    exit();
                }
            }
            $data['action'] = 0;
            array_push($newData, $data);
        }
        $jsonData = json_encode($newData);
        $url = $conf->getRemoteInfos()['uri'] . 'deleteImage?'.uniqid();
        $cmd = "curl -X POST -H 'Content-Type: application/json'";
        $cmd.= " -d '" . $jsonData . "' " . "'" . $url . "'";
        $out = exec($cmd, $output);
        Analog::log(
            ($out)
        );
    }
);
