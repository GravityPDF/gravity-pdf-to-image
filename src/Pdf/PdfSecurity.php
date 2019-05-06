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
 * Class PdfSecurity
 *
 * @package GFPDF\Plugins\PdfToImage\Pdf
 */
class PdfSecurity {

	/**
	 * @param array $settings
	 *
	 * @return bool
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
	 */
	public function is_password_protected( $settings ) {
		return $settings['security'] === 'Yes' && ! empty( $settings['password'] );
	}
}