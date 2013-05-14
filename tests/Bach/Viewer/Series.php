<?php
/**
 * Series testing
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
            $this->_conf->getRoots(),
            ''
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
                $series = New Viewer\Series(
                    array(),
                    '/'
                );
            }
        )->hasMessage('No matching root found!');

        $series = New Viewer\Series(
            $this->_conf->getRoots(),
            '/'
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
        $this->integer($count)->isEqualTo(5);

        $current = $infos['current'];
        $this->string($current)
            ->isIdenticalTo('doms.jpg');

        $next = $infos['next'];
        $this->string($next)
            ->isIdenticalTo('iron_man.jpg');

        $prev = $infos['prev'];
        $this->string($prev)
            ->isIdenticalTo('tech.jpg');

        $position = $infos['position'];
        $this->integer($position)->isIdenticalTo(1);

        $series->setImage('tech.jpg');

        $infos = $this->_series->getInfos();
        $this->array($infos)
            ->hasSize(6);

        $next = $infos['next'];
        $this->string($next)
            ->isIdenticalTo('doms.jpg');

        $prev = $infos['prev'];
        $this->string($prev)
            ->isIdenticalTo('saint-benezet.jpg');
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
}
