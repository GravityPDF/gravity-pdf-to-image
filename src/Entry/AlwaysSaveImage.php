<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Helper\Helper_Trait_Logger;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Image\Generate;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use Exception;

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
 * Class AlwaysSaveImage
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AlwaysSaveImage {

	use Helper_Trait_Logger;

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
		/* Add a listener before Gravity PDF's Model_PDF::maybe_save_pdf() */
		add_action( 'gform_after_submission', [ $this, 'add_listener' ], 9 );
		add_action( 'gform_after_submission', [ $this, 'remove_listener' ], 11 );
	}

	/**
	 * If the PDF is saved to disk, save the image to disk as well
	 *
	 * @since 1.0
	 */
	public function add_listener() {
		add_action( 'gfpdf_post_save_pdf', [ $this, 'maybe_save_image' ], 10, 5 );
	}

	/**
	 * @since 1.0
	 */
	public function remove_listener() {
		remove_action( 'gfpdf_post_save_pdf', [ $this, 'maybe_save_image' ] );
	}

	/**
	 * Save PDF to disk during the form submission process
	 *
	 * @param $pdf_path
	 * @param $filename
	 * @param $settings
	 * @param $entry
	 * @param $form
	 *
	 * @since 1.0
	 */
	public function maybe_save_image( $pdf_path, $filename, $settings, $entry, $form ) {
		if ( ! $this->image_common->has_active_image_settings( $settings ) || $this->pdf_security->is_password_protected( $settings ) ) {
			return;
		}

		try {
			list(
				$pdf_absolute_path,
				$image_absolute_path
				) = $this->image_common->get_pdf_and_image_path_details( $entry, $settings );

			$image = new Generate( $this->image_common, $pdf_absolute_path, $this->image_common->get_settings( $settings ) );
			$image->to_file( $image_absolute_path );

			do_action( 'gfpdf_gravitypdfimage_post_save_image', $image_absolute_path, basename( $image_absolute_path ), $settings, $entry, $form );
			do_action( 'gfpdf_gravitypdfimage_post_save_image_' . $form['id'], $image_absolute_path, basename( $image_absolute_path ), $settings, $entry, $form );

		} catch ( Exception $e ) {
			$this->logger->error(
				'Image Generation Error',
				[
					'entry'     => $entry,
					'settings'  => $settings,
					'exception' => $e->getMessage(),
				]
			);
		}
	}
}
