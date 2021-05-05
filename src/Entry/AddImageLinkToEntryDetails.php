<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\Common;

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
 * Class AddImageLinkToEntryDetails
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AddImageLinkToEntryDetails {
	/**
	 * @var Common
	 */
	protected $image_common;

	/**
	 * AddImageLinkToEntryDetails constructor.
	 *
	 * @param Common $image_common
	 */
	public function __construct( Common $image_common ) {
		$this->image_common = $image_common;
	}

	/**
	 * @since 1.0
	 */
	public function init() {
		add_action( 'gfpdf_entry_detail_post_pdf_links_markup', [ $this, 'add_image_link_to_entry_details' ] );
	}

	/**
	 * If the PDF is configured, add it as an option to the Entry Details page
	 *
	 * @param array $pdf
	 *
	 * @since 1.0
	 */
	public function add_image_link_to_entry_details( $pdf ) {

		if ( $this->image_common->has_active_image_settings( $pdf['settings'] ) ) {
			echo sprintf(
				'<a href="%1$s" class="button" target="_blank">%2$s</a>',
				$this->image_common->get_url( $pdf['settings']['id'], $pdf['entry_id'], $pdf['settings']['pdf_to_image_page'] ),
				__( 'Image', 'gravity-pdf-to-image' )
			);
		}
	}
}
