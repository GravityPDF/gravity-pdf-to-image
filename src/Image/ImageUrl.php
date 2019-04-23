<?php

namespace GFPDF\Plugins\PdfToImage\Image;

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
 * Class ImageUrl
 *
 * @package GFPDF\Plugins\PdfToImage
 */
class ImageUrl {

	/**
	 * Generate the PDF to Image URL
	 *
	 * @param string $pdf_id   The Gravity PDF Settings ID
	 * @param int    $entry_id The Gravity Forms Entry ID
	 * @param int    $page     The page number
	 * @param bool   $download Whether the image should be downloaded or viewed
	 * @param bool   $escape   Whether to escape the URL or not
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public static function get( $pdf_id, $entry_id, $page, $download = false, $escape = true ) {
		global $wp_rewrite;

		/** @var \GFPDF\Model\Model_PDF $model_pdf */
		$model_pdf = \GPDFAPI::get_mvc_class( 'Model_PDF' );
		$url   = $model_pdf->get_pdf_url( $pdf_id, $entry_id, false, false, false );

		if ( $wp_rewrite->using_permalinks() ) {
			$url .= "img/$page/";

			if ( $download ) {
				$url .= 'download/';
			}
		} else {
			$url = add_query_arg(
				[
					'action' => 'img',
					'page'   => $page,
				],
				$url
			);

			if ( $download ) {
				$url = add_query_arg( 'sub_action', 'download', $url );
			}
		}

		if ( $escape ) {
			$url = esc_url( $url );
		}

		return $url;
	}
}