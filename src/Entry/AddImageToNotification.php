<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Helper\Helper_Trait_Logger;
use GFPDF\Plugins\PdfToImage\Exception\PdfGenerationAndSave;
use GFPDF\Plugins\PdfToImage\Image\Generate;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use Exception;

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
 * Class AddImageToNotification
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AddImageToNotification {

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
		add_action( 'gfpdf_post_generate_and_save_pdf_notification', [ $this, 'register_pdf_to_convert_to_image' ], 10, 4 );
		add_filter( 'gform_notification', [ $this, 'maybe_attach_files_to_notifications' ], 10000, 3 );
	}

	/**
	 * Register the PDFs that need to be converted to images
	 *
	 * @param array $form         The Gravity Form
	 * @param array $entry        The Gravity Form Entry
	 * @param array $settings     The Gravity PDF Form Setting
	 * @param array $notification A Gravity Forms Notification
	 *
	 * @since 1.0
	 */
	public function register_pdf_to_convert_to_image( $form, $entry, $settings, $notification ) {
		$this->settings = [];
		if ( $this->image_common->has_active_image_settings( $settings ) && ! $this->image_common->is_attachment( 'PDF', $settings ) && ! $this->pdf_security->is_password_protected( $settings ) ) {
			/* Store via the form ID and notification ID so we can verify we're working on the correct notification during `gform_notification` */
			$this->settings[ $form['id'] . ':' . $notification['id'] ] = $settings;

			$this->logger->addNotice( sprintf( 'Registering PDF ID#%1$s for Notification "%2$s" Attachment', $settings['id'], $notification['name'] ) );
		}
	}

	/**
	 * Check if we have a valid PDF Image configuration and are processing the correct notification
	 *
	 * @param array $notification A Gravity Forms Notification
	 * @param array $form         The Gravity Form
	 * @param array $entry        The Gravity Form Entry
	 *
	 * @return array $notification
	 *
	 * @since 1.0
	 */
	public function maybe_attach_files_to_notifications( $notification, $form, $entry ) {
		if ( count( $this->settings ) !== 1 || ! isset( $this->settings[ $form['id'] . ':' . $notification['id'] ] ) ) {
			return $notification;
		}

		try {
			$notification['attachments'] = $this->attach_files_to_notification( $notification['attachments'], $entry );
		} catch ( Exception $e ) {
			$this->logger->addError(
				'Image Generation Error',
				[
					'entry'     => $entry,
					'settings'  => $this->settings,
					'exception' => $e->getMessage(),
				]
			);
		}

		return $notification;
	}

	/**
	 * Handle the Image/PDF Generation and attach to the notification based off the PDF settings
	 *
	 * @param array $attachments Gravity Forms Notification Attachments
	 * @param array $entry       The Gravity Form Entry
	 *
	 * @return array
	 *
	 * @throws \ImagickException
	 * @throws PdfGenerationAndSave
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 *
	 * @since 1.0
	 */
	public function attach_files_to_notification( $attachments, $entry ) {
		$settings = reset( $this->settings );

		list(
			$pdf_absolute_path,
			$image_absolute_path
			) = $this->image_common->get_pdf_and_image_path_details( $entry, $settings );

		/* Image already exists. Skip image generation and attach to notification */
		if ( is_file( $image_absolute_path ) ) {
			$attachments = $this->handle_attachments( $attachments, $image_absolute_path, $pdf_absolute_path );

			$this->logger->addNotice( sprintf( 'Attaching PDF ID#%1$s Cached Image for Notification', $settings['id'] ) );

			return $attachments;
		}

		/* Convert PDF to Image and save to disk */
		$image = new Generate( $this->image_common, $pdf_absolute_path, $this->image_common->get_settings( $settings ) );
		$image->to_file( $image_absolute_path );

		$attachments = $this->handle_attachments( $attachments, $image_absolute_path, $pdf_absolute_path );

		$this->logger->addNotice( sprintf( 'Attaching PDF ID#%1$s Generated Image for Notification', $settings['id'] ) );

		/* Clean-up */
		if ( $this->pdf_security->is_security_enabled( $settings ) ) {
			unlink( $pdf_absolute_path );
		}

		return $attachments;
	}

	/**
	 * Attach the generated image to the notification and remove the PDF (if needed)
	 *
	 * @param array  $attachments         The Notification attachments
	 * @param string $image_absolute_path The path to the generated image
	 * @param string $pdf_absolute_path   The path to the generated PDF
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function handle_attachments( $attachments, $image_absolute_path, $pdf_absolute_path ) {
		$settings      = reset( $this->settings );
		$attachments[] = $image_absolute_path;

		/* Remove PDF if required */
		if ( $this->image_common->is_attachment( 'Image', $settings ) ) {
			$pdf_absolute_path = dirname( $pdf_absolute_path ) . '/' . $this->image_common->get_original_pdf_filename( basename( $pdf_absolute_path ) );
			$attachments       = array_diff( $attachments, [ $pdf_absolute_path ] );
		}

		return $attachments;
	}
}
