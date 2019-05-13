<?php

namespace GFPDF\Plugins\PdfToImage\Image;

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
		add_filter( 'gfpdf_form_update_pdf', [ $this, 'flush_image_cache' ], 10, 2 );
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
	public function flush_image_cache( $pdf, $form_id ) {
		$misc = \GPDFAPI::get_misc_class();

		/* Get the image cache path, striping off the filename, entry and page directories */
		$pdf_image_cache_path = rtrim( $this->common->get_image_path_from_pdf( '', $form_id, $pdf['id'], 0, 0 ), '/0/0/.jpg' );
		if ( is_dir( $pdf_image_cache_path ) ) {
			$misc->rmdir( $pdf_image_cache_path );
		}

		return $pdf;
	}
}
