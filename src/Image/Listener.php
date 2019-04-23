<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Helper\Helper_PDF;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
	This file is part of Gravity PDF to Image.

	Copyright (c) 2019, Blue Liquid Designs

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Class Listener
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class Listener {
	public function init() {
		add_action( 'gfpdf_pre_pdf_generation_output', [ $this, 'maybe_generate_image_from_pdf' ], 10, 5 );
	}

	/**
	 * @param \Mpdf\Mpdf $mpdf
	 * @param array      $form
	 * @param array      $entry
	 * @param array      $settings
	 * @param Helper_PDF $helper_pdf
	 *
	 * @throws \Exception
	 */
	public function maybe_generate_image_from_pdf( $mpdf, $form, $entry, $settings, $helper_pdf ) {

		$action    = isset( $GLOBALS['wp']->query_vars['action'] ) ? $GLOBALS['wp']->query_vars['action'] : '';
		$subaction = isset( $GLOBALS['wp']->query_vars['sub_action'] ) ? $GLOBALS['wp']->query_vars['sub_action'] : '';
		$page      = isset( $GLOBALS['wp']->query_vars['page'] ) ? $GLOBALS['wp']->query_vars['page'] : 0;

		/* Do nothing if not requesting an image */
		if ( $action !== 'img' ) {
			return;
		}

		/* TODO - add support for additional URL parameters if authorised user (like we do with `template`) */
		$dpi     = isset( $settings['pdf_to_image_dpi'] ) ? $settings['pdf_to_image_dpi'] : 150;
		$quality = isset( $settings['pdf_to_image_quality'] ) ? $settings['pdf_to_image_quality'] : 95;
		$width   = isset( $settings['pdf_to_image_resize_and_crop'][0] ) ? $settings['pdf_to_image_resize_and_crop'][0] : 800;
		$height  = isset( $settings['pdf_to_image_resize_and_crop'][1] ) ? $settings['pdf_to_image_resize_and_crop'][1] : 600;
		$crop    = ! empty( $settings['pdf_to_image_resize_and_crop'][2] ) ? true : false;

		$image = new Generate(
			$helper_pdf->save_pdf( $mpdf->Output( '', \Mpdf\Output\Destination::STRING_RETURN ) ),
			$page,
			$dpi,
			$quality,
			$width,
			$height,
			$crop
		);

		if ( $subaction === 'download' ) {
			$image->to_download();
		} else {
			$image->to_screen();
		}

		wp_die();
	}


}