<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\Generate;
use GFPDF\Plugins\PdfToImage\Image\ImageConfig;
use GFPDF\Plugins\PdfToImage\Pdf\PdfInfo;

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
 * Class AddImageToNotification
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AddImageToNotification {

	protected $tmp_path;

	protected $settings = [];

	public function __construct( $tmp_path ) {
		$this->tmp_path = $tmp_path;
	}

	public function init() {
		add_action( 'gfpdf_post_generate_and_save_pdf_notification', [ $this, 'register_pdf_to_convert_to_image' ], 10, 4 );
		add_filter( 'gform_notification', [ $this, 'maybe_attach_files_to_notifications', ], 10000, 3 );
	}

	/**
	 * Register the PDFs that need to be converted to images
	 *
	 * @param $form
	 * @param $entry
	 * @param $settings
	 * @param $notification
	 */
	public function register_pdf_to_convert_to_image( $form, $entry, $settings, $notification ) {
		$this->settings = [];
		if ( ! empty( $settings['pdf_to_image_toggle'] ) && $settings['pdf_to_image_notifications'] !== 'PDF' ) {
			/* Store via the form ID and notification ID so we can verify we're working on the correct notification during `gform_notification` */
			$this->settings[ $form['id'] . ':' . $notification['id'] ] = $settings;
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
	 * @throws \ImagickException
	 *
	 * @since 1.0
	 */
	public function maybe_attach_files_to_notifications( $notification, $form, $entry ) {
		if ( count( $this->settings ) !== 1 || ! isset( $this->settings[ $form['id'] . ':' . $notification['id'] ] ) ) {
			return $notification;
		}

		$notification['attachments'] = $this->attach_files_to_notification( $notification['attachments'], $entry );

		return $notification;
	}

	/**
	 * Handle the Image/PDF Generation and attach to the notification based off the PDF settings
	 *
	 * @param array $attachments
	 * @param array $entry
	 *
	 * @return array
	 * @throws \ImagickException
	 *
	 * @since 1.0
	 */
	public function attach_files_to_notification( $attachments, $entry ) {
		$settings = reset( $this->settings );

		list(
			$pdf_absolute_path,
			$image_tmp_directory,
			$image_absolute_path
			) = $this->get_pdf_and_image_path_details( $entry, $settings );

		/* Image already exists. Skip image generation and attach to notification */
		if ( is_file( $image_absolute_path ) ) {
			$attachments = $this->handle_attachments( $attachments, $image_absolute_path, $pdf_absolute_path );

			return $attachments;
		}

		/* @TODO if PDF doesnt exist, or password protected regenerate */
		if ( ! is_file( $pdf_absolute_path ) ) {
			return $attachments;
		}

		/* Convert PDF to Image and save to disk */
		$image = new Generate( $pdf_absolute_path, ImageConfig::get( $settings ) );
		$image->to_file( $image_tmp_directory . $image->get_image_name() );

		$attachments = $this->handle_attachments( $attachments, $image_absolute_path, $pdf_absolute_path );

		return $attachments;
	}

	/**
	 * Get the path details for the required files
	 *
	 * @param array $entry    The Gravity Form Entry
	 * @param array $settings The Gravity PDF Form setting
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function get_pdf_and_image_path_details( $entry, $settings ) {
		$pdf_info   = new PdfInfo( $entry, $settings );
		$image_info = new Generate( $pdf_info->get_absolute_path(), ImageConfig::get( $settings ) );

		$pdf_absolute_path   = $pdf_info->get_absolute_path();
		$image_tmp_directory = $this->tmp_path . $entry['form_id'] . $entry['id'] . '/';
		$image_absolute_path = $image_tmp_directory . $image_info->get_image_name();

		return [
			$pdf_absolute_path,
			$image_tmp_directory,
			$image_absolute_path,
		];
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
		if ( $settings['pdf_to_image_notifications'] === 'Image' ) {
			$attachments = array_diff( $attachments, [ $pdf_absolute_path ] );
		}

		return $attachments;
	}
}