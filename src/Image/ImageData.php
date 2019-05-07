<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;

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
 * Class ImageData
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class ImageData implements ImageDataInterface {

	/**
	 * @var string
	 * @since 1.0
	 */
	protected $mime;

	/**
	 * @var string
	 * @since 1.0
	 */
	protected $data;

	/**
	 * @var string
	 * @since 1.0
	 */
	protected $filename;

	/**
	 * ImageData constructor.
	 *
	 * @param string $mime     The image mime type
	 * @param string $data     The image binary data
	 * @param string $filename The image filename
	 */
	public function __construct( $mime, $data, $filename ) {
		if ( ! is_string( $mime ) ) {
			throw new PdfToImageInvalidArgument( '$mime must be a string' );
		}

		if ( ! is_string( $data ) ) {
			throw new PdfToImageInvalidArgument( '$mime must be a string' );
		}

		if ( ! is_string( $filename ) ) {
			throw new PdfToImageInvalidArgument( '$mime must be a string' );
		}

		$this->mime     = $mime;
		$this->data     = $data;
		$this->filename = $filename;
	}

	/**
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_mime() {
		return $this->mime;
	}

	/**
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_filename() {
		return $this->filename;
	}
}
