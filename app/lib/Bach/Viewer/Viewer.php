<?php
/**
 * Bach viewer
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
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer;

use \Analog\Analog;
use \Slim\Http\Request;

/**
 * Bach viewer
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Viewer
{
    private $_conf;
    private $_app_base_url;

    /**
     * Main constructor
     *
     * @param Conf   $conf         Application configuration
     * @param string $app_base_url Application base URL
     */
    public function __construct(Conf $conf, $app_base_url)
    {
        $this->_conf = $conf;
        $this->_app_base_url = $app_base_url;
    }

    /**
     * Get an image
     *
     * @param string $series_path Path to series
     * @param string $image_name  Image name
     *
     * @return Picture
     */
    public function getImage($series_path, $image_name)
    {
        $picture = null;
        $fullpath = null;

        if ( trim($series_path) === '' ) {
            $series_path = null;
        }

        if ( $series_path !== null ) {
            $series = new Series(
                $this->_conf,
                $series_path,
                $this->_app_base_url
            );
            $series->setImage($image_name);
            $fullpath = $series->getFullPath();
        }

        if ( $series_path === null && $image_name === DEFAULT_PICTURE ) {
            $picture = new Picture(
                $this->_conf,
                DEFAULT_PICTURE,
                $this->_app_base_url,
                WEB_DIR . '/images/'
            );
        } else {
            $picture = new Picture(
                $this->_conf,
                $image_name,
                $this->_app_base_url,
                $fullpath
            );
        }

        return $picture;
    }

    /**
     * Bind a request
     *
     * @param Request $request Request
     *
     * @return array
     */
    public function bind(Request $request)
    {
        $params = array(
            'rotate'    => $request->params('r'),
            'negate'    => $request->params('n'),
            'crop'      => false
        );

        if ( $request->params('h') !== null && $request->params('h') !== null
            && $request->params('x') !== null && $request->params('y') !== null
        ) {
            $params['crop'] = array(
                'x' => $request->params('x'),
                'y' => $request->params('y'),
                'w' => $request->params('w'),
                'h' => $request->params('h')
            );
        }

        return $params;
    }
}
