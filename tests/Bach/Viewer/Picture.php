<?php
/**
 * Picture testing
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
 * @package  TestsViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer\tests\units;

use \atoum;
use Bach\Viewer;

require_once __DIR__ . '../../../../app/lib/Bach/Viewer/Conf.php';

/**
 * Picture tests
 *
 * @category Main
 * @package  TestViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Picture extends atoum
{
    private $_config_path;
    private $_conf;
    private $_roots;
    private $_series;
    private $_name;

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
            $this->_conf,
            '',
            null
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
                    'blahblah',
                    null
                );
            }
        )->hasMessage('File blahblah does not exists!');

        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    $this->_conf,
                    'saint-benezet.ico',
                    null,
                    $this->_series->getFullPath()
                );
            }
        )->hasMessage('Unsupported image format!');

        $this->exception(
            function () {
                $picture = new Viewer\Picture(
                    $this->_conf,
                    'doms.tiff',
                    null,
                    $this->_series->getFullPath()
                );
            }
        )->hasMessage('Image format not supported if not pyramidal');

        //test image from series
        $picture = new Viewer\Picture(
            $this->_conf,
            $this->_series->getRepresentative(),
            null,
            $this->_series->getFullPath()
        );

        //test unique image
        $picture = new Viewer\Picture(
            $this->_conf,
            'doms.jpg',
            null
        );

        //test with missing ending/ in path
        $picture = new Viewer\Picture(
            $this->_conf,
            $this->_series->getRepresentative(),
            null,
            rtrim($this->_series->getFullPath(), '/')
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
            'doms.jpg',
            null
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
        if ( file_exists('/tmp/thumb/tech.jpg') && is_file('/tmp/thumb/tech.jpg') ) {
            unlink('/tmp/thumb/tech.jpg');
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

        $this->integer($length)->isIdenticalTo(5239);

        //test with PNG image
        $picture = new Viewer\Picture(
            $this->_conf,
            'tech.png',
            null
        );

        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        $this->integer($length)->isIdenticalTo(21539);

        //test with GIF image
        $picture = new Viewer\Picture(
            $this->_conf,
            'iron_man.gif',
            null
        );

        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        $this->integer($length)->isIdenticalTo(13365);

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
            'doms.jpg',
            null
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
            null,
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
            null,
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
            null,
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

    /** Test image inside a subdirectory
     *
     * @return void
     */
    public function testSeriesImage()
    {
        $picture = new Viewer\Picture(
            $this->_conf,
            $this->_series->getFullPath() . 'tech.jpg',
            null
        );

        $display = $picture->getDisplay('thumb');
        $length = $display['headers']['Content-Length'];

        $this->integer($length)->isIdenticalTo(6827);
    }
}
