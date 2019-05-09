<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfGenerationAndSave;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use GFPDF\Plugins\PdfToImage\Pdf\PdfWrapper;
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

/**
 * Class Common
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class Common {

	/**
	 * @var PdfSecurity
	 *
	 * @since 1.0
	 */
	protected $pdf_security;

	/**
	 * @var string
	 *
	 * @since 1.0
	 */
	protected $tmp_path;

	/**
	 * Common constructor.
	 *
	 * @param PdfSecurity $pdf_security
	 * @param string      $tmp_path
	 */
	public function __construct( PdfSecurity $pdf_security, $tmp_path ) {
		$this->pdf_security = $pdf_security;
		$this->tmp_path     = $tmp_path;
	}

	/**
	 * Check if the current PDF settings have been configured with image support
	 *
	 * @param array $settings A form's PDF setting
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function has_active_image_settings( $settings ) {
		return ! empty( $settings['pdf_to_image_toggle'] );
	}

	/**
	 * Check if specific Notification setting is selected
	 *
	 * @param string $type     The setting value to check
	 * @param array  $settings A form's PDF setting
	 *
	 * @return bool
	 */
	public function is_attachment( $type, $settings ) {
		if ( ! isset( $settings['pdf_to_image_notifications'] ) ) {
			return false;
		}

		return $settings['pdf_to_image_notifications'] === $type;
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
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );
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
	 *
	 * @since 1.0
	 */
	public function get_name_from_pdf( $file ) {
		return sprintf( '%s.jpg', basename( $file, '.pdf' ) );
	}

	/**
	 * @param string $file
	 * @param int    $form_id
	 * @param int    $entry_id
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_image_path_from_pdf( $file, $form_id, $entry_id ) {
		$image_tmp_directory = $this->tmp_path . $form_id . $entry_id . '/';
		$image_name          = $this->get_name_from_pdf( $file );

		return $image_tmp_directory . $image_name;
	}

	/**
	 * Get the path details for the required files
	 *
	 * @param array $entry    The Gravity Form Entry
	 * @param array $settings The Gravity PDF Form setting
	 *
	 * @return array
	 *
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws PdfGenerationAndSave
	 *
	 * @since 1.0
	 */
	public function get_pdf_and_image_path_details( $entry, $settings ) {
		$pdf        = $this->maybe_generate_tmp_pdf( $entry, $settings );
		$image_info = new Generate( $this, $pdf->get_full_pdf_path(), $this->get_settings( $settings ) );

		/* If we had to generate a tmp PDF, reset the image name back to the original */
		if ( $this->pdf_security->is_security_enabled( $settings ) ) {
			$image_info->set_image_name( $this->get_original_pdf_filename( $pdf->get_filename() ) );
		}

		$pdf_absolute_path   = $pdf->get_full_pdf_path();
		$image_absolute_path = $this->get_image_path_from_pdf( $pdf_absolute_path, $entry['form_id'], $entry['id'] );
		$image_tmp_directory = dirname( $image_absolute_path );

		return [
			$pdf_absolute_path,
			$image_absolute_path,
			$image_tmp_directory,
		];
	}

	/**
	 * Check if we need to generate a tmp PDF with security disabled, then generate
	 *
	 * @param array $entry
	 * @param array $settings
	 *
	 * @return PdfWrapper Return a valid Pdf object
	 *
	 * @throws PdfGenerationAndSave
	 *
	 * @since 1.0
	 */
	public function maybe_generate_tmp_pdf( $entry, $settings ) {
		$does_pdf_have_security_enabled = $this->pdf_security->is_security_enabled( $settings );

		if ( $does_pdf_have_security_enabled ) {
			$settings['security'] = 'No';
		}

		$pdf = new PdfWrapper( $entry, $settings );

		/* We need to regenerate the PDF, so adjust the filename to not override the original PDF */
		if ( $does_pdf_have_security_enabled ) {
			$pdf->set_filename( $this->get_tmp_pdf_filename( $pdf->get_filename() ) );
		}

		/* If the PDF doesn't exist, generate */
		if ( ! is_file( $pdf->get_full_pdf_path() ) && ! $pdf->generate() ) {
			throw new PdfGenerationAndSave( 'Could not generate PDF for image conversion' );
		}

		return $pdf;
	}

	/**
	 * Return the original filename
	 *
	 * @param string $tmp_filename
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_original_pdf_filename( $tmp_filename ) {
		$position = strpos( $tmp_filename, '@@' );
		if ( $position === false ) {
			return $tmp_filename;
		}

		return substr( $tmp_filename, $position + 2 );
	}

	/**
	 * Return a tmp PDF filename
	 *
	 * @param string $filename
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_tmp_pdf_filename( $filename ) {
		return time() . '@@' . $filename;
	}
}
