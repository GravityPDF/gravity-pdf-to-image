<?php

namespace GFPDF\Plugins\PdfToImage\Shortcode;

use GFPDF\Exceptions\GravityPdfShortcodeEntryIdException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConditionalLogicFailedException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConfigNotFoundException;
use GFPDF\Exceptions\GravityPdfShortcodePdfInactiveException;
use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;
use GFPDF\Helper\Helper_Abstract_Pdf_Shortcode;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GPDFAPI;
use Exception;

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
 * Class GravityPdfImage
 *
 * @package GFPDF\Plugins\PdfToImage\Shortcode
 */
class GravityPdfImage extends Helper_Abstract_Pdf_Shortcode {

	/**
	 * @since 1.0 The shortcode ID / name
	 */
	const SHORTCODE = 'gravitypdfimage';

	/**
	 * @var bool Whether debug mode is enabled
	 */
	protected $debug;

	/**
	 * @var Common
	 */
	protected $image;

	/**
	 * @since 1.0
	 */
	public function init() {
		add_shortcode( self::SHORTCODE, [ $this, 'process' ] );

		add_filter( 'gform_confirmation', [ $this, 'gravitypdf_confirmation' ], 100, 3 );
		add_filter( 'gform_notification', [ $this, 'gravitypdf_notification' ], 100, 3 );

		/* Basic GravityView Support */
		add_filter( 'gravityview/fields/custom/content_before', [ $this, 'gravitypdf_gravityview_custom' ], 10 );
	}


	/**
	 * @param bool $debug
	 *
	 * @since 1.0
	 */
	public function set_debug_mode( $debug ) {
		$this->debug = (bool) $debug;
	}

	/**
	 * @param Common $image
	 *
	 * @since 1.0
	 */
	public function set_image( Common $image ) {
		$this->image = $image;
	}

	/**
	 * Handle the rendering process for this shortcode
	 *
	 * @param array $attributes
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function process( $attributes ) {

		$controller           = GPDFAPI::get_mvc_class( 'Controller_Shortcodes' );
		$has_view_permissions = $this->debug && $this->gform->has_capability( 'gravityforms_view_entries' );

		/* Merge in standard defaults */
		$attributes = shortcode_atts(
			[
				'id'      => '',
				'text'    => __( 'Download Image', 'gravity-pdf-to-image' ),
				'alt'     => '',
				'type'    => 'img',
				'signed'  => '1',
				'expires' => '',
				'class'   => 'gravitypdf-image',
				'classes' => '',
				'entry'   => '',
				'raw'     => '',
				'width'   => '',
				'height'  => '',
			],
			$attributes,
			self::SHORTCODE
		);

		$attributes = apply_filters( 'gfpdf_gravitypdfimage_shortcode_attributes', $attributes );

		try {
			$entry_id       = $this->get_entry_id_if_empty( $attributes['entry'] );
			$settings       = $this->get_pdf_config( $entry_id, $attributes['id'] );
			$image_settings = $this->image->get_settings( $settings );

			$type     = $attributes['type'];
			$download = $attributes['type'] === 'download';
			$signed   = ! empty( $attributes['signed'] );
			$raw      = ! empty( $attributes['raw'] );

			$attributes['url'] = $this->image->get_url( $settings['id'], $entry_id, $image_settings['page'], $download, false );

			if ( $signed ) {
				$attributes['url'] = $this->url_signer->sign( $attributes['url'], $attributes['expires'] );
			}

			if ( $raw ) {
				return $attributes['url'];
			}

			$attributes['url'] = esc_attr( $attributes['url'] );

			/* Output a raw image, or a view / download link */
			switch ( $type ) {
				case 'img':
					return sprintf(
						'<img src="%1$s" width="%2$s" height="%3$s" alt="%4$s" />',
						$attributes['url'],
						$attributes['width'],
						$attributes['height'],
						$attributes['alt']
					);
				break;

				default:
					return $controller->view->display_gravitypdf_shortcode( $attributes );
				break;
			}
		} catch ( GravityPdfShortcodeEntryIdException $e ) {
			return $has_view_permissions ? $controller->view->no_entry_id() : '';
		} catch ( GravityPdfShortcodePdfConfigNotFoundException $e ) {
			return $has_view_permissions ? $controller->view->invalid_pdf_config() : '';
		} catch ( GravityPdfShortcodePdfInactiveException $e ) {
			return $has_view_permissions ? $controller->view->pdf_not_active() : '';
		} catch ( GravityPdfShortcodePdfConditionalLogicFailedException $e ) {
			return $has_view_permissions ? $controller->view->conditional_logic_not_met() : '';
		} catch ( Exception $e ) {
			return $has_view_permissions ? $e->getMessage() : '';
		}
	}

	/**
	 * Get the PDF configuration
	 *
	 * @param int    $entry_id The Gravity Forms Entry ID
	 * @param string $pdf_id   The Gravity PDF Form Setting ID
	 *
	 * @return array
	 *
	 * @throws GravityPdfShortcodePdfConditionalLogicFailedException
	 * @throws GravityPdfShortcodePdfConfigNotFoundException
	 * @throws GravityPdfShortcodePdfInactiveException
	 * @throws PdfToImageInvalidArgument
	 *
	 * @since 1.0
	 */
	protected function get_pdf_config( $entry_id, $pdf_id ) {
		$settings = parent::get_pdf_config( $entry_id, $pdf_id );

		if ( empty( $settings['pdf_to_image_toggle'] ) ) {
			throw new PdfToImageInvalidArgument( esc_html__( 'This PDF has not been configured to convert to an image.', 'gravity-pdf-to-image' ) );
		}

		return $settings;
	}
}
