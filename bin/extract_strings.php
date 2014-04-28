<?php
/**
 * String extraction from templates
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
 * @category I18n
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

$templates_dir = realpath(__DIR__ . '/../app/views');

$args = getopt(null, array('tmp:'));

if ( !isset($args['tmp']) ) {
    die('Missing required --tmp argument.');
}
$temp_dir = $args['tmp'];

if ( !file_exists($temp_dir) ) {
    die('Temp directory does not exists!');
}

if ( !is_dir($temp_dir) ) {
    die('Temp directory is not a directory!');
}

if ( !is_writable($temp_dir) ) {
    die('Temp directory is not writable!');
}

require_once 'vendor/autoload.php';
echo 'Will process templates files from ' . $templates_dir .
    ' cached into ' . $temp_dir . "\n";

$loader = new Twig_Loader_Filesystem($templates_dir);

// force auto-reload to always have the latest version of the template
$twig = new Twig_Environment(
    $loader,
    array(
        'cache' => $temp_dir,
        'auto_reload' => true
    )
);
$twig->addExtension(
    new Twig_Extensions_Extension_I18n()
);
// configure Twig the way you want

// iterate over all your templates
foreach (
    new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($templates_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    ) as $file
) {
    // force compilation
    if ($file->isFile()) {
        $twig->loadTemplate(str_replace($templates_dir. '/', '', $file));
    }
}
