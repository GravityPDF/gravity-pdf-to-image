<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Helper_Trait_Logger;
use GFPDF\Plugins\PdfToImage\Exception\PdfToImage;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use Mpdf\Output\Destination;
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
 * Class Listener
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class Listener {

	use Helper_Trait_Logger;

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
	 * Load the cached image (if exists)
	 *
	 * @param \Mpdf\Mpdf $mpdf
	 * @param array      $form
	 * @param array      $entry
	 * @param array      $settings
	 * @param Helper_PDF $helper_pdf
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

		try {
			$image_config = $this->image_common->get_settings( $settings );
			$image_data   = new ImageData( 'image/jpeg', file_get_contents( $image_absolute_path ), $image_name );
			$image        = new Generate( $this->image_common, $helper_pdf->get_full_pdf_path(), $image_config + [ 'skip_validation' => true ] );

			$this->logger->addNotice( sprintf( 'Displaying PDF ID#%1$s Cached Image', $settings['id'] ) );

			if ( $subaction === 'download' ) {
				$image->to_download( $image_data );
			} else {
				$image->to_screen( $image_data );
			}
		} catch ( Exception $e ) {
			$this->handle_error( $e );
		}
	}

	/**
	 * Generate the current PDF, then convert it to an image and display
	 *
	 * @param \Mpdf\Mpdf $mpdf
	 * @param array      $form
	 * @param array      $entry
	 * @param array      $settings
	 * @param Helper_PDF $helper_pdf
	 *
	 * @since 1.0
	 */
	public function maybe_generate_image_from_pdf( $mpdf, $form, $entry, $settings, $helper_pdf ) {
		if ( ! $this->is_pdf_image_url() ) {
			return;
		}

		try {
			/* If no image configured, throw error */
			if ( ! $this->image_common->has_active_image_settings( $settings ) ) {
				throw new PdfToImage( esc_html__( 'This PDF has not been configured to convert to an image.', 'gravity-pdf-to-image' ) );
			}

			/* If PDF password protected, throw error */
			if ( $this->pdf_security->is_password_protected( $settings ) ) {
				throw new PdfToImage( esc_html__( 'Password protected PDFs cannot be converted to images.', 'gravity-pdf-to-image' ) );
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
		} catch ( Exception $e ) {
			$this->handle_error( $e );
		}
	}

	/**
	 * Log exception and display error to user
	 *
	 * @param Exception $exception
	 *
	 * @since 1.0
	 */
	protected function handle_error( $exception ) {
		$this->logger->addError(
			'Image Generation Error',
			[
				'exception_msg'  => $exception->getMessage(),
				'exception_file' => $exception->getFile(),
				'exception_line' => $exception->getLine(),
			]
		);

		if ( $this->pdf_security->has_capability( 'gravityforms_view_entries' ) ) {
			wp_die(
				sprintf(
					'%s in %s on line %s',
					$exception->getMessage(),
					$exception->getFile(),
					$exception->getLine()
				)
			);
		}

		wp_die( esc_html__( 'There was a problem generating your image.', 'gravity-pdf-to-image' ) );
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
