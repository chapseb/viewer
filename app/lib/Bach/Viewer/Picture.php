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
    private $_formats;

    /**
     * Main constructor
     *
     * @param string $name    Image name
     * @param string $path    Image path, if known
     * @param array  $formats Image formats
     */
    public function __construct($name, $path=null, $formats = null)
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

        $this->_formats = $formats;
        $this->_checkFormats();
    }

    /**
     * Checks for image formats
     *
     * @return void
     */
    private function _checkFormats()
    {
    }

    /**
     * Displays the image
     *
     * @param string $format Format to display
     *
     * @return void
     */
    public function display($format = 'full')
    {
        $length = null;
        if ( $format == 'full' ) {
            $length = filesize($this->_full_path);
        }
        //TODO: serve other formats, resize image, and so on
        header('Content-type: ' . $this->_mime);
        header('Content-Length: ' . $length);
        ob_clean();
        flush();
        readfile($this->_full_path);
    }

    /**
     * Get image URL to display in web interface
     *
     * @param string $format Format to display
     *
     * @return string
     */
    public function getUrl($format = 'full')
    {
        //TODO: serve other formats, resize image, and so on
        $prefix = '/show/';
        if ( $format !== 'full' ) {
            $prefix .= $format . '/';
        }
        return '/show/' . base64_encode($this->_full_path);
    }

    /**
     * Get "visible" formats (ie. no thumbs)
     *
     * @return array
     */
    public function getVisibleFormats()
    {
        $visibles = array();

        foreach ( $this->_formats as $k=>$fmt ) {
            if ( $k !== 'thumb' ) {
                $visibles[$k] = $k . ' ' . $fmt['width'] . 'x' . $fmt['height'];
            }
        }
        if ( !isset($this->_formats['full']) ) {
            $visibles[_("full")] = _('full') . ' ' . $this->_width .
                'x' . $this->_height;
        }
        return $visibles;
    }
}
