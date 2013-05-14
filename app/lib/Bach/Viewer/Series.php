<?php
/**
 * Series handling
 *
 * PHP version 5
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer;

use \Analog\Analog;

/**
 * Series
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class Series
{
    private $_path;
    private $_full_path;
    private $_start;
    private $_end;
    private $_content;
    private $_current;

    /**
     * Main constructor
     *
     * @param array  $roots Configured roots
     * @param string $path  Series path
     * @param string $start Sub series start point (optional)
     * @param string $end   Sub series end point (optional)
     */
    public function __construct($roots, $path, $start = null, $end = null)
    {
        $this->_path = $path;

        foreach ( $roots as $root ) {
            if ( file_exists($root . $path) && is_dir($root . $path) ) {
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

        if ( $this->_full_path === null ) {
            throw new \RuntimeException(
                _('No matching root found!')
            );
        } else {
            $this->_content = array();
            $handle = opendir($this->_full_path);
            while ( false !== ($entry = readdir($handle)) ) {
                if ($entry != "." && $entry != "..") {
                    try {
                        $picture = new Picture($entry, $this->_full_path);
                        $this->_content[] = $entry;
                    } catch (\RuntimeException $re) {
                        Analog::warning(
                            'Image type for ' . $entry . ' is not supported!'
                        );
                    }
                }
            }
            closedir($handle);
            sort($this->_content, SORT_STRING);
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
        if ( in_array($img, $this->_content) ) {
            $this->_current = $img;
            return true;
        } else {
            Analog::log(
                str_replace(
                    '%image',
                    $img,
                    _('Image %image is not part of current series!')
                )
            );
            return false;
        }
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
     * Get next image
     *
     * @return string
     */
    public function getNextImage()
    {
        $_index = array_search($this->_current, $this->_content);
        if ( $_index === count($this->_content) - 1 ) {
            $_index = 0;
        } else {
            $_index += 1;
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
     * Retrieve informations about current series
     *
     * @return array
     */
    public function getInfos()
    {
        if ( !isset($this->_current) ) {
            throw new \RuntimeException(
                _('Series has not been initialized yet.')
            );
        } else {
            $infos = array(
                'path'      => $this->_path,
                'current'   => $this->_current,
                'next'      => $this->getNextImage(),
                'prev'      => $this->getPreviousImage(),
                'count'     => $this->getCount(),
                'position'  => $this->getCurrentPosition()
            );
            return $infos;
        }
    }
}
