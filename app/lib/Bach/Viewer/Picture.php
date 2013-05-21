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
    private $_conf;
    private $_exif;

    /**
     * Main constructor
     *
     * @param Conf   $conf Viewer configuration
     * @param string $name Image name
     * @param string $path Image path, if known
     */
    public function __construct($conf, $name, $path=null)
    {
        $this->_conf = $conf;
        $this->_name = $name;

        if ( $path !== null ) {
            $this->_path = $path;
        }

        //normalize path
        if ( substr($this->_path, - 1) !== '/'
            && !substr($this->_name, 0, 1) !== '/'
        ) {
            $this->_path = $this->_path . '/';
        }
        $this->_full_path = $this->_path . $this->_name;

        if ( !file_exists($this->_full_path) ) {
            if ( isset($this->_conf) ) {
                $roots = $this->_conf->getRoots();
                foreach ( $roots as $root ) {
                    if ( file_exists($root . $this->_full_path)
                        && is_file($root . $this->_full_path)
                    ) {
                        $this->_full_path = $root . $this->_full_path;
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

            //if file has not been found in roots, trhw an exception
            if ( !file_exists($this->_full_path) ) {
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
     * Get display informations (header, content, etc)
     *
     * @param string $format Format to display
     *
     * @return array
     */
    public function getDisplay($format = 'full')
    {
        $length = null;
        $file_path = null;
        if ( $format == 'full' ) {
            $file_path = $this->_full_path;
            $length = filesize($this->_full_path);
        } else {
            list($file_path, $length) = $this->_checkImageFormat($format);
        }

        $ret = array(
            'headers'   => array(
                'Content-Type'      => $this->_mime,
                'Content-Length'    => $length
            ),
            'content'   => file_get_contents($file_path)
        );
        return $ret;
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
        $path = $this->_conf->getPreparedPath() . $format . $this->_path;
        foreach ( $name_path as $np ) {
            $path .= $np . '/';
        }
        $image_path = $path . $image_name;

        if ( !file_exists($image_path) ) {
            //prepared image does not exists yet
            if ( file_exists($this->_conf->getPreparedPath())
                && is_dir($this->_conf->getPreparedPath())
                && is_writable($this->_conf->getPreparedPath())
            ) {
                if ( !file_exists($path) ) {
                    mkdir($path, 0755, true);
                }
                $this->_prepareImage($format);
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
     * @param string $format Wanted image format
     *
     * @return void
     */
    private function _prepareImage($format)
    {
        $reg = '/^(.*)\.([a-zZ-a]{3,4})/i';
        if ( preg_match($reg, $this->_name, $matches) ) {
            $ext = $matches[2];
            $dest = $this->_conf->getPreparedPath() . $format  .
                $this->_path . $this->_name;

            if ( class_exists('Gmagick') ) {
                Analog::warn('Gmagick is installed but not yet implemented!');
                $this->_gdResizeImage(
                    $this->_full_path,
                    $ext,
                    $dest,
                    $format
                );
            } else if ( class_exists('Imagick') ) {
                Analog::warn('Imagick is installed but not yet implemented!');
                $this->_gdResizeImage(
                    $this->_full_path,
                    $ext,
                    $dest,
                    $format
                );
            } else {
                //none of Gmagick or Imagick present, use GD
                $this->_gdResizeImage(
                    $this->_full_path,
                    $ext,
                    $dest,
                    $format
                );
            }
        } else {
            throw new \RuntimeException(
                str_replace(
                    '%file',
                    $this->_name,
                    _('Unknown file extension for %file')
                )
            );
        }
    }

    /**
    * Resize the image if it exceed max allowed sizes
    *
    * @param string $source the source image
    * @param string $ext    file's extension
    * @param string $dest   the destination image.
    * @param string $format the format to use
    *
    * @return void
    */
    private function _gdResizeImage($source, $ext, $dest, $format)
    {
        if (function_exists("gd_info")) {
            $gdinfo = gd_info();
            $fmt = $this->_conf->getFormats()[$format];
            $h = $fmt['height'];
            $w = $fmt['width'];

            switch(strtolower($ext)) {
            case 'jpg':
                if (!$gdinfo['JPEG Support']) {
                    Analog::log(
                        '[' . $class . '] GD has no JPEG Support - ' .
                        'pictures could not be resized!',
                        Analog::ERROR
                    );
                    return false;
                }
                break;
            case 'png':
                if (!$gdinfo['PNG Support']) {
                    Analog::log(
                        '[' . $class . '] GD has no PNG Support - ' .
                        'pictures could not be resized!',
                        Analog::ERROR
                    );
                    return false;
                }
                break;
            case 'gif':
                if (!$gdinfo['GIF Create Support']) {
                    Analog::log(
                        '[' . $class . '] GD has no GIF Support - ' .
                        'pictures could not be resized!',
                        Analog::ERROR
                    );
                    return false;
                }
                break;
            default:
                Analog::error(
                    'Uknknown ext'
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
            switch(strtolower($ext)) {
            case 'jpg':
                $image = ImageCreateFromJpeg($source);
                imagecopyresampled(
                    $thumb, $image, 0, 0, 0, 0, $w, $h, $cur_width, $cur_height
                );
                imagejpeg($thumb, $dest);
                break;
            case 'png':
                $image = ImageCreateFromPng($source);
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
            case 'gif':
                $image = ImageCreateFromGif($source);
                imagecopyresampled(
                    $thumb, $image, 0, 0, 0, 0, $w, $h, $cur_width, $cur_height
                );
                imagegif($thumb, $dest);
                break;
            }
        } else {
            Analog::log(
                '[' . $class . '] GD is not present - ' .
                'pictures could not be resized!',
                Analog::ERROR
            );
        }
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

        if ( count($this->_conf->getFormats()) > 0 ) {
            $formats = $this->_conf->getFormats();
            foreach ( $formats as $k=>$fmt ) {
                if ( $k !== 'thumb' ) {
                    $visibles[$k] = $k . ' ' . $fmt['width'] . 'x' . $fmt['height'];
                }
            }
            if ( !isset($formats['full']) ) {
                $visibles[_("full")] = _('full') . ' ' . $this->_width .
                    'x' . $this->_height;
            }
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
