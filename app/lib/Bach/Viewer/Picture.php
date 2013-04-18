<?php
/**
 * Picture handling
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

/**
 * Picture
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class Picture
{
    const DEFAULT_WIDTH = 1000;
    const DEFAULT_HEIGHT = 1000;

    private $_path;
    private $_name;
    private $_full_path;
    private $_width;
    private $_height;
    private $_type;
    private $_mime;

    /**
     * Main constructor
     *
     * @param string $name Image name
     * @param string $path Image path, if known
     */
    public function __construct($name, $path=null)
    {
        $this->_name = $name;
        if ( $path !== null ) {
            $this->_path = $path;
        } else {
            //TODO: check path from roots
        }
        //normalize path
        if ( substr($this->_path, - 1) != '/' ) {
            $this->_path = $this->_path . '/';
        }
        $this->_full_path = $this->_path . $this->_name;

        list(
            $this->_width,
            $this->_height,
            $this->_type) = getimagesize($this->_full_path);
        $this->_mime = image_type_to_mime_type($this->_type);
    }

    /**
     * Displays the image
     *
     * @return void
     */
    public function display()
    {
        header('Content-type: '.$this->_mime);
        header('Content-Length: ' . filesize($this->_full_path));
        ob_clean();
        flush();
        readfile($this->_full_path);
    }

    /**
     * Get image URL to display in web interface
     *
     * @return string
     */
    public function getUrl()
    {
        return '/show/' . base64_encode($this->_full_path);
    }
}
