<?php

namespace GFPDF\Plugins\PdfToImage\Options;

use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Trait_Logger;
use GFPDF\Helper\Helper_Misc;

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
 * Class AddPdfToImageFields
 *
 * @package GFPDF\Plugins\PdfToImage\Options
 */
class AddPdfToImageFields {

	/**
	 * @since 1.0
	 */
	use Helper_Trait_Logger;

	/**
	 * @var Helper_Misc
	 *
	 * @since 1.0
	 */
	private $misc;

	/**
	 * @var Helper_Abstract_Options
	 *
	 * @since 1.0
	 */
	private $options;

	/**
	 * AddTextWatermarkFields constructor.
	 *
	 * @param Helper_Misc $misc
	 * @param Helper_Abstract_Options
	 *
	 * @since 1.0
	 */
	public function __construct( Helper_Misc $misc, Helper_Abstract_Options $options ) {
		$this->misc    = $misc;
		$this->options = $options;
	}

	/**
	 * Initialise our module
	 *
	 * @since 1.0
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_assets' ] );
		add_action( 'gfpdf_pdf_to_image_resize_and_crop', [ $this, 'resize_and_crop_callback' ] );

		add_filter( 'gfpdf_form_settings', [ $this, 'add_options' ], 9999 );
		add_filter( 'gfpdf_form_settings_sanitize_hook', [ $this, 'sanitize_resize_and_crop_field' ], 10, 2 );
	}

	/**
	 * Include the Text Watermark settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function add_options( $settings ) {

		$display = apply_filters( 'gfpdf_display_pdf_to_image_options', true, $settings );

		if ( $display ) {

			$pdf_to_image_settings = [
				'pdf_to_image_toggle' => [
					'id'      => 'pdf_to_image_toggle',
					'name'    => esc_html__( 'Generate Image', 'gravity-pdf-to-image' ),
					'desc'    => esc_html__( 'Convert the PDF to an image', 'gravity-pdf-to-image' ),
					'type'    => 'checkbox',
					'tooltip' => '<h6>' . esc_html__( 'Generate Image', 'gravity-pdf-to-image' ) . '</h6>' . esc_html__( 'When enabled, the generated PDF is automatically converted to an image.', 'gravity-pdf-to-image' ),
				],

				'pdf_to_image_page' => [
					'id'      => 'pdf_to_image_page',
					'type'    => 'number',
					'name'    => esc_html__( 'Page', 'gravity-pdf-to-image' ),
					'class'   => 'gfpdf-pdf-to-image',
					'desc'    => esc_html__( 'Generate a specific PDF page as an image. Set to zero to display all pages.', 'gravity-pdf-to-image' ),
					'std'     => 1,
					'size'    => 'small',
					'min'     => -100,
					'tooltip' => '<h6>' . esc_html__( 'Page', 'gravity-pdf-to-image' ) . '</h6>' . esc_html__( 'Use a positive number to specify the PDF page to convert, starting from the beginning of the document. Use a negative to select a page starting from the end of the document. Use zero to display all pages.', 'gravity-pdf-to-image' ),
				],

				'pdf_to_image_resize_and_crop' => [
					'id'      => 'pdf_to_image_resize_and_crop',
					'type'    => 'hook',
					'name'    => esc_html__( 'Constrain Dimensions', 'gravity-pdf-to-image' ),
					'size'    => 'small',
					'class'   => 'gfpdf-pdf-to-image',
					'tooltip' => esc_html__( 'Resize the image proportionally (using a best-fit method) or crop the image to the exact size specified.', 'gravity-pdf-to-image' ),
				],

				'pdf_to_image_dpi' => [
					'id'      => 'pdf_to_image_dpi',
					'type'    => 'number',
					'name'    => esc_html__( 'DPI', 'gravity-pdf-to-image' ),
					'class'   => 'gfpdf-pdf-to-image',
					'std'     => 150,
					'size'    => 'small',
					'tooltip' => '<h6>' . esc_html__( 'DPI', 'gravity-pdf-to-image' ) . '</h6>' . esc_html__( 'A larger DPI will generate higher resolution images, but increase the file size and generation time. A smaller DPI produces a lower resolution image, but has a smaller filesize and is faster to generate.', 'gravity-pdf-to-image' ),
				],

				'pdf_to_image_quality' => [
					'id'      => 'pdf_to_image_quality',
					'type'    => 'number',
					'name'    => esc_html__( 'Image Quality', 'gravity-pdf-to-image' ),
					'class'   => 'gfpdf-pdf-to-image',
					'std'     => 95,
					'desc'    => esc_html__( 'Control the quality of the image using a number between 0-100.', 'gravity-pdf-to-image' ),
					'size'    => 'small',
					'tooltip' => '<h6>' . esc_html__( 'Image Quality', 'gravity-pdf-to-image' ) . '</h6>' . esc_html__( 'Select 100 for the highest quality image, and 0 for the lowest quality image.', 'gravity-pdf-to-image' ),
				],
			];

			$settings += $pdf_to_image_settings;

			$this->logger->notice( 'Add Pdf to Image fields to PDF settings' );
		}

		return $settings;
	}

	/**
	 * Generate mark-up for our custom Resize / Crop field
	 *
	 * @param array $args
	 *
	 * @since 1.0
	 */
	public function resize_and_crop_callback( $args ) {

		/* Treat the element like the paper_size field when getting the values */
		$args['type'] = 'paper_size';
		$size         = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr( $args['size'] ) : 'regular';

		$value  = $this->options->get_form_value( $args );
		$width  = isset( $value[0] ) ? $value[0] : 800;
		$height = isset( $value[1] ) ? $value[1] : 600;
		$crop   = isset( $value[2] ) ? $value[2] : 0;

		ob_start();
		?>

		<input type="number"
		       id="gfpdf_settings[<?= $args['id'] ?>]_width"
		       class="<?= $size ?>-text gfpdf_settings_<?= $args['id'] ?>"
		       name="gfpdf_settings[<?= $args['id'] ?>][]"
		       value="<?= esc_attr( $width ) ?>"
		       min="0"
		/> <?= esc_html__( 'Width', 'gravity-pdf-to-image' ) ?>

		<input type="number"
		       id="gfpdf_settings[<?= $args['id'] ?>]_height"
		       class="<?= $size ?>-text gfpdf_settings_<?= $args['id'] ?>"
		       name="gfpdf_settings[<?= $args['id'] ?>][]"
		       value="<?= esc_attr( $height ) ?>"
		       min="0"
		/> <?= esc_html__( 'Height (px)', 'gravity-pdf-to-image' ) ?>

		&nbsp; — &nbsp;

		<label>
			<input type="checkbox"
			       id="gfpdf_settings[<?= $args['id'] ?>]_crop"
			       name="gfpdf_settings[<?= $args['id'] ?>][]"
				<?php checked( 1, $crop ) ?>
			/> <?= esc_html__( 'Crop to Dimensions', 'gravity-pdf-to-image' ) ?>
		</label>

		<span class="gf_settings_description">
			<label for="gfpdf_settings[<?= esc_attr( $args['id'] ) ?>]"> <?= wp_kses_post( $args['desc'] ) ?></label>
		</span>

		<?php if ( isset( $args['tooltip'] ) ): ?>
			<span class="gf_hidden_tooltip" style="display: none;"><?= wp_kses_post( $args['tooltip'] ) ?></span>
		<?php endif;

		echo ob_get_clean();
	}

