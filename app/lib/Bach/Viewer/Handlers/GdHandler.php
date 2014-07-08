<?php
/**
 * GD picture handler
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
 * GD picture handler
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class GdHandler extends AbstractHandler
{
    protected $capabilities = array(
        'rotate',
        'negate',
        'crop'
    );

    private $_supported_types = array(
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_TIFF_II,
        IMAGETYPE_TIFF_MM
    );

    /**
     * Function name that should exists to check required module presence
     *
     * @return string
     */
    public function extensionName()
    {
        return 'gd';
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
        $infos = getimagesize($path);
        if ( $infos !== false ) {
            $this->img_type = $infos[2];
            $infos[] = image_type_to_mime_type($this->img_type);
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
        $gdinfo = gd_info();
        $fmts = $this->conf->getFormats();
        $fmt = $fmts[$format];
        $h = $fmt['height'];
        $w = $fmt['width'];

        switch( $this->img_type ) {
        case IMAGETYPE_JPEG:
            if (!$gdinfo['JPEG Support']) {
                Analog::log(
                    '[' . $this->get_class($this) . '] GD has no JPEG Support - ' .
                    'pictures could not be resized!',
                    Analog::ERROR
                );
                return false;
            }
            break;
        case IMAGETYPE_PNG:
            if (!$gdinfo['PNG Support']) {
                Analog::log(
                    '[' . $this->get_class($this) . '] GD has no PNG Support - ' .
                    'pictures could not be resized!',
                    Analog::ERROR
                );
                return false;
            }
            break;
        case IMAGETYPE_GIF:
            if (!$gdinfo['GIF Create Support']) {
                Analog::log(
                    '[' . $this->get_class($this) . '] GD has no GIF Support - ' .
                    'pictures could not be resized!',
                    Analog::ERROR
                );
                return false;
            }
            break;
        default:
            Analog::error(
                'Current image type cannot be resized'
            );
            return false;
        }

        list($cur_width, $cur_height, $cur_type, $curattr)
            = getimagesize($source);

        $ratio = $cur_width / $cur_height;

        // calculate image size according to ratio
        if ($cur_width>$cur_height) {
            $h = $w/$ratio;
        } else {
            $w = $h*$ratio;
        }

        $thumb = imagecreatetruecolor($w, $h);
        $image = $this->_getImageAsResource($source);

        switch( $this->img_type ) {
        case IMAGETYPE_JPEG:
            imagecopyresampled(
                $thumb, $image, 0, 0, 0, 0, $w, $h, $cur_width, $cur_height
            );
            imagejpeg($thumb, $dest);
            break;
        case IMAGETYPE_PNG:
            // Turn off alpha blending and set alpha flag. That prevent alpha
            // transparency to be saved as an arbitrary color (black in my tests)
            imagealphablending($thumb, false);
            imagealphablending($image, false);
            imagesavealpha($thumb, true);
            imagesavealpha($image, true);
            imagecopyresampled(
                $thumb, $image, 0, 0, 0, 0, $w, $h, $cur_width, $cur_height
            );
            imagepng($thumb, $dest, 9);
            break;
        case IMAGETYPE_GIF:
            imagecopyresampled(
                $thumb, $image, 0, 0, 0, 0, $w, $h, $cur_width, $cur_height
            );
            imagegif($thumb, $dest);
            break;
        case IMAGETYPE_TIFF_II:
        case IMAGETYPE_TIFF_MM:
            /** Gd cannot resize TIFF images. */
            throw new \RuntimeException(
                _('TIFF images cannot be resized using Gd library!')
            );
            break;
        }
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
        $image = $this->_getImageAsResource($source);
        $result = null;

        if ( isset($params['negate']) ) {
            $res = imagefilter($image, IMG_FILTER_NEGATE);

            if ( $res === false ) {
                throw new \RuntimeException(
                    _('Something went wrong in GD negate!')
                );
            }
        }

        if ( isset($params['rotate']) ) {
            $result = imagerotate(
                (($result !== null) ? $result : $image),
                360 - $params['rotate']['angle'],
                0
            );

            if ( $result === null ) {
                throw new \RuntimeException(
                    _('Something went wrong in GD rotation!')
                );
            }
        }

        if ( isset($params['crop']) ) {
            $cparams = $params['crop'];
            $infos = $this->getImageInfos($source);

            $result = imagecrop(
                (($result !== null) ? $result : $image),
                $cparams
            );

            if ( $result === null ) {
                throw new \RuntimeException(
                    _('Something went wrong in GD crop!')
                );
            }
        }

        if ( $result === null ) {
            $result = $image;
        }

        imagejpeg($result);
        imagedestroy($image);
        imagedestroy($result);

    }

    /**
     * Check if image suits handler capabilities
     *
     * @param boolean $pyramidal Image pyramidal or not
     *
     * @return void
     */
    public function check($pyramidal)
    {
        parent::check($pyramidal);
        if ( !in_array($this->img_type, $this->_supported_types) ) {
            throw new \RuntimeException(_('Unsupported image format!'));
        }
    }

    /**
     * Return a resource corresponding to image type
     *
     * @param string $path Image path
     *
     * @return resource
     */
    private function _getImageAsResource($path)
    {
        $image = null;
        switch( $this->img_type ) {
        case IMAGETYPE_JPEG:
            $image = ImageCreateFromJpeg($path);
            break;
        case IMAGETYPE_PNG:
            $image = ImageCreateFromPng($path);
            break;
        case IMAGETYPE_GIF:
            $image = ImageCreateFromGif($path);
            break;
        case IMAGETYPE_TIFF_II:
        case IMAGETYPE_TIFF_MM:
            /** Gd cannot handle TIFF images. */
            throw new \RuntimeException(
                _('TIFF images cannot be handled using Gd library!')
            );
            break;
        }

        return $image;
    }
}
