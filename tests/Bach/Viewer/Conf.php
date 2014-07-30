<?php
/**
 * Configuration testing
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
 * Configuration tests
 *
 * @category Main
 * @package  TestViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Conf extends atoum
{
    private $_conf;
    private $_path;
    private $_roots;

    /**
     * Set up tests
     *
     * @param stgring $testMethod Method tested
     *
     * @return void
     */
    public function beforeTestMethod($testMethod)
    {
        $this->_path = APP_DIR . '/../tests/config/config.yml';
        $this->_roots = array(
            TESTS_DIR . '/data/images'
        );
        $this->_conf = new Viewer\Conf($this->_path);
    }

    /**
     * Test main constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        //ensure code throws an exception if config file is missing
        $this->exception(
            function () {
                new Viewer\Conf('/path/to/non/existant/config');
            }
        )->hasMessage('Configuration file does not exists!');

        //a test with no additional configuration file
        $conf = new Viewer\Conf();
    }

    /**
     * Test getRoots
     *
     * @return void
     */
    public function testGetRoots()
    {
        $conf = new Viewer\Conf($this->_path);
        $roots = $conf->getRoots();
        //default configured roots does not exists
        $this->array($roots)->isEmpty();

        //set roots to really check
        $conf->setRoots($this->_roots);
        $roots = $conf->getRoots();
        $this->array($roots)
            ->hasSize(1)
            //note the ending '/' that should has been added
            ->strictlyContains(TESTS_DIR . '/data/images/');
    }

    /**
     * Test getFormats
     *
     * @return void
     */
    public function testGetFormats()
    {
        $formats = $this->_conf->getFormats();
        $this->array($formats)
            ->hasSize(4)
            ->hasKey('default')
            ->hasKey('huge')
            ->hasKey('medium')
            ->hasKey('thumb');

        $default = $formats['default'];
        $this->array($default)
            ->hasSize(2)
            ->hasKey('width')
            ->hasKey('height')
            ->strictlyContains(800);
    }

    /**
     * Tests getUI
     *
     * @return void
     */
    public function testGetUI()
    {
        //first, test default configuration
        $conf = new Viewer\Conf();
        $ui = $conf->getUI();
        $this->array($ui)
            ->hasSize(5)
            ->hasKey('enable_right_click')
            ->hasKey('negate')
            ->hasKey('print')
            ->hasKey('contrast')
            ->hasKey('brightness');

        //right click is enabled in default configuration
        $rc_enabled = $ui['enable_right_click'];
        $this->boolean($rc_enabled)->isTrue();

        $negate_enabled = $ui['negate'];
        $this->boolean($negate_enabled)->isTrue();

        $print_enabled = $ui['print'];
        $this->boolean($print_enabled)->isTrue();

        $contrast_enabled = $ui['contrast'];
        $this->boolean($contrast_enabled)->isTrue();

        $brightness_enabled = $ui['brightness'];
        $this->boolean($brightness_enabled)->isTrue();

        //then, test UT configuration
        $ui = $this->_conf->getUI();
        $this->array($ui)
            ->hasSize(5)
            ->hasKey('enable_right_click')
            ->hasKey('negate')
            ->hasKey('print');

        //right click is disabled in test configuration
        $rc_enabled = $ui['enable_right_click'];
        $this->boolean($rc_enabled)->isFalse();

        $negate_enabled = $ui['negate'];
        $this->boolean($negate_enabled)->isTrue();

        $print_enabled = $ui['print'];
        $this->boolean($print_enabled)->isTrue();

        $contrast_enabled = $ui['contrast'];
        $this->boolean($contrast_enabled)->isTrue();

        $brightness_enabled = $ui['brightness'];
        $this->boolean($brightness_enabled)->isTrue();
    }

    /**
     * Test IIP settings
     *
     * @return void
     */
    public function testGetIIP()
    {
        //first, test default configuration
        $conf = new Viewer\Conf();
        $iip = $conf->getIIP();
        $this->array($iip)
            ->hasSize(1)
            ->hasKey('server');

        $iipserver = $iip['server'];
        $this->string($iipserver)->isIdenticalTo('/iipsrv');

        //then, test UT configuration
        $iip = $this->_conf->getIIP();
        $this->array($iip)
            ->hasSize(1)
            ->hasKey('server');

        $iipserver = $iip['server'];
        $this->string($iipserver)->isIdenticalTo('/path/to/configured/iipsrv.fcgi');
    }

    /**
     * Test getPreparedPath
     *
     * @return void
     */
    public function testGetPreparedPath()
    {
        //first, test default configuration
        $conf = new Viewer\Conf();
        $ppath = $conf->getPreparedPath();

        $this->string($ppath)
            ->isIdenticalTo('/var/www/prepared_images/');

        //then, test UT configuration
        $ppath = $this->_conf->getPreparedPath();
        $this->string($ppath)
            ->isIdenticalTo('/tmp/');

    }

    /**
     * Test unknown prepare method
     *
     * @return void
     */
    public function testUnknownPrepareMethod()
    {
        $this->exception(
            function () {
                new Viewer\Conf(TESTS_DIR . '/config/config-unknownmethod.yml');
            }
        )->hasMessage('Prepare method  is not known.');

    }

    /**
     * Test known prepare methods
     *
     * @return void
     */
    public function testGetKnownPrepareMethods()
    {
        $methods = $this->_conf->getKnownPrepareMethods();

        $should = array(
            'choose',
            'gd',
            'imagick',
            'gmagick'
        );

        $this->array($methods)->isIdenticalTo($should);
    }

    /**
     * Test configured prepared method
     *
     * @return void
     */
    public function testGetPrepareMethod()
    {
        $method = $this->_conf->getPrepareMethod();
        $this->string($method)->isIdenticalTo('gd');

        $conf = new Viewer\Conf(TESTS_DIR . '/config/config-woprepared.yml');
        $method = $conf->getPrepareMethod();
        $this->string($method)->isIdenticalTo('choose');
    }
}
