<?php
/**
 * Series handling
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
 * @author   Johan Cwiklinski  <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer;

use \Analog\Analog;

/**
 * Series
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski  <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Series
{
    private $_conf;
    private $_path;
    private $_full_path;
    private $_start;
    private $_end;
    private $_content;
    private $_current;

    /**
     * Main constructor
     *
     * @param array  $conf         Viewer configuration
     * @param string $path         Series path
     * @param string $app_base_url Application base URL
     * @param string $start        Sub series start point (optional)
     * @param string $end          Sub series end point (optional)
     */
    public function __construct(
        $conf, $path, $app_base_url, $start = null, $end = null
    ) {
        $this->_path = $path;
        $this->_conf = $conf;
        $this->_start = $start;
        $this->_end = $end;
        $roots = $conf->getRoots();

        if ($conf->getAWSFlag() == true) {
            /* FIXME find a way to get s3 from main.php in class object without reinstanciate */
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

            // test root with path and get matched link
            foreach ($roots as $root) {
                if (substr($root, - 1) == '/') {
                    $root = substr($root, 0, -1);
                }
                if ($this->_start == null) {
                    $objects = $s3->getIterator(
                        'ListObjects',
                        array(
                            "Bucket" => $conf->getAWSBucket(),
                            "Prefix" => $root . '/' . $path,
                            "Delimiter" => "/",
                        )
                    );
                } else {
                    $objects = $s3->getIterator(
                        'ListObjects',
                        array(
                            "Bucket" => $conf->getAWSBucket(),
                            "Prefix" => $root . '/' . $path,
                            "Delimiter" => "/",
                        )
                    );

                }

                foreach ($objects as $object) {
                    array_push($results, $object['Key']);
                }

                if (!empty($results)) {
                    $this->_full_path = $root . '/' . $path;
                    Analog::log(
                        str_replace(
                            '%path',
                            $this->_full_path,
                            _('Series path set to "%path"')
                        )
                    );
                    break;
                }
            }

        } else {
            // test root with path and get existing file
            foreach ( $roots as $root ) {
                if (file_exists($root . $path) && is_dir($root . $path)) {
                    $this->_full_path = $root . $path;
                    Analog::log(
                        str_replace(
                            '%path',
                            $this->_full_path,
                            _('Series path set to "%path"')
                        )
                    );
                    break;
                }
            }
        }

        if ($this->_full_path === null) {
            throw new \RuntimeException(
                _('No matching root found!')
            );
        } else {
            $this->_content = array();
            if (!$conf->getAWSFlag()) {
                $handle = opendir($this->_full_path);
            } else {
                $handle = opendir(
                    's3://'.$conf->getAWSBucket().'/'.$this->_full_path
                );
            }

            $all_entries = array();
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $go = ($this->_start === null ) ? true : false;
            if ($conf->getAWSFlag()) {
                $go = true;
            }
            if ($go === true) {
                while ( false !== ($entry = readdir($handle)) ) {
                    if ($entry != "."
                        && $entry != ".."
                        && !is_dir($this->_full_path . '/' . $entry)
                    ) {
                        if (!$conf->getAWSFlag()) {
                            $mimetype = $finfo->file(
                                $this->_full_path . '/' . $entry
                            );
                        }
                        if ($conf->getAWSFlag() || $mimetype != ''
                            && strpos($mimetype, 'image') === 0
                        ) {
                            $all_entries[] = $entry;
                        }
                    }
                }

                closedir($handle);
                sort($all_entries, SORT_STRING);

                // build begin/end serie
                if ($this->_start != null && $conf->getAWSFlag()) {
                    $tmp_entry = array();
                    foreach ($all_entries as $entry) {
                        if (strcmp($this->_start, $entry) <= 0
                            && strcmp($this->_end, $entry) >= 0
                        ) {
                            array_push($tmp_entry, $entry);

                        }
                    }
                    $all_entries = $tmp_entry;
                }
            } else {
                $listFiles = scandir($this->_full_path);
                sort($listFiles, SORT_STRING);
                $begin = array_search($this->_start, $listFiles);
                $end   = array_search($this->_end, $listFiles);
                $diff  = $end + 1 - $begin;
                $arrayDif = array_slice($listFiles, $begin, $diff);

                foreach ($arrayDif as $entry) {
                    $mimetype = $finfo->file($this->_full_path . '/' . $entry);
                    if ($mimetype != ''
                        && strpos($mimetype, 'image') === 0 || $conf->getAWSFlag()
                    ) {
                        $all_entries[] = $entry;
                    }
                }
            }

            foreach ($all_entries as $entry) {
                //check for subseries start
                if (!$go
                    && substr($entry, -strlen($this->_start)) === $this->_start
                ) {
                    $go = true;
                }

                if ($go) {
                    try {
                        if (!$conf->getAWSFlag()) {
                            $picture = new Picture(
                                $this->_conf,
                                $entry,
                                $app_base_url,
                                $this->_full_path
                            );
                        }
                        $this->_content[] = $entry;
                    } catch (\RuntimeException $re) {
                        Analog::warning(
                            'Image type for ' . $entry . ' is not supported!'
                        );
                    }

                    if ($this->_end !== false
                        && substr($entry, -strlen($this->_end)) === $this->_end
                    ) {
                        $go = false;
                    }
                } else {
                    Analog::info(
                        str_replace(
                            '%img',
                            $entry,
                            'Image %img is out of subseries.'
                        )
                    );
                }
            }
        }
    }

    /**
     * Retrieve series path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Retrieve series full path
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->_full_path;
    }

    /**
     * Change series full path
     *
     * @param string $fullpath new serie's full path
     *
     * @return void
     */
    public function setFullPath($fullpath)
    {
        $this->_full_path = $fullpath;
    }

    /**
     * Retrieve representative image
     *
     * @return string
     */
    public function getRepresentative()
    {
        //TODO: get a representative image that is not simply the first
        //of current series
        $img = $this->_content[0];
        $this->setImage($img);
        return $img;
    }

    /**
     * Set current image
     *
     * @param string $img Image
     *
     * @return boolean
     */
    public function setImage($img)
    {
        if (in_array($img, $this->_content)) {
            $this->_current = $img;
            return true;
        } else {
            if ($this->_conf->getDebugMode()) {
                Analog::log(
                    str_replace(
                        '%image',
                        $img,
                        _('Image %image is not part of current series!')
                    )
                );
            }
            return false;
        }
    }

    /**
     * Set image from its position in series
     *
     * @param int $pos Image position
     *
     * @return boolean
     */
    public function setNumberedImage($pos)
    {
        $pos = $pos - 1;
        if (isset($this->_content[$pos])) {
            return $this->setImage($this->_content[$pos]);
        } else {
            Analog::error(
                str_replace(
                    '%pos',
                    $pos,
                    _('No image at position %pos!')
                )
            );
            return false;
        }
    }

    /**
     * Get current image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->_current;
    }

    /**
     * Get previous image
     *
     * @return string
     */
    public function getPreviousImage()
    {
        $_index = array_search($this->_current, $this->_content);
        if ($_index === 0 ) {
            $_index = count($this->_content) - 1;
        } else {
            $_index -= 1;
        }
        return $this->_content[$_index];
    }

    /**
     * Get 10 previous image
     *
     * @return string
     */
    public function getTenPreviousImage()
    {
        $_index = array_search($this->_current, $this->_content);
        if ($_index - 10 >= 0) {
            $_index = ($_index - 10);
        } else {
            $_index = (count($this->_content) + ($_index - 10));
        }

        if ($_index < 0) {
            $_index = 0;
            return null;
        }
        return $this->_content[$_index];
    }

    /**
     * Get next image
     *
     * @return string
     */
    public function getNextImage()
    {
        $_index = array_search($this->_current, $this->_content);
        if ($_index === count($this->_content) - 1) {
            $_index = 0;
        } else {
            $_index += 1;
        }
        return $this->_content[$_index];
    }

    /**
     * Get 10 next image
     *
     * @return string
     */
    public function getTenNextImage()
    {
        $_index = array_search($this->_current, $this->_content);
        if ($_index === count($this->_content) - 1) {
            $_index = 0;
        } else {
            $_index = ($_index + 10)%count($this->_content);
        }
        return $this->_content[$_index];
    }

    /**
     * Retrieve number of images in current series
     *
     * @return integer
     */
    public function getCount()
    {
        return count($this->_content);
    }

    /**
     * Retrieve current image position in serie
     *
     * @return integer
     */
    public function getCurrentPosition()
    {
        return array_search($this->_current, $this->_content) + 1;
    }

    /**
     * Get array image
     *
     * @return array
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Retrieve informations about current series
     *
     * @return array
     */
    public function getInfos()
    {
        if (!isset($this->_current)) {
            throw new \RuntimeException(
                _('Series has not been initialized yet.')
            );
        } else {
            $infos = array(
                'path'      => $this->_path,
                'current'   => $this->_current,
                'next'      => $this->getNextImage(),
                'tennext'   => $this->getTenNextImage(),
                'prev'      => $this->getPreviousImage(),
                'tenprev'   => $this->getTenPreviousImage(),
                'count'     => $this->getCount(),
                'position'  => $this->getCurrentPosition()
            );

            $rinfos = $this->_conf->getRemoteInfos();
            if ($rinfos !== false) {
                $rcontents = Picture::getRemoteInfos(
                    $rinfos,
                    $this->_path,
                    $this->_current
                );

                if ($rcontents !== null) {
                    $infos['remote'] = $rcontents;
                }

            }

            return $infos;
        }
    }

    /**
     * Get list of cseries thumbnails
     *
     * @param array   $fmt             Thumbnail format form configuration
     * @param string  $series_path     Path Serie image path
     * @param boolean $communicability Flag to know if serie communicable
     *
     * @return array
     */
    public function getThumbs($fmt, $series_path, $communicability = false)
    {
        $ret = array();
        $thumbs = array();

        $ret['meta'] = $fmt;

        foreach ( $this->_content as $c ) {
            if ($communicability == true) {
                $p = new Picture($this->_conf, $c, null, $this->_full_path);
                $path = null;
                if ($p->isPyramidal()) {
                    $path = $p->getFullPath();
                } else {
                    $path = $c;
                }

                $thumbs[] = array(
                    'name'       => $c,
                    'path'       => $path
                );
            } else {
                $p = new Picture(
                    $this->_conf,
                    DEFAULT_PICTURE,
                    $this->_conf->getRemoteInfos()['uri']
                );
                $path = null;
                if ($p->isPyramidal()) {
                    $path = $p->getFullPath();
                } else {
                    $path = $c;
                }

                $thumbs[] = array(
                    'name'       => $c,
                    'path'       => $path,
                    'path_image' => DEFAULT_PICTURE
                );
            }

        }
        $ret['thumbs'] = $thumbs;

        return $ret;
    }

    /**
     * Get subseries start image
     *
     * @return string or null
     */
    public function getStart()
    {
        return $this->_start;
    }

    /**
     * Get subseries end image
     *
     * @return string or null
     */
    public function getEnd()
    {
        return $this->_end;
    }

    /**
     * Set content Series
     *
     * @param array $content new serie's content
     *
     * @return void
     */
    public function setContent($content)
    {
        $this->_content = $content;
    }
}
