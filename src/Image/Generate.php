<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use Mpdf\Mpdf;
use Imagick;
use ImagickException;
use InvalidArgumentException;

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
	 * @var string The generated image filename
	 */
	protected $image_filename;

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

	public function __construct( $file, $config = [] ) {

		/* Override defaults with user-defined configuration */
		$config = array_merge( [
			'page'    => 1,
			'dpi'     => 150,
			'quality' => 95,
			'width'   => 800,
			'height'  => 600,
			'crop'    => false,
		], $config );

		$this->file           = $file;
		$this->page           = (int) $config['page'];
		$this->dpi            = abs( (int) $config['dpi'] );
		$this->quality        = abs( (int) $config['quality'] );
		$this->width          = abs( (int) $config['width'] );
		$this->height         = abs( (int) $config['height'] );
		$this->crop           = (bool) $config['crop'];
		$this->image_filename = sprintf( '%s.jpg', basename( $this->file, '.pdf' ) );

		$this->check_if_valid_page();
	}

	/**
	 * Verify the page to display is a valid number and exists
	 *
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws InvalidArgumentException
	 *
	 * @since 1.0
	 */
	protected function check_if_valid_page() {
		if ( ! is_int( $this->page ) ) {
			throw new InvalidArgumentException( '$page must be an integer' );
		}

		/* Read the PDF and get the page count */
		$mpdf       = new Mpdf( [ 'mode' => 'c' ] );
		$page_count = $mpdf->setSourceFile( $this->file );

		if ( abs( $this->page ) > $page_count ) {
			throw new InvalidArgumentException( 'The page to convert to an image does not exist in the PDF' );
		}

		/* If negative, count from the end of the document */
		if ( $this->page < 0 ) {
			$this->page = 1 + $page_count + $this->page;
		}
	}

	/**
	 * Convert a PDF Page to an Image
	 *
	 * @return array
	 *
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	protected function generate() {
		wp_raise_memory_limit( 'image' );

		$image = new Imagick();
		$image->setResolution( $this->dpi, $this->dpi ); /* Add ability to control resolution */
		$image->readImage( $this->get_pdf_filename() );

		if ( $this->should_show_all_pages() ) {
			$image->resetIterator();
			$image = $image->appendImages( true );
		}

		if ( ! $image->valid() ) {
			throw new InvalidArgumentException( 'Invalid PDF' );
		}

		$image->setFilename( sprintf( '%s.jpg', basename( $this->file, '.pdf' ) ) );

		$image->setImageFormat( 'jpg' );
		$image->setImageCompressionQuality( $this->quality );
		$image->setImageCompression( Imagick::COMPRESSION_JPEG );

		/* Prepare image for output */
		$image = $this->embed_cmyk_color_profile( $image );
		$image = $this->resize_and_crop_image( $image );

		$info = [
			'mime'     => 'image/' . $image->getImageFormat(),
			'data'     => $image->getImageBlob(),
			'filename' => $image->getFilename(),
		];

		unset( $image );

		return $info;
	}

	/**
	 * @return bool
	 *
	 * @since 1.0
	 */
	protected function should_show_all_pages() {
		return $this->page === 0;
	}

	/**
	 * Get the filename, while respecting the page(s) being generated
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	protected function get_pdf_filename() {
		$pdf_file = $this->file;
		if ( ! $this->should_show_all_pages() ) {
			$pdf_file .= '[' . ( $this->page - 1 ) . ']';
		}

		return $pdf_file;
	}

	/**
	 * @param Imagick $image
	 *
	 * @return Imagick
	 *
	 * @since 1.0
	 */
	protected function resize_and_crop_image( Imagick $image ) {
		if ( $this->width > 0 ) {
			/* Ensure the largest edge gets resized */
			if ( $this->width > $this->height ) {
				$width  = $this->width;
				$height = round( $image->getImageHeight() * ( $this->width / $image->getImageWidth() ) );
			} else {
				$width  = round( $image->getImageWidth() * ( $this->height / $image->getImageHeight() ) );
				$height = $this->height;
			}

			$image->resizeImage( $width, $height, Imagick::FILTER_LANCZOS, 0.8 );

			if ( $this->crop ) {
				if ( $this->height > 0 && $image->getImageHeight() > $this->height ) {
					$image->cropImage( $image->getImageWidth(), $this->height, 0, 0 ); /* Crop $y from the bottom */
				} elseif ( $this->width > 0 && $image->getImageWidth() > $this->width ) {
					$image->cropImage( $this->width, $image->getImageHeight(), ( $width - $this->width ) / 2, 0 ); /* Crop $x from both left and right evenly */
				}
			}
		}

		return $image;
	}

	/**
	 * Convert CMYK to RGB
	 *
	 * @param Imagick $image
	 *
	 * @return Imagick
	 *
	 * @since 1.0
	 */
	protected function embed_cmyk_color_profile( Imagick $image ) {

		if ( $image->getImageColorspace() !== Imagick::COLORSPACE_CMYK ) {
			return $image;
		}

		$profiles = $image->getImageProfiles( '*', false );
		if ( array_search( 'icc', $profiles ) === false ) {
			$icc_cmyk = file_get_contents( dirname( GFPDF_PDF_TO_IMAGE_FILE ) . '/assets/iccprofile/USWebCoatedSWOP.icc' );
			$image->profileImage( 'icc', $icc_cmyk );
			unset( $icc_cmyk );
		}

		/* Add an RGB Profile */
		$icc_rgb = file_get_contents( dirname( GFPDF_PDF_TO_IMAGE_FILE ) . '/assets/iccprofile/sRGB_v4_ICC_preference.icc' );
		$image->profileImage( 'icc', $icc_rgb );
		unset( $icc_rgb );

		/* Now strip the embedded colorspace */
		$image->stripImage();

		return $image;
	}

	/**
	 * Get the filename of the image
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_image_name() {
		return $this->image_filename;
	}

	/**
	 * Output for the PDF image
	 *
	 * @since 1.0
	 */
	public function to_screen() {
		$image = $this->generate();

		header( 'Content-Type: ' . $image['mime'] );
		header( 'Content-Disposition: inline; filename="' . $image['filename'] . '"' );
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
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_data_uri() {
		$image = $this->generate();
		return 'data:' . $image['mime'] . ';base64,' . base64_encode( $image['data'] );
	}

	/**
	 * Return the image blob
	 *
	 * @return string
	 *
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_string() {
		return $this->generate()['data'];
	}

	/**
	 * Write image to file
	 *
	 * @param string $file The absolute path and filename of the image
	 *
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_file( $file ) {

		if ( substr( $file, -4 ) !== '.jpg' ) {
			throw new \Exception( 'The image file extension must be .jpg' );
		}

		if ( wp_mkdir_p( basename( $file ) ) === false ) {
			throw new \Exception( 'Failed to create folder:' . basename( $file ) );
		}

		if ( ! file_put_contents( $file, $this->generate()['data'] ) ) {
			throw new \Exception( 'Failed to write image to file: ' . $file );
		}
	}
}