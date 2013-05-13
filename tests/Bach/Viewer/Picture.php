<?php
/**
 * Picture testing
 *
 * PHP version 5
 *
 * @category Main
 * @package  TestsViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer\tests\units;

use \atoum;
use Bach\Viewer;

require_once __DIR__ . '../../../../app/lib/Bach/Viewer/Conf.php';

/**
 * Series tests
 *
 * @category Main
 * @package  TestViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class Picture extends atoum
{
    private $_config_path;
    private $_conf;
    private $_roots;
    private $_series;

    /**
     * Set up tests
     *
     * @param stgring $testMethod Method tested
     *
     * @return void
     */
    public function beforeTestMethod($testMethod)
    {
        $this->_config_path = APP_DIR . '/../tests/config/config.yml';
        $this->_roots = array(
            TESTS_DIR . '/data/images'
        );
        $this->_name = 'iron_man.jpg';
        $this->_conf = new Viewer\Conf($this->_config_path);
        $this->_conf->setRoots($this->_roots);

        $this->_series = new Viewer\Series(
            $this->_conf->getRoots(),
            ''
        );

    }

    /**
     * Test main constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    $this->_name
                );
            }
        )->hasMessage('File /' . $this->_name  . ' does not exists!');

        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    'saint-benezet.ico',
                    $this->_series->getFullPath()
                );
            }
        )->hasMessage('Unsupported image format!');

        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    'doms.tiff',
                    $this->_series->getFullPath()
                );
            }
        )->hasMessage('Image format not supported if not pyramidal');

        $picture = new Viewer\Picture(
            $this->_series->getRepresentative(),
            $this->_series->getFullPath()
        );
    }

    /**
     * Test image with COMPUTED exif informations only
     *
     * @return void
     */
    public function testComputedExifOnly()
    {
        $picture = new Viewer\Picture(
            'tech.jpg',
            $this->_series->getFullPath()
        );
        $width = $picture->getWidth();
        $height = $picture->getHeight();
        $isPyramidal = $picture->isPyramidal();

        $this->integer($width)->isEqualTo(150);
        $this->integer($height)->isEqualTo(200);
        $this->boolean($isPyramidal)->isFalse();
    }

    /**
     * Test tiled image
     *
     * @return void
     */
    public function testTiledImage()
    {
        $picture = new Viewer\Picture(
            'iron_man_tiled.tif',
            $this->_series->getFullPath()
        );
        $width = $picture->getWidth();
        $height = $picture->getHeight();
        $isPyramidal = $picture->isPyramidal();

        $this->integer($width)->isEqualTo(200);
        $this->integer($height)->isEqualTo(150);
        $this->boolean($isPyramidal)->isTrue();
    }

    /**
     * Test image properties
     *
     * @return void
     */
    public function testImageProperties()
    {
        $picture = new Viewer\Picture(
            $this->_series->getRepresentative(),
            $this->_series->getFullPath(),
            $this->_conf->getFormats()
        );
        $width = $picture->getWidth();
        $height = $picture->getHeight();
        $isPyramidal = $picture->isPyramidal();
        $fpath = $picture->getFullPath();
        $vformats = $picture->getVisibleFormats();
        $url = $picture->getUrl();
        $surl = $picture->getUrl('default');

        $this->integer($width)->isEqualTo(150);
        $this->integer($height)->isEqualTo(200);
        $this->boolean($isPyramidal)->isFalse();
        $this->string($fpath)->isIdenticalTo($this->_roots[0] . '/doms.jpg');
        $this->array($vformats)->hasSize(3);
        $this->string($url)->isIdenticalTo('/show/' . base64_encode($fpath));
        $this->string($surl)->isIdenticalTo(
            '/show/default/' . base64_encode($fpath)
        );

        $picture = new Viewer\Picture(
            $this->_series->getRepresentative(),
            $this->_series->getFullPath()
        );

        $vformats = $picture->getVisibleFormats();
        $this->array($vformats)->hasSize(0);
    }
}
