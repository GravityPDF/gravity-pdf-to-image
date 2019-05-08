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

/**
 * Interface ImageDataInterface
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
interface ImageDataInterface {

	/**
	 * @return string The image mime type
	 *
	 * @since 1.0
	 */
	public function get_mime();

	/**
	 * @return string The image binary data
	 *
	 * @since 1.0
	 */
	public function get_data();

	/**
	 * @return string The image filename
	 *
	 * @since 1.0
	 */
	public function get_filename();
}
