<?php
/**
 * GD handler testing
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

namespace Bach\Viewer\Handlers\tests\units;

use \atoum;
use Bach\Viewer;

require_once __DIR__ . '../../../../../app/lib/Bach/Viewer/Conf.php';

/**
 * GD handler tests
 *
 * @category Main
 * @package  TestViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class GdHandler extends atoum
{
    private $_pyramidal_types = array(
        IMAGETYPE_TIFF_II,
        IMAGETYPE_TIFF_MM
    );

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
     * Test handler constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $handler = new Viewer\Handlers\GdHandler(
            $this->_conf,
            $this->_pyramidal_types
        );

        $extension = $handler->extensionName();
        $this->string($extension)->isEqualTo('gd');

        $this->exception(
            function () use ($handler) {
                $negate = $handler->canNegate();
            }
        )->hasMessage('Negate is not supported with gd!');

        $rotate = $handler->canRotate();
        $this->boolean($rotate)->isTrue();

        $crop = $handler->canCrop();
        $this->boolean($crop)->isTrue();
    }

    /**
     * Test handler constructor
     *
     * @return void
     */
    public function testCheck()
    {
        $handler = new Viewer\Handlers\GdHandler(
            $this->_conf,
            $this->_pyramidal_types
        );

        $handler->setType(IMAGETYPE_TIFF_MM);

        $this->exception(
            function () use ($handler) {
                $handler->check(false);
            }
        )->hasMessage('Image format not supported if not pyramidal');

        $handler->setType(IMAGETYPE_JPEG);
        $handler->check(true);

        $handler->setType('NOT_KNOWN_TYPE');
        $this->exception(
            function () use ($handler) {
                $handler->check(false);
            }
        )->hasMessage('Unsupported image format!');

    }

    /**
     * Test image infos retrieving
     *
     * @return void
     */
    public function testImageInfos()
    {
        $handler = new Viewer\Handlers\GdHandler(
            $this->_conf,
            $this->_pyramidal_types
        );

        $path = TESTS_DIR . '/data/images/iron_man.jpg';
        $infos = $handler->getImageInfos($path);

        $expected = array(
            0 => 200,
            1 => 150,
            2 => 2,
            3 => 'width="200" height="150"',
            'bits' => 8,
            'channels' => 3,
            'mime' => 'image/jpeg',
            4 => 'image/jpeg'
        );


        $this->array($infos)
            ->hasSize(8)
            ->isIdenticalTo($expected);
    }
}
