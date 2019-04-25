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
 * Class ImageConfig
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class ImageConfig {

	/**
	 * Return the image configuration from the PDF settings
	 *
	 * @param array $settings A form's PDF setting
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public static function get( $settings ) {
		$page    = isset( $settings['pdf_to_image_page'] ) ? $settings['pdf_to_image_page'] : 1;
		$dpi     = isset( $settings['pdf_to_image_dpi'] ) ? $settings['pdf_to_image_dpi'] : 150;
		$quality = isset( $settings['pdf_to_image_quality'] ) ? $settings['pdf_to_image_quality'] : 95;
		$width   = isset( $settings['pdf_to_image_resize_and_crop'][0] ) ? $settings['pdf_to_image_resize_and_crop'][0] : 800;
		$height  = isset( $settings['pdf_to_image_resize_and_crop'][1] ) ? $settings['pdf_to_image_resize_and_crop'][1] : 600;
		$crop    = ! empty( $settings['pdf_to_image_resize_and_crop'][2] ) ? true : false;

		return [
			'page' => $page,
			'dpi' => $dpi,
			'quality' => $quality,
			'width' => $width,
			'height' => $height,
			'crop' => $crop,
		];
	}
}