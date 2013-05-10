<?php
/**
 * Configuration testing
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
 * Configuration tests
 *
 * @category Main
 * @package  TestViewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
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
            ->hasSize(3)
            ->hasKey('default')
            ->hasKey('huge')
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
            ->hasSize(1)
            ->hasKey('enable_right_click');

        //right click is enabled in default configuration
        $rc_enabled = $ui['enable_right_click'];
        $this->boolean($rc_enabled)->isTrue();

        //then, test UT configuration
        $ui = $this->_conf->getUI();
        $this->array($ui)
            ->hasSize(1)
            ->hasKey('enable_right_click');

        //right click is disabled in test configuration
        $rc_enabled = $ui['enable_right_click'];
        $this->boolean($rc_enabled)->isFalse();
    }
}