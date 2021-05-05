<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;

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
