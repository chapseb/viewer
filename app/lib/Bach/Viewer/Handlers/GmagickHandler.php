<?php
/**
 * GraphicsMagick picture handler
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
 * GraphicsMagick picture handler
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class GmagickHandler extends AbstractHandler
{
    protected $capabilities = array(
        'rotate',
        'crop',
        'print'
    );

    /**
     * Function name that should exists to check required module presence
     *
     * @return string
     */
    public function extensionName()
    {
        return 'gmagick';
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
        $image = new \Gmagick();
        $infos = array();

        try {
            $image->readImage($path);

            $infos[] = $image->getImageWidth();
            $infos[] = $image->getImageHeight();
            $infos[] = $image->getImageFormat();
            $infos[] = null; //no way to retrieve mimetype
        } catch ( \GmagickException $e ) {
            $image->destroy();
            throw new \RuntimeException($e->getMessage());
        }

        $image->destroy();
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
        $image = new \Gmagick();

        $fmts = $this->conf->getFormats();
        $fmt = $fmts[$format];
        $h = $fmt['height'];
        $w = $fmt['width'];

        try {
            $image->readImage($source);
            $image->thumbnailImage($w, $h, true);
            $image->writeImage($dest);
        } catch ( \GmagickException $e ) {
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
     *
     * @return string
     */
    public function transform($source, $params)
    {
        try {
            $image = new \Gmagick();
            $image->readImage($source);

            if ( isset($params['crop']) ) {
                $image->cropImage(
                    $params['crop']['width'],
                    $params['crop']['height'],
                    $params['crop']['x'],
                    $params['crop']['y']
                );
            }

            if ( isset($params['negate']) ) {
                $this->canNegate();
            }

            if ( isset($params['rotate']) ) {
                $image->rotateImage(
                    '#000',
                    $params['rotate']['angle']
                );
            }

            $ret = $image->getImageBlob();
            $image->destroy();
            return $ret;
        } catch ( \GmagickException $e ) {
            $image->destroy();
            throw new \RuntimeException($e->getMessage());
        }
    }
}
