<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;
use WP_UnitTestCase;

/**
 * Class TestImageData
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 *
 * @group   Image
 */
class TestImageData extends WP_UnitTestCase {

	/**
	 * @dataProvider exception_data_provider
	 * @since        1.0
	 */
	public function test_exceptions( $mime, $data, $filename ) {
		$e = null;
		try {
			new ImageData( $mime, $data, $filename );
		} catch ( PdfToImageInvalidArgument $e ) {

		}

		$this->assertNotNull( $e );
	}

	/**
	 * @return array
	 */
	public function exception_data_provider() {
		return [
			[ 1, '', '' ],
			[ '', 1, '' ],
			[ '', '', 1 ],
		];
	}

	/**
	 * @since 1.0
	 */
	public function test_getters() {
		$image = new ImageData( 'mime', 'data', 'filename' );
		$this->assertSame( 'mime', $image->get_mime() );
		$this->assertSame( 'data', $image->get_data() );
		$this->assertSame( 'filename', $image->get_filename() );
	}
}
