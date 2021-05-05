<?php

namespace GFPDF\Plugins\PdfToImage\Shortcode;

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
 * Class AddImageShortcodeToPdfList
 *
 * @package GFPDF\Plugins\PdfToImage\Shortcode
 */
class AddImageShortcodeToPdfList {

	/**
	 * @var Common
	 * @since 1.0
	 */
	protected $image_common;

	/**
	 * AddImageShortcodeToPdfList constructor.
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
		add_action( 'gfpdf_post_pdf_list_shortcode_column', [ $this, 'add_image_shortcode_to_pdf_list' ] );
	}

	/**
	 * Display a read-only version of the Gravity PDF Image Shortcode for easy copy/paste
	 *
	 * @param array $settings
	 *
	 * @since 1.0
	 */
	public function add_image_shortcode_to_pdf_list( $settings ) {
		if ( $this->image_common->has_active_image_settings( $settings ) ) {
			$shortcode = sprintf(
				'[gravitypdfimage name="%s" id="%s"]',
				esc_attr( str_replace( '"', '', $settings['name'] ) ),
				esc_attr( $settings['id'] )
			);

			echo sprintf(
				'<input type="text" class="gravitypdf_shortcode" value="%s" readonly="readonly" onfocus="jQuery(this).select();" onclick="jQuery(this).select();" />',
				esc_attr( $shortcode )
			);
		}
	}
}
