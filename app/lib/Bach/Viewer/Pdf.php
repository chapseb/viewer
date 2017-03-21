<?php
/**
 * PDF generation
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
 * @author   Vincent Fleurette <vincent.fleurette@anaphore.eu>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\Viewer;

use \Analog\Analog;

/**
 * PDF Generation
 *
 * @category Main
 * @package  Viewer
 * @author   Vincent Fleurette <vincent.fleurette@anaphore.eu>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class Pdf extends \TCPDF
{
    private $_conf;
    private $_picture;
    private $_params;
    private $_image_format;
    private $_header_height = 0;
    private $_footer_height = 0;

    /**
     * Main constructor
     *
     * @param Conf    $conf    App Configuration
     * @param Picture $picture Picture
     * @param array   $params  Print params
     * @param string  $format  Image format
     */
    public function __construct(Conf $conf, Picture $picture, $params, $format)
    {
        $orientation = 'P';

        $w = $picture->getWidth();
        $h = $picture->getHeight();

        if ( $params['crop'] !== false ) {
            if ( $params['crop']['w'] < $w ) {
                $w = $params['crop']['w'];
            }
            if ( $params['crop']['h'] ) {
                $h = $params['crop']['h'];
            }
        }

        if ( $w > $h ) {
            $orientation = 'L';
        }
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8');
        $this->_conf = $conf;
        $this->_picture = $picture;
        $this->_params = $params;
        $this->_image_format = $format;

        $this->setCreator('Bach - ' . PDF_CREATOR);
        $this->setTitle($picture->getName());
    }

    /**
     * Default header
     *
     * @return void
     */
    public function Header()
    {
        $image = $this->_conf->getPrintHeaderImage($this->CurOrientation);
        if ( file_exists($image) ) {
            $this->SetY(5);
            $this->writeHTML(
                '<img src="' . $image . '"/>'
            );

            $this->_header_height = ceil($this->getY());
        } else {
            Analog::error(
                str_replace(
                    '%file',
                    $image,
                    'File %file does not exists!'
                )
            );
        }
    }

    /**
     * Default footer
     *
     * @return void
     */
    public function Footer()
    {
        $image = $this->_conf->getPrintFooter($this->CurOrientation);
        if ( file_exists($image) ) {
            $this->SetY(280);
            $this->writeHTML(
                '<img src="' . $image . '"/>'
            );

            $this->_footer_height = ceil($this->getY());
        } else {
            Analog::error(
                str_replace(
                    '%file',
                    $image,
                    'File %file does not exists!'
                )
            );
        }
    }

    /**
     * Prepare image in PDF
     *
     * @return void
     */
    private function _prepareImage()
    {
        /** FIXME: parametize? */
        $tmp_name = '/tmp/';
        $tmp_name .= uniqid(
            base64_encode($this->_picture->getName()),
            true
        );

        $display = $this->_picture->getDisplay(
            $this->_image_format,
            $this->_params,
            $tmp_name
        );

        $this->AddPage();
        if ( $this->_header_height > 0 ) {
            $this->setTopMargin($this->_header_height);
        }

        $html = '<img src="' .  $tmp_name . '"/>';
        $this->writeHTML($html, true, false, true, false, '');
        unlink($tmp_name);
    }

    /**
     * Retrieve PDF content for display in page
     *
     * @return string
     */
    public function getContent()
    {
        $this->_prepareImage();
        return $this->Output('bach_print.pdf', 'S');
    }

    /**
     * Dowload PDF
     *
     * @return void
     */
    public function download()
    {
        $this->_prepareImage();
        $this->output('bach_print.pdf', 'D');
    }

}
