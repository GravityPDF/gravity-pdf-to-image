<?php

namespace GFPDF\Plugins\PdfToImage\Pdf;

use GFPDF\Helper\Helper_PDF;
use GFPDF\Model\Model_PDF;
use GPDFAPI;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	public function get_full_pdf_path() {
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

	/**
	 * Save the PDF to our tmp directory
	 *
	 * @param string $string
	 *
	 * @return string
	 *
	 * @throws \Exception
	 *
	 * @since  1.0
	 */
	public function save_pdf( $string ) {
		return $this->generator->save_pdf( $string );
	}
}
