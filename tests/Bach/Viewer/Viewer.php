<?php
/**
 * Viewer testing
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
use Bach\Viewer\Viewer as V;
use Bach\Viewer\Conf as C;
use Bach\Viewer\Series as S;
use Slim\Http\Request;

require_once __DIR__ . '../../../../app/lib/Bach/Viewer/Conf.php';

/**
 * Viewer tests
 *
 * @category Main
 * @package  TestViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Viewer extends atoum
{
    /*private $_app;*/
    private $_config_path;
    private $_roots;
    private $_conf;

    private $_request_params = array(
        'r' => 92,
        'n' => true,
        'x' => 2,
        'y' => 3,
        'w' => 100,
        'h' => 150,
        'c' => -2,
        'b' => '70'
    );

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
        /*$this->_name = 'iron_man.jpg';*/
        $this->_conf = new C($this->_config_path);
        $this->_conf->setRoots($this->_roots);

    }

    /**
     * Test get image
     *
     * @return void
     */
    public function testGetImage()
    {
        $viewer = new V($this->_conf, '');
        $picture = $viewer->getImage(null, 'doms.jpg');
        $picture = $viewer->getImage('./', 'tech.png');
        $picture = $viewer->getImage(null, DEFAULT_PICTURE);
    }

    /**
     * Test parameters binding
     *
     * @return void
     */
    public function testBindParams()
    {
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($this->_request_params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => array(
                'x' => '2',
                'y' => '3',
                'w' => '100',
                'h' => '150'
            ),
            'contrast'  => '-2',
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['x']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => '-2',
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['y']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => '-2',
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['w']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => '-2',
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['h']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => '-2',
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['x']);
        unset($params['r']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => null,
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => '-2',
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['x']);
        unset($params['c']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => null,
            'brightness'=> '70'
        );
        $this->array($binded)->isIdenticalTo($expected);

        $params = $this->_request_params;
        unset($params['x']);
        unset($params['b']);
        $request = new Request(
            \Slim\Environment::mock(
                array(
                    'QUERY_STRING' => http_build_query($params)
                )
            )
        );
        $viewer = new V($this->_conf, '');
        $binded = $viewer->bind($request);
        $expected = array(
            'rotate'    => '92',
            'negate'    => '1',
            'crop'      => false,
            'contrast'  => '-2',
            'brightness'=> null
        );
        $this->array($binded)->isIdenticalTo($expected);

    }
}
