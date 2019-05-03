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
 * Class Common
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class Common {

	protected $tmp_path;

	/**
	 * Common constructor.
	 *
	 * @param $tmp_path
	 */
	public function __construct( $tmp_path ) {
		$this->tmp_path = $tmp_path;
	}


	/**
	 * Return the image configuration from the PDF settings
	 *
	 * @param array $settings A form's PDF setting
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_settings( $settings ) {
		$page    = isset( $settings['pdf_to_image_page'] ) ? $settings['pdf_to_image_page'] : 1;
		$dpi     = isset( $settings['pdf_to_image_dpi'] ) ? $settings['pdf_to_image_dpi'] : 150;
		$quality = isset( $settings['pdf_to_image_quality'] ) ? $settings['pdf_to_image_quality'] : 95;
		$width   = isset( $settings['pdf_to_image_resize_and_crop'][0] ) ? $settings['pdf_to_image_resize_and_crop'][0] : 800;
		$height  = isset( $settings['pdf_to_image_resize_and_crop'][1] ) ? $settings['pdf_to_image_resize_and_crop'][1] : 600;
		$crop    = ! empty( $settings['pdf_to_image_resize_and_crop'][2] ) ? true : false;

		return [
			'page'    => $page,
			'dpi'     => $dpi,
			'quality' => $quality,
			'width'   => $width,
			'height'  => $height,
			'crop'    => $crop,
		];
	}

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
	public function get_url( $pdf_id, $entry_id, $page, $download = false, $escape = true ) {
		global $wp_rewrite;

		/** @var \GFPDF\Model\Model_PDF $model_pdf */
		$model_pdf = \GPDFAPI::get_mvc_class( 'Model_PDF' );
		$url       = $model_pdf->get_pdf_url( $pdf_id, $entry_id, false, false, false );

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

	/**
	 * Converts the PDF name to the associated image name
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function get_name_from_pdf( $file ) {
		return sprintf( '%s.jpg', basename( $file, '.pdf' ) );
	}

	/**
	 * @param string $file
	 * @param int $form_id
	 * @param int $entry_id
	 *
	 * @return string
	 */
	public function get_image_path_from_pdf( $file, $form_id, $entry_id ) {
		$image_tmp_directory = $this->tmp_path . $form_id . $entry_id . '/';
		$image_name          = $this->get_name_from_pdf( $file );

		return $image_tmp_directory . $image_name;
	}
}
