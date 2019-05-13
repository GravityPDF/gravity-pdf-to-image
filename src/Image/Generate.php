<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;
use Mpdf\Mpdf;
use Imagick;
use ImagickException;

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
 * Class Generate
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 */
class Generate {

	/**
	 * @var Common
	 * @since 1.0
	 */
	protected $common;

	/**
	 * @var string Path to PDF
	 * @since 1.0
	 */
	protected $file;

	/**
	 * @var string The generated image filename
	 * @since 1.0
	 */
	protected $image_filename;

	/**
	 * @var int The PDF page to load
	 * @since 1.0
	 */
	protected $page;

	/**
	 * @var int the PDF DPI
	 * @since 1.0
	 */
	protected $dpi;

	/**
	 * @var int The image quality
	 * @since 1.0
	 */
	protected $quality;

	/**
	 * @var int The image width constraint
	 * @since 1.0
	 */
	protected $width;

	/**
	 * @var int The image height constraint
	 * @since 1.0
	 */
	protected $height;

	/**
	 * @var bool Whether to crop the image to the width/height, or resize with a best fit
	 * @since 1.0
	 */
	protected $crop;

	/**
	 * Generate constructor.
	 *
	 * @param Common $common
	 * @param string $file   The absolute path to the PDF an image should be generated from
	 * @param array  $config The custom configuration that should be applied when converting the PDF to an image
	 *
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 *
	 * @since 1.0
	 */
	public function __construct( Common $common, $file, $config = [] ) {

		$this->common = $common;

		/* Override defaults with user-defined configuration */
		$config = array_merge(
			[
				'page'    => 1,
				'dpi'     => 150,
				'quality' => 95,
				'width'   => 800,
				'height'  => 600,
				'crop'    => false,
			],
			$config
		);

		$this->file    = $file;
		$this->page    = (int) $config['page'];
		$this->dpi     = abs( (int) $config['dpi'] );
		$this->quality = abs( (int) $config['quality'] );
		$this->width   = abs( (int) $config['width'] );
		$this->height  = abs( (int) $config['height'] );
		$this->crop    = (bool) $config['crop'];

		$this->set_image_name( basename( $this->file ) );

		if ( empty( $config['skip_validation'] ) ) {
			$this->check_if_valid_page();
		}
	}

	/**
	 * Verify the page to display is a valid number and exists
	 *
	 * @throws \Mpdf\MpdfException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 *
	 * @since 1.0
	 */
	protected function check_if_valid_page() {
		if ( ! is_int( $this->page ) ) {
			throw new PdfToImageInvalidArgument( '$page must be an integer' );
		}

		/* Read the PDF and get the page count */
		$mpdf       = new Mpdf( [ 'mode' => 'c' ] );
		$page_count = $mpdf->setSourceFile( $this->file );

		if ( abs( $this->page ) > $page_count ) {
			throw new PdfToImageInvalidArgument( 'The page to convert to an image does not exist in the PDF' );
		}

		/* If negative, count from the end of the document */
		if ( $this->page < 0 ) {
			$this->page = 1 + $page_count + $this->page;
		}
	}

	/**
	 * Convert a PDF Page to an Image
	 *
	 * @return ImageData
	 *
	 * @throws ImagickException
	 * @since 1.0
	 */
	protected function generate() {
		wp_raise_memory_limit( 'image' );

		$image = new Imagick();
		$image->setResolution( $this->dpi, $this->dpi ); /* Add ability to control resolution */
		$image->readImage( $this->get_pdf_path() );

		if ( $this->should_show_all_pages() ) {
			$image->resetIterator();
			$image = $image->appendImages( true );
		}

		if ( ! $image->valid() ) {
			throw new PdfToImageInvalidArgument( 'Invalid PDF' );
		}

		$image->setFilename( sprintf( '%s.jpg', basename( $this->file, '.pdf' ) ) );

		$image->setImageFormat( 'jpg' );
		$image->setImageCompressionQuality( $this->quality );
		$image->setImageCompression( Imagick::COMPRESSION_JPEG );

		/* Prepare image for output */
		$image = $this->embed_cmyk_color_profile( $image );
		$image = $this->resize_and_crop_image( $image );

		$info = new ImageData(
			'image/' . $image->getImageFormat(),
			$image->getImageBlob(),
			$image->getFilename()
		);

		unset( $image );

		return $info;
	}

