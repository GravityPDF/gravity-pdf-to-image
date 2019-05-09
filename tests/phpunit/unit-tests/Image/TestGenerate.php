<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfToImageInvalidArgument;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * Class TestGenerate
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 *
 * @group   Image
 */
class TestGenerate extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	static $headers = [];

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		self::$headers = [];

		parent::tearDown();
	}

	/**
	 * @param array $config
	 *
	 * @return Generate
	 * @throws \Mpdf\MpdfException
	 *
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
	 * @TODO fix this after the resize / crop functionality is reworked
	 */
	public function config_data_provider() {
		return [
			[
				[],
				24567,
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
					'width' => 600,
				],
				24841,
				424,
				600,
			],

			[
				[
					'width' => 800,
					'width' => 600,
					'page'  => 2,
				],
				42251,
				800,
				566,
			],

			[
				[
					'crop'   => true,
					'width'  => 150,
					'height' => 150,
				],
				2864,
				150,
				150,
			],

			[
				[
					'page'   => 2,
					'crop'   => true,
					'width'  => 150,
					'height' => 150,
				],
				3129,
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
		$this->assertTrue( $this->is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Content-Type: image/jpg', self::$headers[0] );
		$this->assertSame( 'Content-Disposition: inline; filename="sample.jpg"', self::$headers[1] );

		ob_start();
		$image = new ImageData( 'image/png', 't', 'test.png' );
		$this->get()->to_screen( $image );
		$this->assertSame( 'Content-Type: image/png', self::$headers[2] );
		$this->assertSame( 'Content-Disposition: inline; filename="test.png"', self::$headers[3] );
		ob_get_clean();
	}

	/**
	 * @since 1.0
	 */
	public function test_to_download() {
		ob_start();
		$this->get()->to_download();
		$this->assertTrue( $this->is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Cache-Control: must-revalidate, post-check=0, pre-check=0', self::$headers[0] );
		$this->assertSame( 'Content-Description: File Transfer', self::$headers[1] );
		$this->assertStringStartsWith( 'Content-Length: ', self::$headers[2] );
		$this->assertSame( 'Content-Transfer-Encoding: Binary', self::$headers[3] );
		$this->assertSame( 'Content-Type: application/octet-stream', self::$headers[4] );
		$this->assertSame( 'Content-Disposition: attachment; filename="sample.jpg"', self::$headers[5] );

		ob_start();
		$image = new ImageData( 'image/png', 't', 'test.png' );
		$this->get()->to_download( $image );
		$this->assertStringStartsWith( 'Content-Length: 1', self::$headers[8] );
		$this->assertSame( 'Content-Disposition: attachment; filename="test.png"', self::$headers[11] );
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
		$this->assertTrue( $this->is_jpeg( $this->get()->to_string() ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_to_object() {
		$data = $this->get()->to_object();

		$this->assertSame( 'image/jpg', $data->get_mime() );
		$this->assertSame( 'sample.jpg', $data->get_filename() );
		$this->assertTrue( $this->is_jpeg( $data->get_data() ) );
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

	/**
	 * Check the magic bytes of the data and verify it is a JPG
	 *
	 * @param string $data
	 *
	 * @return bool
	 */
	protected function is_jpeg( $data ) {
		return ( bin2hex( $data[0] ) == 'ff' && bin2hex( $data[1] ) == 'd8' );
	}

}

function header( $string ) {
	array_push( TestGenerate::$headers, $string );
}
