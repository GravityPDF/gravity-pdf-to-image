<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Helper\Helper_Trait_Logger;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;

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

/**
 * Class AlwaysSaveImage
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AlwaysSaveImage {

	use Helper_Trait_Logger;

	/**
	 * @var array The Gravity PDF Form setting to process
	 *
	 * @since 1.0
	 */
	protected $settings = [];

	/**
	 * @var Common
	 *
	 * @since 1.0
	 */
	protected $image_common;

	/**
	 * @var PdfSecurity
	 *
	 * @since 1.0
	 */
	protected $pdf_security;

	/**
	 * AddImageToNotification constructor.
	 *
	 * @param Common      $image_common
	 * @param PdfSecurity $pdf_security
	 *
	 * @since 1.0
	 */
	public function __construct( Common $image_common, PdfSecurity $pdf_security ) {
		$this->pdf_security = $pdf_security;
		$this->image_common = $image_common;
	}

	/**
	 * @since 1.0
	 */
	public function init() {
		add_action( 'gform_after_submission', [ $this, 'add_listener' ], 9 );
		add_action( 'gform_after_submission', [ $this, 'remove_listener' ], 11 );
	}

	public function add_listener() {
		add_action( 'gfpdf_post_save_pdf', [ $this, 'maybe_save_image' ], 10, 5 );
	}

	public function remove_listener() {
		remove_action( 'gfpdf_post_save_pdf', [ $this, 'maybe_save_image' ] );
	}

	public function maybe_save_image( $pdf_path, $filename, $settings, $entry, $form ) {
		if ( ! $this->image_common->has_active_image_settings( $settings ) || $this->pdf_security->is_password_protected( $settings ) ) {
			return;
		}

		/*
		 * Need to abstract out:
		 *
		 * AddImageToNotification::get_pdf_and_image_path_details
		 * AddImageToNotification::maybe_generate_tmp_pdf
		 * AddImageToNotification::get_tmp_pdf_filename
		 * AddImageToNotification::get_original_pdf_filename
		 *
		 * Then extend this class
		 */

		$image_absolute_path = '';

		do_action( 'gfpdf_gravitypdfimage_post_save_image', $image_absolute_path, basename($image_absolute_path), $settings, $entry, $form );
		do_action( 'gfpdf_gravitypdfimage_post_save_image_' . $form['id'], $image_absolute_path, basename($image_absolute_path), $settings, $entry, $form );
	}
}
