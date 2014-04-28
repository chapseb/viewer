<?php
/**
 * Series testing
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
class Series extends atoum
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
                $conf = new Viewer\Conf($this->_config_path);
                $series = New Viewer\Series(
                    $conf,
                    '/',
                    null
                );
            }
        )->hasMessage('No matching root found!');

        $series = New Viewer\Series(
            $this->_conf,
            '/',
            null
        );
    }

    /**
     * Test setImage
     *
     * @return void
     */
    public function testSetImage()
    {
        $set = $this->_series->setImage('toto.jpg');

        $this->boolean($set)->isFalse();

        $set = $this->_series->setImage('iron_man.jpg');
        $this->boolean($set)->isTrue();
    }

    /**
     * Test setNumberedImage
     *
     * @return void
     */
    public function testSetNumberedImage()
    {
        $set = $this->_series->setNumberedImage(100);
        $this->boolean($set)->isFalse();

        $set = $this->_series->setNumberedImage(1);
        $this->boolean($set)->isTrue();

        $img = $this->_series->getImage();
        $this->string($img)->isIdenticalTo('doms.jpg');
    }

    /**
     * Test getRepresentative
     *
     * @return void
     */
    public function testGetRepresentative()
    {
        $repr = $this->_series->getRepresentative();

        $this->string($repr)
            ->isIdenticalTo('doms.jpg');
    }

    /**
     * Test getImage
     *
     * @return void
     */
    public function testGetImage()
    {
        $this->_series->setImage('iron_man.jpg');
        $img = $this->_series->getImage();

        $this->string($img)->isIdenticalTo('iron_man.jpg');
    }

    /**
     * Test getInfos
     *
     * @return void
     */
    public function testGetInfos()
    {
        $series = $this->_series;
        $this->exception(
            function () use ($series) {
                $infos = $series->getInfos();
            }
        )->hasMessage('Series has not been initialized yet.');

        $repr = $this->_series->getRepresentative();
        $this->string($repr)
            ->isIdenticalTo('doms.jpg');

        $infos = $this->_series->getInfos();
        $this->array($infos)
            ->hasSize(6);

        $count = $infos['count'];
        $this->integer($count)->isEqualTo(7);

        $current = $infos['current'];
        $this->string($current)
            ->isIdenticalTo('doms.jpg');

        $next = $infos['next'];
        $this->string($next)
            ->isIdenticalTo('iron_man.gif');

        $prev = $infos['prev'];
        $this->string($prev)
            ->isIdenticalTo('tech.png');

        $position = $infos['position'];
        $this->integer($position)->isIdenticalTo(1);

        $series->setImage('tech.png');

        $infos = $this->_series->getInfos();
        $this->array($infos)
            ->hasSize(6);

        $next = $infos['next'];
        $this->string($next)
            ->isIdenticalTo('doms.jpg');

        $prev = $infos['prev'];
        $this->string($prev)
            ->isIdenticalTo('tech.jpg');
    }

    /**
     * Test getPath
     *
     * @return void
     */
    public function testGetPath()
    {
        $path = $this->_series->getPath();
        $this->string($path)->isIdenticalTo('');
    }

    /**
     * Test getFullPath
     *
     * @return void
     */
    public function testGetFullPath()
    {
        $fpath = $this->_series->getFullPath();
        $this->string($fpath)->isIdenticalTo($this->_roots[0] . '/');
    }

    /**
     * Test getThumbs
     *
     * @return void
     */
    public function testGetThumbs()
    {
        $formats = $this->_conf->getFormats();
        $fmt = $formats['thumb'];
        $thumbs_array = $this->_series->getThumbs($fmt);

        $this->array($thumbs_array)
            ->hasSize(2)
            ->hasKey('meta')
            ->hasKey('thumbs');

        $meta = $thumbs_array['meta'];
        $this->array($meta)
            ->isIdenticalTo($fmt);

        $thumbs = $thumbs_array['thumbs'];

        $this->array($thumbs)
            ->hasSize(7);

        //check standard image
        $standard = $thumbs[0];
        $standard_name = $standard['name'];
        $standard_path = $standard['path'];
        $this->string($standard_name)
            ->isIdenticalTo('doms.jpg');
        $this->string($standard_path)
            ->isIdenticalTo('doms.jpg');

        // check tiled image
        $tiled = $thumbs[3];
        $tiled_name = $tiled['name'];
        $tiled_path = $tiled['path'];
        $this->string($tiled_name)
            ->isIdenticalTo('iron_man_tiled.tif');
        $this->string($tiled_path)
            ->isIdenticalTo($this->_roots[0] . '/iron_man_tiled.tif');
    }

    /**
     * Test subseries
     *
     * @return void
     */
    public function testSubseries()
    {
        $series = New Viewer\Series(
            $this->_conf,
            '/',
            null,
            'doms.jpg',
            'tech.jpg'
        );

        $start = $series->getStart();
        $this->string($start)->isIdenticalTo('doms.jpg');

        $end = $series->getEnd();
        $this->string($end)->isIdenticalTo('tech.jpg');
    }
}
