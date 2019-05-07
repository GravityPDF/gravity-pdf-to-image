<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\Common;
use GFForms;

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
 * Class AddImageLinkToEntryList
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AddImageLinkToEntryList {

	/**
	 * @var Common
	 */
	protected $image_common;

	/**
	 * AddImageLinkToEntryList constructor.
	 *
	 * @param Common $image_common
	 */
	public function __construct( Common $image_common ) {
		$this->image_common = $image_common;
	}

	/**
	 * Only run on the Gravity Forms Entry List Admin Page
	 *
	 * @since 1.0
	 */
	public function init() {

		if ( GFForms::get_page() !== 'entry_list' ) {
			return;
		}

		add_filter( 'gfpdf_get_pdf_display_list', [ $this, 'add_image_link_to_entry_list' ] );
	}

	/**
	 * Add PDF Image links to the Entry Details page
	 *
	 * @param array $list A list of PDFs available to the current entry
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function add_image_link_to_entry_list( $list ) {
		$new_list = [];
		foreach ( $list as $pdf ) {

			$new_list[] = $pdf;

			if ( $this->image_common->has_active_image_settings( $pdf['settings'] ) ) {
				$pdf['name']     = sprintf( __( 'Image: %s', 'gravity-pdf-to-image' ), $pdf['name'] );
				$pdf['view']     = $this->image_common->get_url( $pdf['settings']['id'], $pdf['entry_id'], $pdf['settings']['pdf_to_image_page'] );
				$pdf['download'] = $this->image_common->get_url( $pdf['settings']['id'], $pdf['entry_id'], $pdf['settings']['pdf_to_image_page'], true );

				$new_list[] = $pdf;
			}
		}

		return $new_list;
	}
}