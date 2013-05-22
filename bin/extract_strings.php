<?php
/**
 * String extraction from templates
 *
 * PHP version 5
 *
 * @category I18n
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
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
