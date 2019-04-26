<?php

namespace GFPDF\Plugins\PdfToImage\Pdf;

use GFPDF\Helper\Helper_PDF;
use GFPDF\Model\Model_PDF;
use GPDFAPI;

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
 * Class PdfWrapper
 *
 * @package GFPDF\Plugins\PdfToImage\Pdf
 */
class PdfWrapper {

	/**
	 * @var Helper_PDF
	 *
	 * @since 1.0
	 */
	protected $generator;

	/**
	 * PdfInfo constructor.
	 *
	 * @param array $entry The Gravity Forms Entry
	 * @param array $pdf   The PDF settings
	 *
	 * @since 1.0
	 */
	public function __construct( $entry, $pdf ) {
		/** @var Model_PDF $pdf_model */
		$pdf_model = GPDFAPI::get_mvc_class( 'Model_PDF' );

		$this->generator = new Helper_PDF(
			$entry,
			$pdf,
			GPDFAPI::get_form_class(),
			GPDFAPI::get_data_class(),
			GPDFAPI::get_misc_class(),
			GPDFAPI::get_templates_class(),
			GPDFAPI::get_log_class()
		);

		$this->generator->set_filename( $pdf_model->get_pdf_name( $pdf, $entry ) );
	}

	/**
	 * Save the PDF to disk
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function generate() {
		/** @var Model_PDF $pdf_model */
		$pdf_model = GPDFAPI::get_mvc_class( 'Model_PDF' );

		return $pdf_model->process_and_save_pdf( $this->generator );
	}

	/**
	 * Get the PDF Filename
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_filename() {
		return $this->generator->get_filename();
	}

	/**
	 * Set the PDF filename
	 *
	 * @param string $filename
	 *
	 * @since 1.0
	 */
	public function set_filename( $filename ) {
		$this->generator->set_filename( $filename );
	}

	/**
	 * Get the absolute path to save the PDF
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_absolute_path() {
		return $this->generator->get_full_pdf_path();
	}

	/**
	 * Get the PDF path
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_path() {
		return $this->generator->get_path();
	}
}