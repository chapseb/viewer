<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Bach Viewer code coverage configuration for atoum.
 *
 * To launch the code coverage, please make sure you have pecl-xdebug installed,
 * go to viewer main tests directory and run:
 * $ php vendor/atoum/atoum/bin/atoum \
 *       -bf tests/Bootstrap.php      \
 *       -d tests/Bach                \
 *       -c tests/config/coverage.php
 *
 * PHP version 5
 *
 * @category  Main
 * @package   TestsViewer
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2013 Anaphore
 * @license   Unknown http://unknown.com
 * @link      http://anaphore.eu
 */

use \mageekguy\atoum\report\fields\runner\coverage;

$coverageHtmlField = new coverage\html('Bach Viewer', __DIR__ . '/../coverage');
$coverageHtmlField->setRootUrl('file://' . realpath(__DIR__ . '/../coverage'));

//Not relevant for now
/*$coverageTreemapField = new coverage\treemap(
    'Bach Viewer',
    __DIR__ . '/../treemap'
);
$coverageTreemapField
    ->setTreemapUrl('file://' . realpath(__DIR__ . '/../treemap'))
    ->setHtmlReportBaseUrl($coverageHtmlField->getRootUrl());*/

$script
    ->addDefaultReport()
    ->addField($coverageHtmlField)
    /*->addField($coverageTreemapField)*/;
