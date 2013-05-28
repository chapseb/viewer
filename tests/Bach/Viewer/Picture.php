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
                    $this->_conf,
                    'blahblah'
                );
            }
        )->hasMessage('File /blahblah does not exists!');

        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    $this->_conf,
                    'saint-benezet.ico',
                    $this->_series->getFullPath()
                );
            }
        )->hasMessage('Unsupported image format!');

        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    $this->_conf,
                    'doms.tiff',
                    $this->_series->getFullPath()
                );
            }
        )->hasMessage('Image format not supported if not pyramidal');

        //test image from series
        $picture = new Viewer\Picture(
            $this->_conf,
            $this->_series->getRepresentative(),
            $this->_series->getFullPath()
        );

        //test unique image
        $picture = new Viewer\Picture(
            $this->_conf,
            'doms.jpg'
        );
    }

    /**
     * Test image display informations
     *
     * @return void
     */
    public function testGetDisplay()
    {
        $picture = new Viewer\Picture(
            $this->_conf,
            'doms.jpg'
        );

        $display = $picture->getDisplay();

        $this->array($display)
            ->hasSize(2)
            ->hasKey('headers')
            ->hasKey('content');

        $headers = $display['headers'];
        $this->array($headers)
            ->hasSize(2);

        $mime = $headers['Content-Type'];
        $this->string($mime)->isIdenticalTo('image/jpeg');

        $length = $headers['Content-Length'];
        $this->integer($length)->isIdenticalTo(20157);

        //remove thumb if exists
        //FIXME: maybe not the better way to do that,
        //but if image exists, it is not possible to test
        //image generation
        if ( file_exists('/tmp/thumb/doms.jpg') && is_file('/tmp/thumb/doms.jpg') ) {
            unlink('/tmp/thumb/doms.jpg');
        }
        if ( file_exists('/tmp/thumb/tech.png') && is_file('/tmp/thumb/tech.png') ) {
            unlink('/tmp/thumb/tech.png');
        }
        if ( file_exists('/tmp/thumb/iron_man.gif')
            && is_file('/tmp/thumb/iron_man.gif')
        ) {
            unlink('/tmp/thumb/iron_man.gif');
        }

        if ( file_exists('/tmp/thumb/') && is_dir('/tmp/thumb/') ) {
            rmdir('/tmp/thumb');
        }
        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        $this->integer($length)->isIdenticalTo(5234);

        //test with PNG image
        $picture = new Viewer\Picture(
            $this->_conf,
            'tech.png'
        );

        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        $this->integer($length)->isIdenticalTo(21898);

        //test with GIF image
        $picture = new Viewer\Picture(
            $this->_conf,
            'iron_man.gif'
        );

        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        $this->integer($length)->isIdenticalTo(13449);

    }

    /**
     * Test image transformation with unexistant prepared images path
     *
     * @return void
     */
    public function testMissingPreparedPath()
    {
        $config_path = APP_DIR . '/../tests/config/config-woprepared.yml';
        $conf = new Viewer\Conf($config_path);
        $conf->setRoots($this->_roots);

        $picture = new Viewer\Picture(
            $conf,
            'doms.jpg'
        );

        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        //thumb does not exists, size is full image size
        $this->integer($length)->isIdenticalTo(20157);
    }

    /**
     * Test image with COMPUTED exif informations only
     *
     * @return void
     */
    public function testComputedExifOnly()
    {
        $picture = new Viewer\Picture(
            $this->_conf,
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
            $this->_conf,
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
            $this->_conf,
            $this->_series->getRepresentative(),
            $this->_series->getFullPath()
        );
        $width = $picture->getWidth();
        $height = $picture->getHeight();
        $isPyramidal = $picture->isPyramidal();
        $fpath = $picture->getFullPath();
        $vformats = $picture->getVisibleFormats();
        $url = $picture->getUrl();
        $surl = $picture->getUrl('default');
        $name = $picture->getName();

        $this->integer($width)->isEqualTo(150);
        $this->integer($height)->isEqualTo(200);
        $this->boolean($isPyramidal)->isFalse();
        $this->string($fpath)->isIdenticalTo($this->_roots[0] . '/doms.jpg');
        $this->string($name)->isIdenticalTo('doms.jpg');
        $this->array($vformats)->hasSize(3);
        $this->string($url)->isIdenticalTo('/show/default/' . base64_encode($fpath));
        $this->string($surl)->isIdenticalTo(
            '/show/default/' . base64_encode($fpath)
        );
    }
}
