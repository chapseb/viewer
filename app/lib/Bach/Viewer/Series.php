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
                    $this->_content[] = $entry;
                }
            }
            closedir($handle);
        }
    }

    /**
     * Retrieve series path
     *
     * @return string
     */
    public function getPath()
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
        return $this->_content[0];
    }
}