	/**
	 * Check if all PDF pages should be shown in the generated image
	 *
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
	protected function get_pdf_path() {
		$pdf_file = $this->file;
		if ( ! $this->should_show_all_pages() ) {
			$pdf_file .= '[' . ( $this->page - 1 ) . ']';
		}

		return $pdf_file;
	}

	/**
	 * Resize and Crop the Image, if needed
	 *
	 * @param Imagick $image
	 *
	 * @return Imagick
	 *
	 * @since 1.0
	 */
	protected function resize_and_crop_image( Imagick $image ) {
		if ( ( $this->width > 0 || $this->height > 0 ) && $this->width < $image->getImageWidth() && $this->height < $image->getImageHeight() ) {

			if ( $this->crop ) {
				/* When cropping, resize against the smallest edge */
				$width  = $image->getImageWidth() > $image->getImageHeight() ? 0 : $this->width;
				$height = $image->getImageWidth() < $image->getImageHeight() ? 0 : $this->height;
			} else {
				$width  = $this->width;
				$height = $this->height;
			}

			$best_fit = $width > 0 && $height > 0;
			$image->resizeImage( $width, $height, Imagick::FILTER_LANCZOS, 0.8, $best_fit );

			if ( $this->crop ) {
				$image->cropImage( $this->width, $this->height, 0, 0 );
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
		if ( array_search( 'icc', $profiles, true ) === false ) {
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
	 * Set the image filename
	 *
	 * @param string $file The name of the image or PDF
	 *
	 * @throws PdfToImageInvalidArgument
	 *
	 * @since 1.0
	 */
	public function set_image_name( $file ) {
		if ( substr( $file, -4 ) === '.pdf' ) {
			$this->image_filename = $this->common->get_name_from_pdf( $file );
		} elseif ( substr( $file, -4 ) !== '.jpg' ) {
			throw new PdfToImageInvalidArgument( 'The image file extension must be .jpg' );
		} else {
			$this->image_filename = $file;
		}
	}

	/**
	 * Output for the PDF image
	 *
	 * @param null|ImageData $image
	 *
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_screen( $image = null ) {

		if ( ! $image instanceof ImageData ) {
			$image = $this->generate();
		}

		header( 'Content-Type: ' . $image->get_mime() );
		header( 'Content-Disposition: inline; filename="' . $image->get_filename() . '"' );
		echo $image->get_data();
	}

	/**
	 * Force download prompt for the PDF image
	 *
	 * @param null|ImageData $image
	 *
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_download( $image = null ) {

		if ( ! $image instanceof ImageData ) {
			$image = $this->generate();
		}

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Length: ' . strlen( $image->get_data() ) );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $image->get_filename() . '"' );

		echo $image->get_data();
	}

	/**
	 * Return a data URI for the PDF image
	 *
	 * @param null|ImageData $image
	 *
	 * @return string
	 *
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_data_uri( $image = null ) {
		if ( ! $image instanceof ImageData ) {
			$image = $this->generate();
		}

		return 'data:' . $image->get_mime() . ';base64,' . base64_encode( $image->get_data() );
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
		return $this->generate()->get_data();
	}

	/**
	 * Returns the image information in object form
	 *
	 * @return ImageData
	 * @throws ImagickException
	 *
	 * @since 1.0
	 */
	public function to_object() {
		return $this->generate();
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
			throw new PdfToImageInvalidArgument( 'The image file extension must be .jpg' );
		}

		if ( wp_mkdir_p( dirname( $file ) ) === false ) {
			throw new PdfToImageInvalidArgument( 'Failed to create folder:' . basename( $file ) );
		}

		if ( ! file_put_contents( $file, $this->generate()->get_data() ) ) {
			throw new PdfToImageInvalidArgument( 'Failed to write image to file: ' . $file );
		}
	}
}
