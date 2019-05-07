<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Helper\Helper_PDF;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use Mpdf\Output\Destination;

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

	/**
	 * @var Common
	 * @since 1.0
	 */
	protected $image_common;

	/**
	 * @var PdfSecurity
	 * @since 1.0
	 */
	protected $pdf_security;

	/**
	 * Listener constructor.
	 *
	 * @param Common      $image_common
	 * @param PdfSecurity $security
	 *
	 * @since 1.0
	 */
	public function __construct( Common $image_common, PdfSecurity $security ) {
		$this->image_common = $image_common;
		$this->pdf_security = $security;
	}


	/**
	 * @since 1.0
	 */
	public function init() {
		add_action( 'gfpdf_pre_pdf_generation_initilise', [ $this, 'maybe_display_cached_pdf_image' ], 10, 5 );
		add_action( 'gfpdf_pre_pdf_generation_output', [ $this, 'maybe_generate_image_from_pdf' ], 10, 5 );
	}

	/**
	 * @param \Mpdf\Mpdf $mpdf
	 * @param array      $form
	 * @param array      $entry
	 * @param array      $settings
	 * @param Helper_PDF $helper_pdf
	 *
	 * @throws \ImagickException
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 *
	 * @since 1.0
	 */
	public function maybe_display_cached_pdf_image( $mpdf, $form, $entry, $settings, $helper_pdf ) {
		if ( ! $this->is_pdf_image_url() ) {
			return;
		}

		$image_absolute_path = $this->image_common->get_image_path_from_pdf( $helper_pdf->get_filename(), $form['id'], $entry['id'] );
		$image_name          = basename( $image_absolute_path );

		if ( ! is_file( $image_absolute_path ) ) {
			return;
		}

		list( $subaction ) = $this->get_pdf_image_url_config();

		$image_config = $this->image_common->get_settings( $settings );
		$image_data   = new ImageData( 'image/jpeg', file_get_contents( $image_absolute_path ), $image_name );
		$image        = new Generate( $this->image_common, $helper_pdf->get_full_pdf_path(), $image_config + [ 'skip_validation' => true ] );

		if ( $subaction === 'download' ) {
			$image->to_download( $image_data );
		} else {
			$image->to_screen( $image_data );
		}
	}

	/**
	 * @param \Mpdf\Mpdf $mpdf
	 * @param array      $form
	 * @param array      $entry
	 * @param array      $settings
	 * @param Helper_PDF $helper_pdf
	 *
	 * @throws \ImagickException
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws \Exception
	 *
	 * @since 1.0
	 */
	public function maybe_generate_image_from_pdf( $mpdf, $form, $entry, $settings, $helper_pdf ) {
		if ( ! $this->is_pdf_image_url() ) {
			return;
		}

		/* If no image configured, throw error */
		if ( ! $this->image_common->has_active_image_settings( $settings ) ) {
			wp_die( __( 'This PDF has not been configured to convert to an image.', 'gravity-pdf-to-image' ) );
		}

		/* If PDF password protected, throw error */
		if ( $this->pdf_security->is_password_protected( $settings ) ) {
			wp_die( __( 'Password protected PDFs cannot be converted to images.', 'gravity-pdf-to-image' ) );
		}

		list( $subaction, $page ) = $this->get_pdf_image_url_config();

		$image_config         = $this->image_common->get_settings( $settings );
		$image_config['page'] = $page;
		$image_absolute_path  = $this->image_common->get_image_path_from_pdf( $helper_pdf->get_filename(), $form['id'], $entry['id'] );
		$image_name           = basename( $image_absolute_path );

		$mpdf->encrypted = false;
		$helper_pdf->save_pdf( $mpdf->Output( '', Destination::STRING_RETURN ) );

		/* Save the image to disk for caching purposes, then display to the user */
		$image = new Generate( $this->image_common, $helper_pdf->get_full_pdf_path(), $image_config );
		$image->to_file( $image_absolute_path );
		$image_data = new ImageData( 'image/jpeg', file_get_contents( $image_absolute_path ), $image_name );

		if ( $subaction === 'download' ) {
			$image->to_download( $image_data );
		} else {
			$image->to_screen( $image_data );
		}

		wp_die();
	}

	/**
	 * Check if user is requesting to generate a PDF URL
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function is_pdf_image_url() {
		$action = isset( $GLOBALS['wp']->query_vars['action'] ) ? $GLOBALS['wp']->query_vars['action'] : '';
		return $action === 'img';
	}

	/**
	 * Return the subaction and page data from the query variables (if they exist)
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_pdf_image_url_config() {
		$subaction = isset( $GLOBALS['wp']->query_vars['sub_action'] ) ? $GLOBALS['wp']->query_vars['sub_action'] : '';
		$page      = isset( $GLOBALS['wp']->query_vars['page'] ) ? $GLOBALS['wp']->query_vars['page'] : 0;

		return [
			$subaction,
			$page,
		];
	}
}