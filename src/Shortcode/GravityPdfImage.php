<?php

namespace GFPDF\Plugins\PdfToImage\Shortcode;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Sha256_Url_Signer;
use GFPDF\Plugins\PdfToImage\Image\ImageConfig;
use GFPDF\Plugins\PdfToImage\Image\ImageUrl;

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

/* @TODO - REFRACTOR Model_Shortcode so we can extend it */

/**
 * Class GravityPdfImage
 *
 * @package GFPDF\Plugins\PdfToImage
 */
class GravityPdfImage {

	const SHORTCODE = 'gravitypdfimage';

	protected $gform;
	protected $misc;

	public function __construct( Helper_Abstract_Form $gform, Helper_Misc $misc ) {
		$this->gform = $gform;
		$this->misc  = $misc;
	}

	public function init() {
		add_shortcode( self::SHORTCODE, [ $this, 'process' ] );

		add_filter( 'gform_confirmation', [ $this, 'gravitypdf_confirmation' ], 100, 3 );
		add_filter( 'gform_notification', [ $this, 'gravitypdf_notification' ], 100, 3 );

		/* Basic GravityView Support */
		add_filter( 'gravityview/fields/custom/content_before', [ $this, 'gravitypdf_gravityview_custom' ], 10 );
	}

	public function process( $attributes ) {

		/* Merge in standard defaults */
		$attributes = shortcode_atts(
			[
				'id'      => '',
				'text'    => '',
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

		$entry_id = $this->get_entry_id_if_empty( $attributes['entry'] );
		$settings = $this->get_pdf_config( $entry_id, $attributes['id'] );

		$image_settings = ImageConfig::get( $settings );

		$url = ImageUrl::get( $settings['id'], $entry_id, $image_settings['page'], false, false );

		if ( ! empty( $attributes['signed'] ) ) {
			$url = $this->sign_url( $url, $attributes['expires'] );
		}

		return "<img src='$url' />";
	}

	protected function get_entry_id_if_empty( $entry_id ) {
		if ( ! empty( $entry_id ) ) {
			return $entry_id;
		}

		if ( isset( $_GET['lid'], $_GET['entry'] ) ) {
			return isset( $_GET['lid'] ) ? (int) $_GET['lid'] : (int) $_GET['entry'];
		}

		throw new \Exception( 'shortcode_entry_id_not_found' );
	}

	protected function get_pdf_config( $entry_id, $pdf_id ) {
		$entry    = $this->gform->get_entry( $entry_id );
		$settings = ! is_wp_error( $entry ) ? \GPDFAPI::get_pdf( $entry['form_id'], $pdf_id ) : $entry;

		if ( is_wp_error( $settings ) ) {
			throw new \Exception( 'form_pdf_config_not_found' );
		}

		if ( $settings['active'] !== true ) {
			throw new \Exception( 'pdf_is_inactive' );
		}

		if ( isset( $settings['conditionalLogic'] ) && ! $this->misc->evaluate_conditional_logic( $settings['conditionalLogic'], $entry ) ) {
			throw new \Exception( 'pdf_conditional_logic_not_met' );
		}

		if ( empty( $settings['pdf_to_image_toggle'] ) ) {
			throw new \Exception( 'image_not_enabled_for_pdf' );
		}

		return $settings;
	}

	protected function sign_url( $url, $expires ) {
		$secret_key = \GPDFAPI::get_plugin_option( 'signed_secret_token', '' );

		/* If no secret key exists, generate it */
		if ( empty( $secret_key ) ) {
			$secret_key = wp_generate_password( 64 );
			\GPDFAPI::update_plugin_option( 'signed_secret_token', $secret_key );
		}

		$url_signer = new Helper_Sha256_Url_Signer( $secret_key );

		if ( empty( $expires ) ) {
			$expires = intval( \GPDFAPI::get_plugin_option( 'logged_out_timeout', '20' ) ) . ' minutes';
		}

		$date    = new \DateTime();
		$timeout = $date->modify( $expires );

		return $url_signer->sign( $url, $timeout );
	}

	public function gravitypdf_confirmation( $confirmation, $form, $entry ) {

		/* check if confirmation is text-based */
		if ( ! is_array( $confirmation ) ) {
			$confirmation = $this->add_entry_id_to_shortcode( $confirmation, $entry['id'] );
		}

		return $confirmation;
	}

	public function gravitypdf_notification( $notification, $form, $entry ) {

		/* check if notification has a 'message' */
		if ( isset( $notification['message'] ) ) {
			$notification['message'] = $this->add_entry_id_to_shortcode( $notification['message'], $entry['id'] );
		}

		return $notification;
	}

	public function gravitypdf_gravityview_custom( $html ) {
		$gravityview_view = GravityView_View::getInstance();
		$entry            = $gravityview_view->getCurrentEntry();

		return $this->add_entry_id_to_shortcode( $html, $entry['id'] );
	}

	protected function add_entry_id_to_shortcode( $string, $entry_id ) {

		$gravitypdf = $this->get_shortcode_information( 'gravitypdfimage', $string );

		if ( count( $gravitypdf ) > 0 ) {
			foreach ( $gravitypdf as $shortcode ) {
				/* if the user hasn't explicitely defined an entry to display... */
				if ( ! isset( $shortcode['attr']['entry'] ) ) {
					/* get the new shortcode information */
					$new_shortcode = $this->add_shortcode_attr( $shortcode, 'entry', $entry_id );

					/* update our confirmation message */
					$string = str_replace( $shortcode['shortcode'], $new_shortcode['shortcode'], $string );
				}
			}
		}

		return $string;
	}

	public function add_shortcode_attr( $code, $attr, $value ) {

		/* if the attribute doesn't already exist... */
		if ( ! isset( $code['attr'][ $attr ] ) ) {

			$raw_attr = "{$code['attr_raw']} {$attr}=\"{$value}\"";

			/* if there are no attributes at all we'll need to fix our str replace */
			if ( 0 === strlen( $code['attr_raw'] ) ) {
				$pattern           = '^\[([a-zA-Z]+)';
				$code['shortcode'] = preg_replace( "/$pattern/s", "[$1 {$attr}=\"{$value}\"", $code['shortcode'] );
			} else {
				$code['shortcode'] = str_ireplace( $code['attr_raw'], $raw_attr, $code['shortcode'] );
			}

			$code['attr_raw'] = $raw_attr;

		} else { /* replace the current attribute */
			$pattern           = $attr . '="(.+?)"';
			$code['shortcode'] = preg_replace( "/$pattern/si", $attr . '="' . $value . '"', $code['shortcode'] );
			$code['attr_raw']  = preg_replace( "/$pattern/si", $attr . '="' . $value . '"', $code['attr_raw'] );
		}

		/* Update the actual attribute */
		$code['attr'][ $attr ] = $value;

		return $code;
	}

	public function get_shortcode_information( $shortcode, $text ) {
		$shortcodes = [];

		if ( has_shortcode( $text, $shortcode ) ) {
			/* our shortcode exists so parse the shortcode data and return an easy-to-use array */
			preg_match_all( '/' . get_shortcode_regex( [ $shortcode ] ) . '/', $text, $matches, PREG_SET_ORDER );

			if ( empty( $matches ) ) {
				return $shortcodes;
			}

			foreach ( $matches as $item ) {
				if ( $shortcode === $item[2] ) {
					$attr = shortcode_parse_atts( $item[3] );

					$shortcodes[] = [
						'shortcode' => $item[0],
						'attr_raw'  => $item[3],
						'attr'      => ( is_array( $attr ) ) ? $attr : [],
					];
				}
			}
		}

		return $shortcodes;
	}

}