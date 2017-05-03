<?php
/**
 * Configuration handling
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

use \Symfony\Component\Yaml\Parser;
use \Analog\Analog;

/**
 * Configuration
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Conf
{
    private $_conf;
    private $_roots;
    private $_formats;
    private $_ui;
    private $_iip;
    private $_print;
    private $_comment;
    private $_readingroom;
    private $_ip_internal;
    private $_remote_infos;

    private $_path;
    private $_local_path;
    private $_prepared_path;
    private $_prepare_method;
    private $_known_methods;
    private $_debug_mode;
    private $_notdownloadprint;

    private $_known_remote_methods = array(
        'bach',
        'pleade'
    );

    /**
     * Main constructor
     *
     * @param string $path Optional path to additional configuration file
     */
    public function __construct($path = null)
    {
        $this->_path = APP_DIR . '/config/config.yml';

        //set additional configuration path if not provided
        if ( $path === null ) {
            $this->_local_path = APP_DIR . '/config/local.config.yml';
        } else {
            $this->_local_path = $path;
            if ( !file_exists($this->_local_path) ) {
                throw new \RuntimeException('Configuration file does not exists!');
            }
        }

        $yaml = new Parser();
        $this->_conf = $yaml->parse(
            file_get_contents($this->_path)
        );

        if ( file_exists($this->_local_path) ) {
            $this->_conf = array_replace_recursive(
                $this->_conf,
                $yaml->parse(
                    file_get_contents($this->_local_path)
                )
            );
        }

        $this->_known_methods = array('choose', 'gd', 'imagick', 'gmagick');

        $this->_check();
    }

    /**
     * Check if config is valid
     *
     * @return void
     */
    private function _check()
    {
        $this->_ui = $this->_conf['ui'];
        $this->_formats = $this->_conf['formats'];

        $this->_comment = $this->_conf['comment'];
        $this->_readingroom = $this->_conf['readingroom'];
        $this->_ip_internal = $this->_conf['ip_internal'];

        $this->_prepared_path = $this->_conf['prepared_images']['path'];
        if ( substr($this->_prepared_path, - 1) != '/' ) {
            $this->_prepared_path .= '/';
        }

        if ( isset($this->_conf['prepared_images']['method']) ) {
            $method = $this->_conf['prepared_images']['method'];
            if ( !in_array($method, $this->_known_methods) ) {
                throw new \RuntimeException(
                    str_replace(
                        '%method',
                        $this->_prepare_method,
                        _('Prepare method %method is not known.')
                    )
                );
            } else {
                $this->_prepare_method = $method;
            }
        } else {
            $this->_prepare_method = 'choose';
        }

        $this->_iip = $this->_conf['iip'];
        $this->_setRoots($this->_conf['roots']);

        $this->_print = $this->_conf['print'];

        if ( $this->_conf['remote_infos'] === null ) {
            $this->_remote_infos = false;
        } else {
            $rmethod = $this->_conf['remote_infos']['method'];
            if ( !in_array($rmethod, $this->_known_remote_methods) ) {
                throw new \RuntimeException(
                    str_replace(
                        '%method',
                        $rmethod,
                        'Unknwon remote method %method!'
                    )
                );
            }
            $this->_remote_infos = array(
                'method'    => $rmethod,
                'uri'       => $this->_conf['remote_infos']['uri']
            );
        }
        $this->_debug_mode            = $this->_conf['debug_mode'];
        $this->_notdownloadprint      = $this->_conf['notdownloadprint'];
    }

    /**
     * Set roots directories
     *
     * @param array $roots array of root directories
     *
     * @return void
     */
    private function _setRoots($roots)
    {
        $this->_roots = array();
        //check roots
        foreach ( $roots as $root ) {
            if ( file_exists($root) && is_dir($root) ) {
                //normalize path
                if ( substr($root, - 1) != '/' ) {
                    $root .= '/';
                }
                //path does exists and is a directory
                Analog::log(
                    str_replace(
                        '%root',
                        $root,
                        _('Added root path: %root')
                    ),
                    Analog::DEBUG
                );
                $this->_roots[] = $root;
            } else {
                Analog::log(
                    str_replace(
                        '%root',
                        $root,
                        _('The root path "%root" does not exists or is not a directory!')
                    ),
                    Analog::ERROR
                );
            }
        }
    }

    /**
     * Retrieve configured roots
     *
     * @return array
     */
    public function getRoots()
    {
        return $this->_roots;
    }

    /**
     * Retrieve configured formats
     *
     * @return array
     */
    public function getFormats()
    {
        return $this->_formats;
    }

    /**
     * Retrieve if comment is allowed
     *
     * @return string
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * Retrieve readingroom ip
     *
     * @return string
     */
    public function getReadingroom()
    {
        return $this->_readingroom;
    }

    /**
     * Retrieve if internal ip for communicability
     *
     * @return string
     */
    public function getIpinternal()
    {
        return $this->_ip_internal;
    }

    /**
     * Retrieve configured UI parts
     *
     * @return array
     */
    public function getUi()
    {
        return $this->_ui;
    }

    /**
     * Retrieve IIP configuration
     *
     * @return array
     */
    public function getIIP()
    {
        return $this->_iip;
    }

    /**
     * Retrieve prepared images path
     *
     * @return string
     */
    public function getPreparedPath()
    {
        return $this->_prepared_path;
    }

    /**
     * Retrieve know prepare methods
     *
     * @return array
     */
    public function getKnownPrepareMethods()
    {
        return $this->_known_methods;
    }

    /**
     * Retrieve prepare method
     *
     * @return string
     */
    public function getPrepareMethod()
    {
        return $this->_prepare_method;
    }

    /**
     * Retrieve print header image
     *
     * @param char $orientation Either L(andscape) or P(ortrait)
     *
     * @return string
     */
    public function getPrintHeaderImage($orientation = null)
    {
        $image = $this->_print['header']['image'];

        if ( $orientation === 'P'
            && isset($this->_print['header']['image_portrait'])
        ) {
            $image = $this->_print['header']['image_portrait'];
        }

        if ( $orientation === 'L'
            && isset($this->_print['header']['image_landscape'])
        ) {
            $image = $this->_print['header']['image_landscape'];
        }

        return $image;
    }

    /**
     * Retrieve print footer
     *
     * @return string
     */
    public function getPrintFooter($orientation = null)
    {

        $image = $this->_print['footer']['image'];

        if ( $orientation === 'P'
            && isset($this->_print['footer']['image_portrait'])
        ) {
            $image = $this->_print['footer']['image_portrait'];
        }

        if ( $orientation === 'L'
            && isset($this->_print['footer']['image_landscape'])
        ) {
            $image = $this->_print['footer']['image_landscape'];
        }

        return $image;
    }

    /**
     * Retrieve remote informations
     *
     * @return false|array
     */
    public function getRemoteInfos()
    {
        return $this->_remote_infos;
    }

    /**
     * Convenient function to set roots.
     *
     * Configuration file for unit tests is always irrelevant
     * regarding roots path, since none of them does exists.
     * This function does nothing outside of unit tests.
     *
     * @param array $roots Array of existing roots paths
     *
     * @return void
     */
    public function setRoots(array $roots)
    {
        if ( defined('APP_TESTS') ) {
            $this->_setRoots($roots);
        }
    }

    /**
     * Retrieve debug mode in config
     *
     * @return boolean
     */
    public function getDebugMode()
    {
        return $this->_debug_mode;
    }

    /**
     * Retrieve download print param
     *
     * @return boolean
     */
    public function getNotDownloadPrint()
    {
        return $this->_notdownloadprint;
    }
}
