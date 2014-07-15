<?php
/**
 * Abstract picture handler
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
 * Abstract picture handler
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
abstract class AbstractHandler
{
    protected $conf;
    protected $img_type;
    protected $capabilities = array();

    /**
     * Main constructor
     *
     * @param Conf  $conf   Viewer configuration
     * @param array $ptypes Pyramidal types
     */
    public function __construct($conf, $ptypes)
    {
        if ( !extension_loaded($this->extensionName()) ) {
            Analog::error(
                $this->missingLib()
            );

            throw new \RuntimeException(
                $this->missingLib()
            );
        }

        $this->conf = $conf;
        $this->_pyramidal_types = $ptypes;
    }

    /**
     * Function name that should exists to check required module presence
     *
     * @return string
     */
    public abstract function extensionName();

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
    public abstract function getImageInfos($path);

    /**
     * Set image type
     *
     * @param string $type Image type
     *
     * @return void
     */
    public function setType($type)
    {
        $this->img_type = $type;
    }

    /**
     * Message to display when library is missing
     *
     * @return string
     */
    protected function missingLib()
    {
        return str_replace(
            array('%handler', '%ext'),
            array(get_class($this), $this->extensionName()),
            _('%handler has been selected, but %ext is not present!')
        );
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
    public abstract function resize($source, $dest, $format);

    /**
     * Apply transformations on image
     *
     * @param string $source Source image
     * @param array  $params Transformations and parameters
     * @param string $store  Temporary store on disk
     *
     * @return string
     */
    public abstract function transform($source, $params, $store = null);

    /**
     * Rotate image
     *
     * @param string $source Source image
     * @param int    $angle  Rotation angle
     *
     * @return void
     */
    public function rotate($source, $angle)
    {
        if ( $this->canRotate() ) {
            return $this->transform(
                $source,
                array(
                    'rotate' => array(
                        'angle' => $angle
                    )
                )
            );
        }
    }

    /**
     * Negate image
     *
     * @param string $source the source image
     *
     * @return void
     */
    public function negate($source)
    {
        if ( $this->canNegate() ) {
            return $this->transform(
                $source,
                array(
                    'negate' => true
                )
            );
        }
    }

    /**
     * Crop image
     *
     * @param string $source the source image
     *
     * @return void
     */
    public function crop($source)
    {
        if ( $this->canCrop() ) {
            return $this->transform(
                $source,
                array(
                    'crop' => true
                )
            );
        }
    }

    /**
     * Check for negate capabiblity
     *
     * @return boolean
     */
    public function canNegate()
    {
        if ( in_array('negate', $this->capabilities) ) {
            return true;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%ext',
                    $this->extensionName(),
                    _('Negate is not supported with %ext!')
                )
            );
        }
    }

    /**
     * Check for rotate capabiblity
     *
     * @return boolean
     */
    public function canRotate()
    {
        if ( in_array('rotate', $this->capabilities) ) {
            return true;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%ext',
                    $this->extensionName(),
                    _('Rotate is not supported with %ext!')
                )
            );
        }
    }

    /**
     * Check for crop capabiblity
     *
     * @return boolean
     */
    public function canCrop()
    {
        if ( in_array('crop', $this->capabilities) ) {
            return true;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%ext',
                    $this->extensionName(),
                    _('Crop is not supported with %ext!')
                )
            );
        }
    }

    /**
     * Check for print capabiblity
     *
     * @return boolean
     */
    public function canPrint()
    {
        if ( in_array('print', $this->capabilities) ) {
            return true;
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%ext',
                    $this->extensionName(),
                    _('Print is not supported with %ext!')
                )
            );
        }
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
        if ( in_array($this->img_type, $this->_pyramidal_types)
            && $pyramidal === false
        ) {
            //TODO: maybe we can convert simple TIFF files to JPEG
            //instead of throwing an error
            throw new \RuntimeException(
                _('Image format not supported if not pyramidal')
            );
        }
    }

}

