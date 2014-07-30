<?php
/**
 * ImageMagick picture handler
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
 * @category Handlers
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer\Handlers;

use \Analog\Analog;

/**
 * ImageMagick picture handler
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class ImagickHandler extends AbstractHandler
{
    private $_quality = 70;

    protected $capabilities = array(
        'rotate',
        'negate',
        'crop',
        'print',
        'contrast',
        'brightness'
    );

    /**
     * Function name that should exists to check required module presence
     *
     * @return string
     */
    public function extensionName()
    {
        return 'imagick';
    }

    /**
     * Retrieve image informations
     *
     * @param string $path Image path
     *
     * @return array(
     *  'width',
     *  'height',
     *  'type',
     *  'mime'
     * )
     */
    public function getImageInfos($path)
    {
        $infos = array();

        try {
            $image = new \Imagick($path);
            $iinfos = $image->identifyImage();

            $infos[] = $iinfos['geometry']['width'];
            $infos[] = $iinfos['geometry']['height'];
            $infos[] = $iinfos['format'];
            $infos[] = null; //no way to retrieve mimetype
            $image->destroy();
        } catch ( \ImagickException $e ) {
            throw new \RuntimeException($e->getMessage());
        }

        return $infos;
    }

    /**
     * Resize the image if it exceed max allowed sizes
     *
     * @param string $source the source image
     * @param string $dest   the destination image.
     * @param string $format the format to use
     *
     * @return void
     */
    public function resize($source, $dest, $format)
    {
        $image = new \Imagick($source);

        $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality($this->_quality);

        $fmts = $this->conf->getFormats();
        $fmt = $fmts[$format];
        $h = $fmt['height'];
        $w = $fmt['width'];

        try {
            $image->thumbnailImage($w, $h, true);
            $image->writeImage($dest);
        } catch ( \ImagickException $e ) {
            $image->destroy();
            throw new \RuntimeException($e->getMessage());
        }

        $image->destroy();
    }

    /**
     * Apply transformations on image
     *
     * @param string $source Source image
     * @param array  $params Transformations and parameters
     * @param string $store  Temporary store on disk
     *
     * @return string
     */
    public function transform($source, $params, $store = null)
    {
        try {
            $image = new \Imagick($source);

            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality($this->_quality);

            if ( isset($params['negate']) ) {
                $image->negateImage(false);
            }

            if ( isset($params['rotate']) ) {
                $image->rotateImage(
                    new \ImagickPixel('#00000000'),
                    $params['rotate']['angle']
                );
            }

            if ( isset($params['crop']) ) {
                $image->cropImage(
                    $params['crop']['w'],
                    $params['crop']['h'],
                    $params['crop']['x'],
                    $params['crop']['y']
                );
            }

            if ( isset($params['contrast']) ) {
                $level = (int)$params['contrast'];
                if ($level < -10) {
                    $level = -10;
                } else if ($level > 10) {
                    $level = 10;
                }
                if ($level > 0) {
                    for ($i = 0; $i < $level; $i++) {
                        $image->contrastImage(1);
                    }
                } else if ($level < 0) {
                    for ($i = $level; $i < 0; $i++) {
                        $image->contrastImage(0);
                    }
                }
            }

            if ( isset($params['brightness']) ) {
                $value = (int)$params['brightness'];
                $brightness = null;
                if ( $value <= 0 ) {
                    $brightness = $value + 100;
                } else {
                    $brightness = $value * 3  + 100;
                }
                $image->modulateImage($brightness, 100, 100);
            }

            $ret = null;
            if ( $store !== null ) {
                $ret = $image->writeImage($store);
            } else {
                $ret = $image->getImageBlob();
            }
            $image->destroy();
            return $ret;
        } catch ( \ImagickException $e ) {
            $image->destroy();
            throw new \RuntimeException($e->getMessage());
        }
    }
}