	/**
	 * Sanitize the image resize and crop field
	 *
	 * @param mixed  $new_value
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function sanitize_resize_and_crop_field( $new_value, $key ) {
		if ( $key === 'pdf_to_image_resize_and_crop' ) {
			$new_value[0] = abs( (int) $new_value[0] );
			$new_value[1] = abs( (int) $new_value[1] );
			$new_value[2] = isset( $new_value[2] ) ? 1 : 0;
		}

		return $new_value;
	}

	/**
	 * Load the CSS/JS needed on the PDF Settings page
	 *
	 * @since 1.0
	 */
	public function load_admin_assets() {
		if ( $this->misc->is_gfpdf_page() ) {
			$form_id = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : false;
			$pdf_id  = ( isset( $_GET['pid'] ) ) ? $_GET['pid'] : false;

			if ( $form_id !== false && $pdf_id !== false ) {
				$version = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : GFPDF_PDF_TO_IMAGE_VERSION;

				wp_enqueue_script( 'gfpdf_js_pdf_to_image', plugins_url( 'assets/js/pdf-to-image-toggle.js', GFPDF_PDF_TO_IMAGE_FILE ), [ 'jquery' ], $version );

				wp_enqueue_style( 'gfpdf_css_pdf_to_image', plugins_url( 'assets/css/pdf-to-image.css', GFPDF_PDF_TO_IMAGE_FILE ), [], $version );
			}
		}
	}
}
