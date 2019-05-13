<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

require_once( __DIR__ . '/helpers.php' );

/**
 * Class TestGenerate
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 *
 * @group   Image
 */
class TestGenerate extends WP_UnitTestCase {

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		Header::$headers = [];

		$data = \GPDFAPI::get_data_class();
		$misc = \GPDFAPI::get_misc_class();
		$misc->rmdir( $data->template_tmp_location );

		parent::tearDown();
	}

	/**
	 * @since 1.0
	 */
	protected function get( $config = [] ) {
		$data = \GPDFAPI::get_data_class();

		return new Generate(
			new Common( new PdfSecurity(), $data->template_tmp_location ),
			__DIR__ . '/../../assets/pdf/sample.pdf',
			$config
		);
	}

	/**
	 * @since        1.0
	 * @dataProvider config_data_provider
	 */
	public function test_config( $config, $bytes, $width, $height ) {
		$blob  = $this->get( $config )->to_string();
		$image = imagecreatefromstring( $blob );
		$this->assertSame( $bytes, strlen( $blob ) );
		$this->assertSame( $width, imagesx( $image ) );
		$this->assertSame( $height, imagesy( $image ) );

		unset( $image );
	}

	/*
	 * @since 1.0
	 */
	public function config_data_provider() {
		return [
			[
				[],
				24841,
				424,
				600,
			],

			[
				[
					'dpi' => 300,
				],
				24567,
				424,
				600,
			],

			[
				[
					'width' => 800,
					'height' => 600,
				],
				24841,
				424,
				600,
			],

			[
				[
					'width' => 800,
					'height' => 0,
				],
				67941,
				800,
				1132,
			],

			[
				[
					'width' => 0,
					'height' => 600,
				],
				24841,
				424,
				600,
			],

			[
				[
					'width' => 800,
					'height' => 600,
					'page'  => 2,
				],
				42251,
				800,
				566,
			],

			[
				[
					'width'  => 150,
					'height' => 150,
					'crop'   => true,
				],
				4245,
				150,
				150,
			],

			[
				[
					'width'  => 150,
					'height' => 150,
					'page'   => 2,
					'crop'   => true,
				],
				2732,
				150,
				150,
			],

			[
				[
					'width'   => 150,
					'height'  => 150,
					'quality' => 15,
				],
				507,
				106,
				150,
			],
		];
	}

	/**
	 * @since 1.0
	 */
	public function test_get_image_name() {
		$this->assertSame( 'sample.jpg', $this->get()->get_image_name() );
	}

	/**
	 * @since 1.0
	 */
	public function test_to_screen() {
		ob_start();
		$this->get()->to_screen();
		$this->assertTrue( is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Content-Type: image/jpg', Header::$headers[0] );
		$this->assertSame( 'Content-Disposition: inline; filename="sample.jpg"', Header::$headers[1] );

		ob_start();
		$image = new ImageData( 'image/png', 't', 'test.png' );
		$this->get()->to_screen( $image );
		$this->assertSame( 'Content-Type: image/png', Header::$headers[2] );
		$this->assertSame( 'Content-Disposition: inline; filename="test.png"', Header::$headers[3] );
		ob_get_clean();
	}

	/**
	 * @since 1.0
	 */
	public function test_to_download() {
		ob_start();
		$this->get()->to_download();
		$this->assertTrue( is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Cache-Control: must-revalidate, post-check=0, pre-check=0', Header::$headers[0] );
		$this->assertSame( 'Content-Description: File Transfer', Header::$headers[1] );
		$this->assertStringStartsWith( 'Content-Length: ', Header::$headers[2] );
		$this->assertSame( 'Content-Transfer-Encoding: Binary', Header::$headers[3] );
		$this->assertSame( 'Content-Type: application/octet-stream', Header::$headers[4] );
		$this->assertSame( 'Content-Disposition: attachment; filename="sample.jpg"', Header::$headers[5] );

		ob_start();
		$image = new ImageData( 'image/png', 't', 'test.png' );
		$this->get()->to_download( $image );
		$this->assertStringStartsWith( 'Content-Length: 1', Header::$headers[8] );
		$this->assertSame( 'Content-Disposition: attachment; filename="test.png"', Header::$headers[11] );
		ob_get_clean();
	}

	/**
	 * @since 1.0
	 */
	public function test_to_data_uri() {
		$this->assertStringStartsWith( 'data:image/jpg;base64,', $this->get()->to_data_uri() );

		$image = new ImageData( 'image/png', 't', 'test.png' );
		$this->assertSame( 'data:image/png;base64,dA==', $this->get()->to_data_uri( $image ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_to_string() {
		$this->assertTrue( is_jpeg( $this->get()->to_string() ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_to_object() {
		$data = $this->get()->to_object();

		$this->assertSame( 'image/jpg', $data->get_mime() );
		$this->assertSame( 'sample.jpg', $data->get_filename() );
		$this->assertTrue( is_jpeg( $data->get_data() ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_to_file() {
		$filename = sys_get_temp_dir() . '/test/' . time();
		$image    = $this->get();

		$e = null;
		try {
			$image->to_file( $filename );
		} catch ( PdfToImageInvalidArgument $e ) {

		}

		$this->assertNotNull( $e );

		$filename .= '.jpg';
		$image->to_file( $filename );
		$this->assertFileExists( $filename );

		unlink( $filename );
	}
}
