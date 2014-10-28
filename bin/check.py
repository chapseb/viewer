#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (c) 2014, Anaphore
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are
# met:
#
#     (1) Redistributions of source code must retain the above copyright
#     notice, this list of conditions and the following disclaimer.
#
#     (2) Redistributions in binary form must reproduce the above copyright
#     notice, this list of conditions and the following disclaimer in
#     the documentation and/or other materials provided with the
#     distribution.
#
#     (3)The name of the author may not be used to
#    endorse or promote products derived from this software without
#    specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
# IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
# INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
# SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
# HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
# STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
# IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.

import os
import argparse
import yaml
import urllib2
from termcolor import colored, cprint

name = 'BachviewChecker'
version = '0.0.1'
authors = ['Johan Cwiklinski']
author_mail = 'johan.cwiklinski@anaphore.eu'
locale_dir = os.path.join(os.path.dirname(os.path.realpath(__file__)), 'locale')
website = 'https://bitbucket.org/trashy/bachviewer/'


def readConfig():
    """Reads config and launch checks"""
    global args

    app_path = os.path.abspath(
        os.path.join(
            os.path.dirname(os.path.realpath(__file__)),
            os.pardir
        )
    )
    if args.app_path:
        app_path = args.app_path

    config_file = os.path.join(
        app_path, 'app/config/local.config.yml'
    )

    if args.verbose:
        print 'Reading config file %s' % config_file

    if os.path.exists(config_file):
        config = yaml.load(file(config_file, 'r'))
        if args.debug:
            print yaml.dump(config)

        doChecks(config)
    else:
        print_warning('Configuration file %s not found!' % config_file)


def doChecks(config):
    """Runs checks on each config entry"""
    for key in config:
        if key == 'roots':
            checkRoots(config['roots'])
        else:
            if type(config[key]) is dict:
                if key == 'ui':
                    checkUi(config[key])
                elif key == 'remote_infos':
                    checkRemoteInfos(config[key])
                elif key == 'print':
                    checkPrint(config[key])
                elif key == 'formats':
                    checkFormats(config[key])
                elif key == 'prepared_images':
                    checkPrepared(config[key])
                else:
                    print_warning('Unknown key %s' % key)
            else:
                print_error(
                    '"%s" entry is defined but is empty or not well formed!'
                    % key
                )


def checkRoots(roots):
    """Checks roots configuration"""
    global args

    if args.verbose:
        print 'Checking roots...'

    if type(roots) is list:
        for root in roots:
            if args.debug:
                print 'Checking root %s' % root

            if not os.path.exists(root):
                print_error('Defined root %s does not exists!' % root)

            if root[-1] is not '/':
                print_warning('Root %s does not ends with a /' % root)
    else:
        print_error('"roots" entry is defined but is empty!')


def checkUi(ui):
    """Check ui entry"""
    global args

    if args.verbose:
        print 'Checking UI config...'

    known_keys = [
        'enable_right_click',
        'negate',
        'print',
        'contrast',
        'brightness'
    ]

    for key in ui:
        if key not in known_keys:
            print_warning('Unknown key ui/%s' % key)
        else:
            if type(ui[key]) is not bool:
                print_error('Key ui/%s *must* be a boolean value!' % key)
            elif args.verbose:
                print_success('Key ui/%s OK' % key)


def checkRemoteInfos(remote):
    """Checks remote informations"""
    global args

    if args.verbose:
        print 'Checking remote infos config...'

    known_methods = ['bach', 'pleade']

    if len(remote) == 2:
        if remote['method'] not in known_methods:
            print_error('Method %s is not known' % remote['method'])
        elif args.verbose:
            print_success('Key remote_infos/method OK')

        if uri_exists(remote['uri']):
            if args.verbose:
                print_success('Remote URI %s OK' % remote['uri'])
        else:
            print_error(
                'Remote URI %s does not exists or return an error!'
                % remote['uri']
            )
    else:
        print_error('Remote infos must have two keys')


def uri_exists(uri):
    """Checks if a given URI do exists"""
    try:
        urllib2.urlopen(urllib2.Request(uri))
        return True
    except:
        return False


def checkPrint(printconf):
    """Checks print configuration"""
    global args

    if args.verbose:
        print 'Checking print config...'

    if printconf['header']:
        if type(printconf['header']) is dict:
            known_keys = [
                'image',
                'image_landscape',
                'image_portrait',
                'contents'
            ]

            for key in printconf['header']:
                if key in known_keys:
                    if key == 'contents':
                        if type(key) is unicode:
                            if args.verbose:
                                print_success('Key print/header/contents OK')
                        else:
                            print_error(
                                'Key print/header/contents must be a string!'
                            )
                    else:
                        file_path = printconf['header'][key]
                        if os.path.exists(file_path):
                            if args.verbose:
                                print_success('Key print/header/%s OK' % key)
                        else:
                            print_warning(
                                'Key print/header/%s points a missing file!'
                                % file_path
                            )
                else:
                    print_error('Unknown key print/header/%s' % key)
        else:
            print_error('Key print/header is not well formed')

    if printconf['footer']:
        if type(printconf['footer']) is unicode:
            if args.verbose:
                print_success('Key print/footer OK')
        else:
            print_error('Key print/footer is not well formed!')


def checkFormats(formats):
    """Checks formats configuration"""
    global args

    if args.verbose:
        print 'Checking print config...'

    for key in formats:
        if type(formats[key]) is dict:
            for skey in formats[key]:
                if skey == 'width' or skey == 'height':
                    if type(formats[key][skey]) is int:
                        if args.verbose:
                            print_success('Key formats/%s/%s OK' % (key, skey))
                    else:
                        print_error(
                            'Key formats/%s/%s must be an integer!'
                            % (key, skey)
                        )
                else:
                    print_error('Unknown key formats/%s/%s!' % (key, skey))
        else:
            print_error('Key formats/%s is not well formed!' % key)


def checkPrepared(prepared):
    """Checks prepared images configuration"""
    global args

    if args.verbose:
        print 'Checking prepared images config...'

    for key in prepared:
        if key == 'method':
            if prepared['method'] in ['choose', 'gd', 'imagick', 'gmagick']:
                pass
            else:
                print_error(
                    'Method %s in prepared_images/method key does not exists!'
                    % prepared['method']
                )
        elif key == 'path':
            file_path = prepared[key]
            if os.path.exists(file_path):
                if args.verbose:
                    print_success('Key prepared_images/path OK')
            else:
                print_error(
                    'Path %s in prepared_images/path key does not exists!'
                    % file_path
                )
        else:
            print_warning('Unknown key %s' % key)


def print_warning(msg):
    """Prints a warning message"""
    print colored(
        msg,
        'red'
    )


def print_error(msg):
    """Prints a fatal error message"""
    cprint(
        'FATAL ERROR: ' + msg,
        'white',
        'on_red',
        attrs=['bold']
    )


def print_success(msg):
    """"Prints a success message"""
    print colored(
        msg,
        'green'
    )


if '__main__' == __name__:
    parser = argparse.ArgumentParser(
        description='Bach viewer config check',
        version=version
    )

    parser.add_argument(
        '-p',
        '--path',
        dest='app_path',
        help='Define application path',
        action='store',
        nargs='?'
    )
    parser.add_argument(
        '-V',
        '--verbose',
        dest='verbose',
        help='Be a bit more verbose.',
        action='store_true',
        default=False
    )
    parser.add_argument(
        '-D',
        '--debug',
        dest='debug',
        help='Activate debug mode (show executes commands, etc.).',
        action='store_true',
        default=False
    )

    args = parser.parse_args()
    readConfig()
