<?php
/**
 * Bach's viewer app
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
 * @category Main
 * @package  Viewer
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

use \Slim\Slim;
use \Slim\Route;
use \Slim\Views\Twig;
use \Bach\Viewer\Conf;
use \Bach\Viewer\Picture;
use Bach\Viewer\Viewer;
use \Analog\Analog;

session_start();
$session = &$_SESSION['bachviewer'];

require APP_DIR . '/../vendor/autoload.php';

if ( defined('VIEWER_XHPROF_PATH')
    && function_exists('xhprof_enable')
) {
    $profiler = new Bach\XHProf();
    $profiler->start();
}

$logger = null;

$log_lvl = Analog::ERROR;
if ( APP_DEBUG === true ) {
    $log_lvl = Analog::DEBUG;
}

if ( defined('APP_TESTS') ) {
    $viewer_log = '';
    $logger = \Analog\Handler\Variable::init($viewer_log);
} else {
    $log_dir = APP_DIR . '/logs/';
    if ( defined('APP_LOGS') ) {
        $log_dir = APP_LOGS;
    }
    if ( !file_exists($log_dir) ) {
        throw new \RuntimeException(
            'Log directory (' . $log_dir  . ') does not exists!'
        );
    } else if ( !is_writeable($log_dir)  ) {
        throw new \RuntimeException(
            'Log directory (' . $log_dir . ') is not writeable!'
        );
    }
    $log_file = $log_dir . 'viewer.log';
    $logger = \Analog\Handler\File::init($log_file);
}
Analog::handler(
    \Analog\Handler\LevelBuffer::init(
        $logger,
        $log_lvl
    )
);

$conf = new Conf();

/** I18n stuff */
$lang = null;
$langs = array(
    'en-US',
    'fr-FR'
);

/** Try to detect user language */
if ( function_exists('http_negotiate_language') ) {
    $nego = http_negotiate_language($langs);
    switch ( strtolower($nego) ) {
    case 'en-us':
        $lang = 'en_US';
        break;
    case 'fr-fr':
        $lang = 'fr_FR.utf8';
        break;
    }
} else {
    if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
        $langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        if ( substr($langs, 0, 2) == 'fr' ) {
            $lang = 'fr_FR.utf8';
        }
    }
}

//fallback to english
if ( $lang === null ) {
    $lang = 'en_US';
}

putenv('LC_ALL=' . $lang);
setlocale(LC_ALL, $lang);

//Specify the location of the translation tables
bindtextdomain('BachViewer', APP_DIR . '/locale');
bind_textdomain_codeset('BachViewer', 'UTF-8');

//Choose domain
textdomain('BachViewer');
/** /I18n stuff */

$app = new Slim(
    array(
        'debug'             => APP_DEBUG,
        'view'              => new Twig(),
        'templates.path'    => APP_DIR . '/views'
    )
);

$app_base_url = '';
$app_title_html = $conf->getTitleHtml();
$app_favicon_path = $conf->getFaviconPath();
if ( strncmp($_SERVER['PHP_SELF'], '/index.php', strlen('/index.php')) 
    && strncmp($_SERVER['PHP_SELF'], '/debug.php', strlen('/debug.php'))
) {
    preg_match('/.*(index|debug)\.php/', $_SERVER['PHP_SELF'], $matches);
    if ( isset($matches[0]) ) {
        $app_base_url = $matches[0];
    }
}

$viewer = new Viewer($conf, $app_base_url);

$view = $app->view();
$view->parserExtensions = array(
    new Twig_Extensions_Extension_I18n()
);

if ( defined('APP_CACHE') && APP_CACHE !== false ) {
    $view->parserOptions = array(
        'cache'         => APP_CACHE,
        'auto_reload'   => true
    );
}

if ( !defined('DEFAULT_PICTURE') ) {
    define('DEFAULT_PICTURE', 'main.jpg');
}

$app->hook(
    'slim.before.dispatch',
    function () use ($app, $conf, $lang, $app_base_url) {
        //let's send view parameters before dispatching
        $v = $app->view();
        $ui = $conf->getUI();
        $v->setData('app_base_url', $app_base_url);
        $v->setData('app_title_html', $conf->getTitleHtml());
        $v->setData('app_favicon_path', $conf->getFaviconPath());
        $v->setData(
            'app_web_url',
            str_replace(
                array('/index.php', '/debug.php'),
                array('', ''),
                $app_base_url
            )
        );
        $v->setData(
            'enable_right_click',
            $ui['enable_right_click']
        );
        $v->setData('lang', $lang);
        $v->setData(
            'negate',
            $ui['negate']
        );
        $v->setData(
            'contrast',
            $ui['contrast']
        );
        $v->setData(
            'brightness',
            $ui['brightness']
        );
        $v->setData(
            'print',
            $ui['print']
        );
        $v->setData(
            'comment',
            $conf->getComment()
        );

        $fmts = $conf->getFormats();
        $v->setData('thumb_format', $fmts['thumb']);

        $remote_infos = $conf->getRemoteInfos();
        if ( $remote_infos !== false ) {
            $v->setData(
                'remote_method',
                $remote_infos['method']
            );
            $v->setData(
                'remote_uri',
                $remote_infos['uri']
            );
        }
    }
);

//set default conditions
Route::setDefaultConditions(
    array(
        'image'     => '.+\.[a-zA-Z]{3,4}',
        'series'    => '.+',
        'format'    => 'full|' . implode('|', array_keys($conf->getFormats()))
    )
);

//404 handler
$app->notFound(
    function () use ($app) {
        $app->render('404.html.twig');
    }
);

//custom error handler
$app->error(
    function (\Exception $e) use ($app, $conf, $app_base_url) {
        $resuUri = $app->request()->getResourceUri();

        $etype = get_class($e);
        Analog::error(
            'exception \'' . $etype . '\' with message \'' . $e->getMessage() .
            '\' in ' . $e->getFile()  . ':' . $e->getLine()  .
            "\nStack trace:\n" . $e->getTraceAsString()
        );

        if ( (substr($resuUri, 0, 10) === '/ajax/img/'
            || substr($resuUri, 0, 21) === '/ajax/representative/')
            && APP_DEBUG !== true
        ) {
            $format = 'default';
            preg_match('/.*\/format\/(.*)/', $resuUri, $matches);
            if ( isset($matches[1]) ) {
                $format = $matches[1];
            }
            $picture = new Picture(
                $conf,
                DEFAULT_PICTURE,
                $app_base_url
            );
            $display = $picture->getDisplay($format);
            $response = $app->response();
            foreach ( $display['headers'] as $key=>$header ) {
                $response[$key] = $header;
            }
            $response->body($display['content']);
        } else {
            $app->render(
                '50x.html.twig',
                array(
                    'exception' => $e
                )
            );
        }
    }
);

//main route
$app->get(
    '/',
    function () use ($app, $app_base_url) {
        $app->redirect($app_base_url . '/viewer/' . DEFAULT_PICTURE);
    }
);

//include routes files
require_once 'routes/images.routes.php';
require_once 'routes/series.routes.php';
require_once 'routes/ajax.routes.php';
if ( APP_DEBUG === true ) {
    include_once 'routes/debug.routes.php';
}

if ( !defined('APP_TESTS') ) {
    $app->run();

    if ( isset($profiler) ) {
        $profiler->stop();
    }
}
