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
                new Viewer\Conf('/path/to/non/existant/config/');
            }
        )->hasMessage('Missing configuration file.');

        $conf = new Viewer\Conf(APP_DIR . '/../tests/config/');

    }
}
