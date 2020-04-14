<?php

namespace GFPDF\Plugins\PdfToImage\Image;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FlushCache
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class FlushCache {

	/**
	 * @var Common
	 * @since 1.0
	 */
	protected $common;

	/**
	 * FlushCache constructor.
	 *
	 * @param Common $common
	 */
	public function __construct( Common $common ) {
		$this->common = $common;
	}

	/**
	 * @since 1.0
	 */
	public function init() {
		add_filter( 'gfpdf_form_update_pdf', [ $this, 'flush_pdf_image_cache' ], 10, 2 );
		add_action( 'gform_after_update_entry', [ $this, 'gform_after_update_entry' ], 10, 2 );
		add_action( 'gform_post_update_entry', [ $this, 'gform_post_update_entry' ], 10, 2 );
	}

	/**
	 * Flush the image disk cache for the current form PDF
	 *
	 * @param array $pdf
	 * @param int   $form_id
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function flush_pdf_image_cache( $pdf, $form_id ) {

		/* Get the image cache path, striping off the filename, entry and page directories */
		$pdf_image_cache_path = rtrim( $this->common->get_image_path_from_pdf( '', $form_id, $pdf['id'], 0, 0 ), '/0/0/.jpg' );
		$this->delete_folder( $pdf_image_cache_path );

		return $pdf;
	}

	/**
	 * Proxy to flush_entry_image_cache
	 *
	 * @param array $form
	 * @param int   $entry_id
	 *
	 * @since 1.0
	 */
	public function gform_after_update_entry( $form, $entry_id ) {
		$this->flush_entry_image_cache( $form['id'], $entry_id );
	}

	/**
	 * Proxy to flush_entry_image_cache
	 *
	 * @param $entry
	 *
	 * @since 1.0
	 */
	public function gform_post_update_entry( $entry ) {
		$this->flush_entry_image_cache( $entry['form_id'], $entry['id'] );
	}

	/**
	 * Flush the image disk cache for the current form entry
	 *
	 * @param array $form_id
	 * @param int   $entry_id
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function flush_entry_image_cache( $form_id, $entry_id ) {
		$pdfs = \GPDFAPI::get_form_pdfs( $form_id );

		if ( is_wp_error( $pdfs ) ) {
			return;
		}

		foreach ( $pdfs as $pdf ) {
			$this->delete_folder( $this->common->get_tmp_image_directory( $form_id, $pdf['id'], $entry_id ) );
		}
	}

	protected function delete_folder( $path ) {
		$misc = \GPDFAPI::get_misc_class();

		if ( is_dir( $path ) ) {
			$misc->rmdir( $path );
		}
	}
}
