<?php

namespace GFPDF\Plugins\PdfToImage\Pdf;

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
 * Class PdfSecurity
 *
 * @package GFPDF\Plugins\PdfToImage\Pdf
 */
class PdfSecurity {

	/**
	 * @param array $settings
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function is_security_enabled( $settings ) {
		return ! isset( $settings['security'] ) || $settings['security'] === 'Yes';
	}

	/**
	 * Check if the PDF is configured to be password protected
	 *
	 * @param array $settings
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function is_password_protected( $settings ) {
		return $settings['security'] === 'Yes' && ! empty( $settings['password'] );
	}

	/**
	 * Check if the current user has this capability
	 *
	 * @param string $capability
	 *
	 * @return bool
	 */
	public function has_capability( $capability ) {
		$gform = \GPDFAPI::get_form_class();
		return $gform->has_capability( $capability );
	}
}