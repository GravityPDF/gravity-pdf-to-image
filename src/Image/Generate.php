<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use Mpdf\Mpdf;
use Imagick;

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
 * Class Generate
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class Generate {

	/**
	 * @var string Path to PDF
	 */
	protected $file;

	/**
	 * @var int The PDF page to load
	 */
	protected $page;

	/**
	 * @var int the PDF DPI
	 */
	protected $dpi;

	/**
	 * @var int The image quality
	 */
	protected $quality;

	/**
	 * @var int The image width constraint
	 */
	protected $width;

	/**
	 * @var int The image height constraint
	 */
	protected $height;

	/**
	 * @var bool Whether to crop the image to the width/height, or resize with a best fit
	 */
	protected $crop;

	public function __construct( $file, $page, $dpi = 128, $quality = 100, $width = 0, $height = 0, $crop = false ) {
		$this->file    = $file;
		$this->page    = $page;
		$this->dpi     = abs( (int) $dpi );
		$this->quality = abs( (int) $quality );
		$this->width   = abs( (int) $width );
		$this->height  = abs( (int) $height );
		$this->crop    = (bool) $crop;

		$this->check_if_valid_page();
	}

	/**
	 * Verify the page to display is a valid number and exists
	 *
	 * @since 1.0
	 */
	protected function check_if_valid_page() {
		if ( ! is_int( $this->page ) ) {
			throw new \Exception( '$page must be an integer' );
		}

		/* Read the PDF and get the page count */
		$mpdf       = new Mpdf( [ 'mode' => 'c' ] );
		$page_count = $mpdf->setSourceFile( $this->file );

		if ( $this->page > $page_count ) {
			throw new \Exception( 'The page to convert to an image does not exist in the PDF' );
		}
	}

	/**
	 * Convert a PDF Page to an Image
	 *
	 * @return array
	 * @throws \ImagickException
	 *
	 * @since 1.0
	 */
	protected function generate() {
		wp_raise_memory_limit( 'image' );

		$image = new Imagick();
		$image->setResolution( $this->dpi, $this->dpi ); /* Add ability to control resolution */
		$image->readImage( $this->file . '[' . ( $this->page - 1 ) . ']' );

		/* TODO - set filename */

		if ( ! $image->valid() ) {
			throw new \Exception( 'Invalid PDF' );
		}

		$image->setImageFormat( 'jpg' );
		$image->setImageCompressionQuality( $this->quality );
		$image->setImageCompression( imagick::COMPRESSION_JPEG );

		/* Resize image to the specification */
		if ( $this->width > 0 ) {
			/* Ensure the largest edge gets resized */
			$width  = $this->width;
			$height = round( $image->getImageHeight() * ( $this->width / $image->getImageWidth() ) );

			$image->resizeImage( $width, $height, imagick::FILTER_LANCZOS, 0.8 );

			/* Crop if the height is less than the resized height */
			if ( $this->crop && $this->height > 0 && $image->getImageHeight() > $this->height ) {
				$image->cropImage( $image->getImageWidth(), $this->height, 0, 0 );
			}
		}

		$info = [
			'mime'     => 'image/' . $image->getImageFormat(),
			'data'     => $image->getImageBlob(),
			'filename' => $image->getFilename(),
		];

		unset( $image );

		return $info;
	}

	/**
	 * Output for the PDF image
	 *
	 * @since 1.0
	 */
	public function to_screen() {
		$image = $this->generate();

		header( 'Content-Type: ' . $image['mime'] );
		echo $image['data'];
	}

	/**
	 * Force download prompt for the PDF image
	 *
	 * @since 1.0
	 */
	public function to_download() {
		$image = $this->generate();

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Length: ' . strlen( $image['data'] ) );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $image['filename'] . '"' );
		echo $image['data'];
	}

	/**
	 * Return a data URI for the PDF image
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function to_data_uri() {
		$image = $this->generate();
		return 'data:' . $image['mime'] . ';base64,' . base64_encode( $image['data'] );
	}

//	/* return image blob */
//	public function to_string() {
//		return $this->generate()['data'];
//	}
//
//	/* Save to file */
//	public function to_file( $file ) {
//
//	}

}