<?php
/**
 * Configuration handling
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

use \Symfony\Component\Yaml\Parser;
use \Analog\Analog;

/**
 * Configuration
 *
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class Conf
{
    private $_conf;
    private $_roots;
    private $_formats;
    private $_ui;

    private $_path;
    private $_local_path;

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
            $this->_conf = array_merge(
                $this->_conf,
                $yaml->parse(
                    file_get_contents($this->_local_path)
                )
            );
        }

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

        $this->_roots = array();
        //check roots
        foreach ( $this->_conf['roots'] as $root ) {
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
     * Retrieve configured UI parts
     *
     * @return array
     */
    public function getUi()
    {
        return $this->_ui;
    }
}
