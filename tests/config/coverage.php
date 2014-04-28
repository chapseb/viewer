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
 * @category  Main
 * @package   TestsViewer
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2013 Anaphore
 * @license   Unknown http://unknown.com
 * @link      http://anaphore.eu
 */

use \mageekguy\atoum;

$tests_dir = __DIR__ . '/../../tests-results/';
$coverage_dir = $tests_dir . 'code-coverage/';

if ( !file_exists($tests_dir) ) {
    mkdir($tests_dir);
    mkdir($tests_dir . 'code-coverage');
}

$coverageHtmlField = new atoum\report\fields\runner\coverage\html(
    'Bach Viewer',
    $coverage_dir
);
$coverageHtmlField->setRootUrl('file://' . realpath($coverage_dir));

$xunitWriter = new atoum\writers\file($tests_dir . '/atoum.xunit.xml');
$cloverWriter = new atoum\writers\file($tests_dir . '/clover.xml');

//Not relevant for now
/*$coverageTreemapField = new atoum\report\fields\runner\coverage\treemap(
    'Bach Viewer',
    __DIR__ . '/../treemap'
);
$coverageTreemapField
    ->setTreemapUrl('file://' . realpath(__DIR__ . '/../treemap'))
    ->setHtmlReportBaseUrl($coverageHtmlField->getRootUrl());*/

$xunitReport = new atoum\reports\asynchronous\xunit();
$xunitReport->addWriter($xunitWriter);

$clover = new atoum\reports\asynchronous\clover();
$clover->addWriter($cloverWriter);

$runner->addReport($xunitReport);
$runner->addReport($clover);
$script
    ->addDefaultReport()
    ->addField($coverageHtmlField)
    /*->addField($coverageTreemapField)*/;
