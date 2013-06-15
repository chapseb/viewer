<?php
/**
 * Bach's viewer images routes
 *
 * PHP version 5
 *
 * @category Routes
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

use \Bach\Viewer\Picture;

$app->get(
    '/dbg/show/:uri',
    function ($uri) use ($app, $conf, $app_base_url) {
        $picture = new Picture($conf, base64_decode($uri), $app_base_url);
        var_dump($picture);
    }
);

