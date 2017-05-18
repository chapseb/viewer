<?php
/**
 * Picture handling
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
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer;

use \Analog\Analog;

/**
 * Picture
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Picture
{
    const DEFAULT_WIDTH = 1000;
    const DEFAULT_HEIGHT = 1000;

    const METHOD_GMAGICK = 'gmagick';
    const METHOD_IMAGICK = 'imagick';
    const METHOD_GD = 'gd';

    private $_path;
    private $_name;
    private $_full_path;
    private $_width;
    private $_height;
    private $_type;
    private $_mime;
    private $_pyramidal = false;
    private $_conf;
    private $_exif;
    private $_app_base_url;
    private $_handler;

    private $_pyramidal_types = array(
        IMAGETYPE_TIFF_II,
        IMAGETYPE_TIFF_MM
    );

    /**
     * Main constructor
     *
     * @param Conf   $conf         Viewer configuration
     * @param string $name         Image name
     * @param string $app_base_url Application base URL
     * @param string $path         Image path, if known
     */
    public function __construct($conf, $name, $app_base_url, $path=null)
    {
        $this->_conf = $conf;
        $this->_name = $name;
        $this->_app_base_url = $app_base_url;

        if ($name === DEFAULT_PICTURE) {
            $this->_full_path = WEB_DIR . '/images/' . $this->_name;
        } else {
            if ($path !== null) {
                $this->_path = $path;
                //normalize path
                if (substr($this->_path, - 1) !== '/'
                    && !substr($this->_name, 0, 1) !== '/'
                ) {
                    $this->_path = $this->_path . '/';
                }
            }

            $this->_full_path = $this->_path . $this->_name;
        }

        if (!file_exists($this->_full_path)) {
            if (isset($this->_conf)) {
                $roots = $this->_conf->getRoots();
                foreach ( $roots as $root ) {
                    if (substr($this->_full_path, 0, 1) == '/'
                        && substr($root, -1) == '/'
                    ) {
                        $allPathFile = substr_replace(
                            $root,
                            "",
                            -1
                        ) . $this->_full_path;
                    } else {
                        $allPathFile = $root . $this->_full_path;
                    }
                    if (file_exists($allPathFile)
                        && is_file($allPathFile)
                    ) {
                        $this->_full_path = $allPathFile;
                        Analog::log(
                            str_replace(
                                '%path',
                                $this->_full_path,
                                _('Image path set to "%path"')
                            )
                        );
                        break;
                    }
                }
            }

            //if file has not been found in roots, throw an exception
            if (!file_exists($this->_full_path)) {
                throw new \RuntimeException(
                    str_replace(
                        '%file',
                        $this->_full_path,
                        _('File %file does not exists!')
                    )
                );
            }
        }

        $this->_exif = @exif_read_data($this->_full_path);

        $this->_prepareHandler();

        if ($this->_exif === false) {
            //no exif data in picture, let's ask handler
            list(
                $this->_width,
                $this->_height,
                $this->_type,
                $this->_mime) = $this->_handler->getImageInfos($this->_full_path);
        } else {
            if (isset($this->_exif['ExifImageWidth'])) {
                $this->_width = $this->_exif['ExifImageWidth'];
            } else if (isset($this->_exif['ImageWidth'])) {
                $this->_width = $this->_exif['ImageWidth'];
            } else if (isset($this->_exif['COMPUTED']['Width'])) {
                $this->_width = $this->_exif['COMPUTED']['Width'];
            } else {
                throw new \RuntimeException(_('Unable to get image width!'));
            }

            if (isset($this->_exif['ExifImageLength'])) {
                $this->_height = $this->_exif['ExifImageLength'];
            } else if (isset($this->_exif['ImageLength'])) {
                $this->_height = $this->_exif['ImageLength'];
            } else if (isset($this->_exif['COMPUTED']['Height'])) {
                $this->_height = $this->_exif['COMPUTED']['Height'];
            } else {
                throw new \RuntimeException(_('Unable to get image height!'));
            }

            $this->_type = $this->_exif['FileType'];
            $this->_handler->setType($this->_type);
            $this->_mime =  $this->_exif['MimeType'];

            //checks pyramidal images
            if (in_array($this->_type, $this->_pyramidal_types)) {
                if (isset($this->_exif['TileWidth'])
                    || isset($this->_exif['TileLength'])
                ) {
                    $this->_pyramidal = true;
                }
            }
        }

        $this->_handler->check($this->_pyramidal);
    }

    /**
     * Prepares image handler
     *
     * @return void
     */
    private function _prepareHandler()
    {
        //chek method that will be used
        $method = $this->_conf->getPrepareMethod();

        if ($method === 'choose') {
            //automatically set method
            if (class_exists('Imagick')) {
                $method = self::METHOD_IMAGICK;
            } else if (class_exists('Gmagick')) {
                $method = self::METHOD_GMAGICK;
            } else {
                $method = self::METHOD_GD;
            }
        }

        if ($method === null) {
            Analog::info(
                _('Falling back to Gd method.')
            );
            $method = self::METHOD_GD;
        }

        $handler_class = 'GdHandler';

        switch ( $method ) {
        case self::METHOD_GMAGICK:
            $handler_class = 'GmagickHandler';
            break;
        case self::METHOD_IMAGICK:
            $handler_class = 'ImagickHandler';
            break;
        }

        $handler_class = 'Bach\Viewer\Handlers\\' . $handler_class;
        $this->_handler = new $handler_class(
            $this->_conf,
            $this->_pyramidal_types
        );
    }

    /**
     * Get display informations (header, content, etc)
     *
     * @param string $format           Format to display
     * @param array  $transform_params Transformation parameters, optionnal
     * @param string $store            Temporary store image on disk
     *
     * @return array
     */
    public function getDisplay($format = 'full', $transform_params = null,
        $store = null
    ) {
        Analog::log(
            'Displaying ' . $this->_full_path . ' (format: ' . $format . ')',
            Analog::DEBUG
        );

        $length = null;
        $file_path = null;
        //if ( $format == 'full' ) {
            $file_path = $this->_full_path;
            $length = filesize($this->_full_path);
        /*} else {
            list($file_path, $length) = $this->_checkImageFormat($format);
            }*/

        $content = null;
        if ($transform_params === null) {
            $content = file_get_contents($file_path);
        } else {
            $length = null; //FIXME: find a way to get lenght

            $params = array();
            //translate parameters for handler
            if ($transform_params['rotate'] !== null) {
                $params['rotate'] = array('angle' => $transform_params['rotate']);
            }
            if ($transform_params['negate'] !== null) {
                $params['negate'] = true;
            }
            if ($transform_params['crop'] !== false) {
                $params['crop'] = $transform_params['crop'];
            }
            if ($transform_params['contrast'] !== null) {
                $params['contrast'] = $transform_params['contrast'];
            }
            if ($transform_params['brightness'] !== null) {
                $params['brightness'] = $transform_params['brightness'];
            }

            $content = $this->_handler->transform($file_path, $params, $store);
        }

        $headers = array();

        if ($length !== null) {
            $headers['Content-Length'] = $length;
        }

        if ($this->_mime !== null) {
            $headers['Content-Type'] = $this->_mime;
        }


        $ret = array(
            'headers'   => $headers,
            'content'   => $content
        );
        return $ret;
    }

    /**
     * Get image relative path
     *
     * @param string $image_name Image name
     *
     * @return string
     */
    private function _getRelativePath($image_name)
    {

        if ($image_name === DEFAULT_PICTURE) {
            return '';
        } else {
            $relative_path = str_replace($image_name, '', $this->_full_path);

            foreach ($this->_conf->getRoots() as $root) {
                if (strpos($this->_full_path, $root) === 0) {
                    return str_replace($root, '', $relative_path);
                }
            }
        }

        throw new \RuntimeException(
            str_replace(
                '%image',
                $image_name,
                'Unable to get %image relative path!'
            )
        );
    }

    /**
     * Get image informations for a specific format
     *
     * @param string $format Required format
     *
     * @return array
     */
    private function _checkImageFormat($format)
    {
        $name_path = explode('/', $this->_name);
        $image_name = array_pop($name_path);

        $prepared_path = $this->_conf->getPreparedPath() . $format;
        $prepared_path .= '/' . $this->_getRelativePath($image_name);

        $image_path = $prepared_path . $image_name;
        $flagChangeImage = false;
        if (file_exists($image_path) ) {
            $dateImage = new \DateTime();
            $datePreparedImage = new \DateTime();
            $dateImage->setTimestamp(filectime($this->_full_path));
            $datePreparedImage->setTimestamp(filectime($image_path));
            if ($dateImage > $datePreparedImage) {
                $flagChangeImage = true;
            }
        }

        if (!file_exists($image_path)
            || $flagChangeImage
        ) {
            //prepared image does not exists yet
            if (file_exists($this->_conf->getPreparedPath())
                && is_dir($this->_conf->getPreparedPath())
                && is_writable($this->_conf->getPreparedPath())
            ) {
                if (!file_exists($prepared_path)) {
                    mkdir($prepared_path, 0755, true);
                }
                $this->_prepareImage($image_path, $format);
            } else {
                Analog::error(
                    str_replace(
                        '%path',
                        $this->_conf->getPreparedPath(),
                        _('%path does not exists or is not writable!')
                    )
                );
                //let's serve original image...
                return array(
                    $this->_full_path,
                    filesize($this->_full_path)
                );
            }
        }
        return array(
            $image_path,
            filesize($image_path)
        );

    }

    /**
     * Converts image to specified format
     *
     * @param string $dest   Destination file full path
     * @param string $format Wanted image format
     *
     * @return void
     */
    private function _prepareImage($dest, $format)
    {
        $this->_handler->resize(
            $this->_full_path,
            $dest,
            $format
        );

    }

    /**
     * Get image name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get image URL to display in web interface
     *
     * @param Series $series Current series
     * @param string $format Format to display
     *
     * @return string
     */
    public function getUrl(Series $series = null, $format = 'default')
    {
        $prefix = $this->_app_base_url . '/show/';
        $prefix .= $format . '/';
        if ($series) {
            $prefix .= $series->getPath() . '/';
        } else if ($this->_path !== null) {
            $prefix .= $this->_path;
        }
        return $prefix . $this->getName();
    }

    /**
     * Get "visible" formats (ie. no thumbs, nor medium)
     *
     * @return array
     */
    public function getVisibleFormats()
    {
        $visibles = array();

        if (count($this->_conf->getFormats()) > 0) {
            $formats = $this->_conf->getFormats();
            foreach ($formats as $k=>$fmt) {
                if ($k !== 'thumb' && $k !== 'medium') {
                    if ($fmt['width'] < $this->_width
                        || $fmt['height'] < $this->_height
                    ) {
                        $visibles[$k] = $k . ' ' . $fmt['width'] . 'x' .
                            $fmt['height'];
                    }
                }
            }
            if (!isset($formats['full'])) {
                $visibles["full"] = _('full') . ' ' . $this->_width .
                    'x' . $this->_height;
            }
        }
        return $visibles;
    }

    /**
     * Does format exists
     *
     * @param string $format Format name
     *
     * @return boolean
     */
    public function hasFormat($format)
    {
        return in_array(
            $format,
            array_keys($this->getVisibleFormats())
        );
    }

    /**
     * Is current image pyramidal?
     *
     * @return Boolean
     */
    public function isPyramidal()
    {
        return $this->_pyramidal;
    }

    /**
     * Get image full path on disk
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->_full_path;
    }

    /**
     * Get image path
     *
     * @return string
     */
    public function getPath()
    {
        return trim($this->_path, '/');
    }

    /**
     * Get image width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->_width;
    }

    /**
     * Get image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->_height;
    }

    /**
     * Is negate supported by current handler
     *
     * @return boolean
     */
    public function canNegate()
    {
        try {
            $this->_handler->canNegate();
            return true;
        } catch ( \RuntimeException $e ) {
            return false;
        }
    }

    /**
     * Is print supported by current handler
     *
     * @return boolean
     */
    public function canPrint()
    {
        try {
            $this->_handler->canPrint();
            return true;
        } catch ( \RuntimeException $e ) {
            return false;
        }
    }

    /**
     * Is contrast supported by current handler
     *
     * @return boolean
     */
    public function canContrast()
    {
        try {
            $this->_handler->canContrast();
            return true;
        } catch ( \RuntimeException $e ) {
            return false;
        }
    }

    /**
     * Is brightness supported by current handler
     *
     * @return boolean
     */
    public function canBrightness()
    {
        try {
            $this->_handler->canBrightness();
            return true;
        } catch ( \RuntimeException $e ) {
            return false;
        }
    }

    /**
     * Get remoteinfos URI
     *
     * @param array  $rinfos Remote informations configuration
     * @param string $path   Image path
     * @param string $img    Image name
     *
     * @return string
     */
    public static function getRemoteInfosURI($rinfos, $path, $img)
    {
        $uri = $rinfos['uri'];
        if ($rinfos['method'] === 'bach') {
            $uri .= 'infosimage/' . $path . $img;
        } else if ($rinfos['method'] === 'pleade') {
            $uri .= 'functions/ead/infosimage.xml?path=' .
                $path  . '&name=' . $img;
        }
        Analog::debug(
            'Loading remote infos from ' . $uri
        );
        return $uri;
    }

    /**
     * Retrieve remote informations about image
     *
     * @param array  $rinfos        Remote informations configuration
     * @param string $path          Image path
     * @param string $img           Image name
     * @param string $ruri          Remote URI (will be build with
     *                              getRemoteInfosURI if null)
     * @param string $readingRoomIp Ip test for reading room
     *
     * @return string
     */
    public static function getRemoteInfos($rinfos, $path, $img,
        $ruri = null, $readingRoomIp = null
    ) {
        $uri = null;

        if ($ruri === null) {
            $uri = self::getRemoteInfosURI($rinfos, $path, $img);
        } else {
            $uri = $ruri;
        }
        $rcontents = null;
        if ($remoteContents = json_decode(@file_get_contents($uri))) {
            $rcontents['cookie'] = $remoteContents->cookie;
            if ($rinfos['method'] === 'bach') {
                if (isset($remoteContents->mat)) {
                    $rcontents['mat']['link_mat'] = str_replace(
                        'href="',
                        'target="_blank" href="' . rtrim($rinfos['uri'], '/'),
                        $remoteContents->mat->link_mat
                    );
                    $rcontents['mat']['record'] = $remoteContents->mat->record;
                }
                if (isset($remoteContents->ead)) {
                    $rcontents['ead']['link'] = str_replace(
                        'href="',
                        'target="_blank" href="' . rtrim($rinfos['uri'], '/'),
                        $remoteContents->ead->link
                    );
                    $rcontents['ead']['unitid'] = $remoteContents->ead->unitid;
                    $rcontents['ead']['cUnittitle'] = $remoteContents->ead->cUnittitle;
                    $rcontents['ead']['doclink'] = str_replace(
                        'href="',
                        'target="_blank" href="' . rtrim($rinfos['uri'], '/'),
                        $remoteContents->ead->doclink
                    );
                    $rcontents['ead']['communicability_general']
                        = $remoteContents->ead->communicability_general;
                    $rcontents['ead']['communicability_sallelecture']
                        = $remoteContents->ead->communicability_sallelecture;
                }
            } else if ($rinfos['method'] === 'pleade') {
                $rxml = @simplexml_load_string($remoteContents->link);
                if ($rxml->a) {
                    unset($rxml->a['onclick']);
                    unset($rxml->a['id']);
                    unset($rxml->a['attr']);
                    $rxml->a['href'] = $rinfos['uri'] .
                        str_replace('../', '', $rxml->a['href']);
                    $rcontents = $rxml->a->asXML();
                } else {
                    $rcontents = null;
                }
            }
            $rcontents['communicability'] = self::isCommunicable(
                $rcontents,
                $readingRoomIp
            );

        }
        return $rcontents;
    }

    /**
     * Treat remote infos communicability
     *
     * @param array  $rcontents     Remote informations with communicability
     * @param string $readingRoomIp Ip of reading room client
     *
     * @return boolean
     */
    public static function isCommunicable($rcontents, $readingRoomIp)
    {
        // get client ip
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $rcontents['reader'] = false;
        if (isset($_COOKIE[$rcontents['cookie'].'_reader']) ) {
            $jsonCookie = json_decode(
                $_COOKIE[$rcontents['cookie'].'_reader']
            );
            if (isset($jsonCookie->reader)) {
                $rcontents['reader'] = $jsonCookie->reader;
            }
            if (isset($jsonCookie->archivist)) {
                $rcontents['archivist'] = $jsonCookie->archivist;
            }
        }
        $readerFlag = $rcontents['reader'];
        //$archivistFlag = $rcontents['archivist'];

        $communicability = $communicabilityEad = $communicabilityMat = false;
        $current_date = new \DateTime();
        $current_year = $current_date->format("Y");

        if (isset($rcontents['ead'])) {
            $remoteInfosEad = $rcontents['ead'];

            // test communicability_general
            if ($remoteInfosEad['communicability_general'] == null) {
                $communicabilityEad = true;
            }
            if (isset($remoteInfosEad['communicability_general'])
                && $remoteInfosEad['communicability_general'] <= $current_year
            ) {
                    $communicabilityEad = true;
            }

            // test communicability_sallelecture
            if (strpos($readingRoomIp, $ip) !== false
                && $readerFlag == true
                && isset($remoteInfosEad['communicability_sallelecture'])
                && $remoteInfosEad['communicability_sallelecture'] <= $current_year
            ) {
                $communicabilityEad = true;
            }
        }

        if (isset($rcontents['mat'])) {
            if (isset($rcontents['mat']['record'])) {
                $remoteInfosMat = $rcontents['mat']['record'];
                if (isset($remoteInfosMat->communicability_general)) {
                    $communicabilityGeneralMat = new DateTime($remoteInfosMat->communicability_general);
                    if ($communicabilityGeneralMat <= $current_date) {
                        $communicabilityMat = true;
                    }
                }
                if (isset($remoteInfosMat->communicability_sallelecture)) {
                    $communicabilitySallelectureMat = new DateTime($remoteInfosMat->communicability_sallelecture);
                    if (strpos($readingRoomIp, $ip) !== false
                        && $readerFlag == true
                        && $communicabilitySallelectureMat <= $current_date
                    ) {
                        $communicabilityMat = true;
                    }
                }

                if (!isset($remoteInfosMat->communicability_general)
                    && !isset($remoteInfosMat->communicability_sallelecture)
                ) {
                        $communicabilityMat = true;
                }
            }
        }

        if ($communicabilityEad  || $communicabilityMat) {
            $communicability = true;
        }

        return $communicability;
    }

    /**
     * Retrive remote comments about image
     *
     * @param array  $rinfos Remote informations configuration
     * @param string $path   Image path
     * @param string $img    Image name
     *
     * @return string
     */
    public static function getRemoteComments($rinfos, $path, $img)
    {
        $uri = $rinfos['uri'];
        if ( $rinfos['method'] === 'bach' ) {
            $uri .= 'comment/images/' . $path . $img . '/get';
        } else {
            return;
        }

        $rcontents = @file_get_contents($uri);
        return $rcontents;
    }
}
