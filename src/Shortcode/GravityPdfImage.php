<?php

namespace GFPDF\Plugins\PdfToImage\Shortcode;

use GFPDF\Exceptions\GravityPdfShortcodeEntryIdException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConditionalLogicFailedException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConfigNotFoundException;
use GFPDF\Exceptions\GravityPdfShortcodePdfInactiveException;
use GFPDF\Helper\Helper_Abstract_Pdf_Shortcode;
use GFPDF\Plugins\PdfToImage\Image\ImageConfig;
use GFPDF\Plugins\PdfToImage\Image\ImageUrl;
use GFPDF\Plugins\PdfToImage\Images\Common;
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
 * Class GravityPdfImage
 *
 * @package GFPDF\Plugins\PdfToImage\Shortcode
 */
class GravityPdfImage extends Helper_Abstract_Pdf_Shortcode {

	/**
	 * @since 1.0
	 */
	const SHORTCODE = 'gravitypdfimage';

	/**
	 * @var bool
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
	 * @param $debug
	 */
	public function set_debug_mode( $debug ) {
		$this->debug = (bool) $debug;
	}

	/**
	 * @param Common $image
	 */
	public function set_image( Common $image ) {
		$this->image = $image;
	}

	/**
	 * @param array $attributes
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function process( $attributes ) {

		$controller           = GPDFAPI::get_mvc_class( 'Controller_Shortcode' );
		$has_view_permissions = $this->debug && $this->gform->has_capability( 'gravityforms_view_entries' );

		/* Merge in standard defaults */
		$attributes = shortcode_atts(
			[
				'id'      => '',
				'text'    => 'Download Image',
				'type'    => 'img',
				'signed'  => '1',
				'expires' => '',
				'class'   => 'gravitypdf-image',
				'classes' => '',
				'entry'   => '',
				'raw'     => '',
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
			$empty    = ! empty( $attributes['signed'] );
			$raw      = ! empty( $attributes['raw'] );

			$url = $this->image->get_url( $settings['id'], $entry_id, $image_settings['page'], $download, false );


			if ( $empty ) {
				$attributes['url'] = $this->sign_url( $url, $attributes['expires'] );
			}

			if ( $raw ) {
				return $attributes['url'];
			}

			$attributes['url'] = esc_attr( $attributes['url'] );

			/* Output a raw image, or a view / download link */
			switch ( $type ) {
				case 'img':
					return sprintf( '<img src="%s" />', $attributes['url'] );
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
		} catch ( \Exception $e ) {
			return $has_view_permissions ? $e->getMessage() : '';
		}
	}

	/**
	 * @param int    $entry_id
	 * @param string $pdf_id
	 *
	 * @return array
	 * @throws GravityPdfShortcodePdfConditionalLogicFailedException
	 * @throws GravityPdfShortcodePdfConfigNotFoundException
	 * @throws GravityPdfShortcodePdfInactiveException
	 *
	 * @since 1.0
	 */
	protected function get_pdf_config( $entry_id, $pdf_id ) {
		$settings = parent::get_pdf_config( $entry_id, $pdf_id );

		if ( empty( $settings['pdf_to_image_toggle'] ) ) {
			throw new \Exception( 'image_not_enabled_for_pdf' );
		}

		return $settings;
	}
}
