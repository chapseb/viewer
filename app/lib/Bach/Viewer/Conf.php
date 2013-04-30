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

    /**
     * Main constructor
     */
    public function __construct()
    {
        if ( !defined('CONFIG_FILE') ) {
            throw new \RuntimeException(
                _('Configuration file not set!')
            );
        }

        if ( !defined('LOCAL_CONFIG_FILE') ) {
            Analog::log(
                _('No local configuration file present.'),
                Analog::WARNING
            );
        }

        $yaml = new Parser();
        $this->_conf = $yaml->parse(
            file_get_contents(CONFIG_FILE)
        );

        if ( defined('LOCAL_CONFIG_FILE') ) {
            $this->_conf = array_merge(
                $this->_conf,
                $yaml->parse(
                    file_get_contents(LOCAL_CONFIG_FILE)
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
