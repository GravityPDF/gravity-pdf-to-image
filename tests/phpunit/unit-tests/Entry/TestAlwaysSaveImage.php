<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * Class TestAlwaysSaveImage
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 *
 * @group   Entry
 */
class TestAlwaysSaveImage extends WP_UnitTestCase {

	/**
	 * @var AlwaysSaveImage
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data        = \GPDFAPI::get_data_class();
		$this->class = new AlwaysSaveImage( new Common( new PdfSecurity(), $data->template_tmp_location ), new PdfSecurity() );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_listener() {
		$this->class->add_listener();
		$this->assertSame( 10, has_action( 'gfpdf_post_save_pdf', [ $this->class, 'maybe_save_image' ] ) );
		$this->class->remove_listener();
		$this->assertFalse( has_action( 'gfpdf_post_save_pdf', [ $this->class, 'maybe_save_image' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_maybe_save_image() {
		$form  = [ 'id' => 1 ];
		$entry = [
			'id'      => 1,
			'form_id' => 1,
		];

		$pdf = [
			'id'                  => '12345678',
			'filename'            => 'sample',

			'security'            => 0,
			'pdf_to_image_toggle' => 1,
			'pdf_to_image_page'   => 1,
		];

		$this->class->maybe_save_image( '', '', $pdf, $entry, $form );

		add_action(
			'gfpdf_gravitypdfimage_post_save_image',
			function( $image_absolute_path ) {
				$this->assertFileExists( $image_absolute_path );
				@unlink( $image_absolute_path );
			}
		);

		$this->assertSame( 1, did_action( 'gfpdf_gravitypdfimage_post_save_image' ) );
	}
}
