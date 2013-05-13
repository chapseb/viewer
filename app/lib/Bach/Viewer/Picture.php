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

use \Analog\Analog;

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

    private $_supported_types = array(
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_TIFF_II,
        IMAGETYPE_TIFF_MM
    );
    private $_pyramidal_types = array(
        IMAGETYPE_TIFF_II,
        IMAGETYPE_TIFF_MM
    );

    private $_path;
    private $_name;
    private $_full_path;
    private $_width;
    private $_height;
    private $_type;
    private $_mime;
    private $_pyramidal = false;
    private $_formats;
    private $_exif;

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
        if ( substr($this->_path, - 1) !== '/'
            && !substr($this->_name, 0, 1) !== '/'
        ) {
            $this->_path = $this->_path . '/';
        }
        $this->_full_path = $this->_path . $this->_name;

        if ( !file_exists($this->_full_path) ) {
            throw new \RuntimeException(
                str_replace(
                    '%file',
                    $this->_full_path,
                    _('File %file does not exists!')
                )
            );
        }

        $this->_exif = @exif_read_data($this->_full_path);

        if ( $this->_exif === false ) {
            //no exif data in picture, let's try another way
            list(
                $this->_width,
                $this->_height,
                $this->_type) = getimagesize($this->_full_path);
            $this->_mime = image_type_to_mime_type($this->_type);
        } else {
            if ( isset($this->_exif['ExifImageWidth']) ) {
                $this->_width = $this->_exif['ExifImageWidth'];
            } else if ( isset($this->_exif['ImageWidth']) ) {
                $this->_width = $this->_exif['ImageWidth'];
            } else if ( isset($this->_exif['COMPUTED']['Width']) ) {
                $this->_width = $this->_exif['COMPUTED']['Width'];
            } else {
                throw new \RuntimeException(_('Unable to get image width!'));
            }

            if ( isset($this->_exif['ExifImageLength']) ) {
                $this->_height = $this->_exif['ExifImageLength'];
            } else if ( isset($this->_exif['ImageLength']) ) {
                $this->_height = $this->_exif['ImageLength'];
            } else if ( isset($this->_exif['COMPUTED']['Height']) ) {
                $this->_height = $this->_exif['COMPUTED']['Height'];
            } else {
                throw new \RuntimeException(_('Unable to get image height!'));
            }

            $this->_type = $this->_exif['FileType'];
            $this->_mime =  $this->_exif['MimeType'];

            //checks pyramidal images
            if ( in_array($this->_type, $this->_pyramidal_types) ) {
                if ( isset($this->_exif['TileWidth'])
                    || isset($this->_exif['TileLength'])
                ) {
                    $this->_pyramidal = true;
                }
            }
        }

        if ( $formats !== null ) {
            $this->_formats = $formats;
        }

        $this->_check();
    }

    /**
     * Check if image is supported
     *
     * @return void
     */
    private function _check()
    {
        if ( !in_array($this->_type, $this->_supported_types) ) {
            throw new \RuntimeException(_('Unsupported image format!'));
        }

        if ( in_array($this->_type, $this->_pyramidal_types)
            && $this->_pyramidal === false
        ) {
            //TODO: maybe we can convert simple TIFF files to JPEG
            //instead of throwing an error
            throw new \RuntimeException(
                _('Image format not supported if not pyramidal')
            );
        }
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
        return $prefix . base64_encode($this->_full_path);
    }

    /**
     * Get "visible" formats (ie. no thumbs)
     *
     * @return array
     */
    public function getVisibleFormats()
    {
        $visibles = array();

        if ( count($this->_formats) > 0 ) {
            foreach ( $this->_formats as $k=>$fmt ) {
                if ( $k !== 'thumb' ) {
                    $visibles[$k] = $k . ' ' . $fmt['width'] . 'x' . $fmt['height'];
                }
            }
            if ( !isset($this->_formats['full']) ) {
                $visibles[_("full")] = _('full') . ' ' . $this->_width .
                    'x' . $this->_height;
            }
        } else {
            Analog::warning(_('No formats has been defined'));
        }
        return $visibles;
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

}
