<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\GpdfUnitTestCase;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;

/**
 * Class TestAlwaysSaveImage
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 *
 * @group   Entry
 */
class TestAlwaysSaveImage extends GpdfUnitTestCase {

	/**
	 * @var AlwaysSaveImage
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		parent::setUp();

		$this->class = new AlwaysSaveImage( new Common( new PdfSecurity(), $this->template_tmp_location ), new PdfSecurity() );

		$this->class->set_logger( \GPDFAPI::get_log_class() );
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

		/* Verify it's attached with cached copy */
		wp_mkdir_p( $this->template_tmp_location . '11' );
		copy( __DIR__ . '/../../assets/pdf/sample.pdf', $this->template_tmp_location . '11/sample.pdf' );

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
