<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Helper\Helper_PDF;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use GFPDF\Plugins\PdfToImage\Pdf\PdfWrapper;
use WP_UnitTestCase;

/**
 * Class TestListener
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 *
 * @group   Image
 */
class TestListener extends WP_UnitTestCase {

	/**
	 * @var Listener
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$this->class = new Listener(
			new Common( new PdfSecurity(), sys_get_temp_dir() . '/' ),
			new PdfSecurity()
		);

		$this->class->set_logger( \GPDFAPI::get_log_class() );

		$GLOBALS['wp']->query_vars['action'] = 'img';

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();

		$this->assertSame( 10, has_action( 'gfpdf_pre_pdf_generation_initilise', [ $this->class, 'maybe_display_cached_pdf_image' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_pre_pdf_generation_output', [ $this->class, 'maybe_generate_image_from_pdf' ] ) );
	}

	public function test_maybe_generate_image_from_pdf() {
		$form = [
			'id' => 1,
		];

		$entry = [
			'id'      => 1,
			'form_id' => 1,
		];

		$pdf = [
			'id'       => '12345678',
			'filename' => 'sample',

			'security'            => 0,
			'pdf_to_image_toggle' => 0,
			'pdf_to_image_page'   => 1,
		];

		$pdf_wrapper = new PdfWrapper( $entry, $pdf );

		ob_start();
		$this->class->maybe_generate_image_from_pdf( $pdf_wrapper->get_pdf_class(), $form, $entry, $pdf, $pdf_wrapper );

		$this->assertContains( 'not been configured', ob_get_clean() );
	}
}

function wp_die( $string ) {
	echo $string;
}