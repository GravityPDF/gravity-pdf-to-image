<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use GFPDF\Plugins\PdfToImage\Pdf\PdfWrapper;
use Mpdf\Mpdf;
use WP_UnitTestCase;

require_once( __DIR__ . '/helpers.php' );

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
	 * @var Common
	 */
	protected $image_common;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data               = \GPDFAPI::get_data_class();
		$this->image_common = new Common( new PdfSecurity(), $data->template_tmp_location );

		$this->class = new Listener(
			$this->image_common,
			new PdfSecurity()
		);

		$this->class->set_logger( \GPDFAPI::get_log_class() );

		$GLOBALS['wp']->query_vars['action'] = 'img';

		$user = wp_get_current_user();
		$user->remove_role( 'subscriber' );
		$user->add_role( 'administrator' );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		Header::$headers = [];
		$data            = \GPDFAPI::get_data_class();
		$misc            = \GPDFAPI::get_misc_class();
		$misc->rmdir( $data->template_tmp_location );

		$user = wp_get_current_user();
		$user->remove_role( 'administrator' );
		$user->add_role( 'subscriber' );

		parent::tearDown();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();

		$this->assertSame( 10, has_action( 'gfpdf_pre_pdf_generation_initilise', [ $this->class, 'maybe_display_cached_pdf_image' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_pre_pdf_generation_output', [ $this->class, 'maybe_generate_image_from_pdf' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_maybe_display_cached_pdf_image() {
		$form = [
			'id' => 2,
		];

		$entry = [
			'id'      => 2,
			'form_id' => 1,
		];

		$pdf = [
			'id'                  => '12345678',
			'filename'            => 'sample',

			'security'            => 'No',
			'pdf_to_image_toggle' => 0,
			'pdf_to_image_page'   => 1,
		];

		$pdf_wrapper = new PdfWrapper( $entry, $pdf );

		/* Verify the image does not exist */
		ob_start();
		$this->class->maybe_display_cached_pdf_image( '', $form, $entry, $pdf, $pdf_wrapper );
		$this->assertEmpty( ob_get_clean() );

		/* Verify the cached copy is served inline */
		$image_absolute_path = $this->image_common->get_image_path_from_pdf( $pdf_wrapper->get_filename(), $form['id'], $entry['id'] );
		wp_mkdir_p( dirname( $image_absolute_path ) );
		copy( __DIR__ . '/../../assets/image/sample.jpg', $image_absolute_path );

		ob_start();
		$this->class->maybe_display_cached_pdf_image( '', $form, $entry, $pdf, $pdf_wrapper );
		$this->assertTrue( is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Content-Type: image/jpeg', Header::$headers[0] );

		/* Test image download generation */
		Header::$headers                         = [];
		$GLOBALS['wp']->query_vars['sub_action'] = 'download';

		ob_start();
		$this->class->maybe_display_cached_pdf_image( '', $form, $entry, $pdf, $pdf_wrapper );
		$this->assertTrue( is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Content-Description: File Transfer', Header::$headers[1] );
	}

	/**
	 * @since 1.0
	 */
	public function test_maybe_generate_image_from_pdf() {
		$form = [
			'id' => 2,
		];

		$entry = [
			'id'      => 2,
			'form_id' => 1,
		];

		$pdf = [
			'id'                  => '12345678',
			'filename'            => 'sample',

			'security'            => 'No',
			'pdf_to_image_toggle' => 0,
			'pdf_to_image_page'   => 1,
		];

		$mpdf        = new Mpdf( [ 'mode' => 'c' ] );
		$pdf_wrapper = new PdfWrapper( $entry, $pdf );

		/* Test generic error */
		ob_start();
		$this->class->maybe_generate_image_from_pdf( $mpdf, $form, $entry, $pdf, $pdf_wrapper );
		$this->assertContains( 'There was a problem', ob_get_clean() );

		/* Test inactive settings error */
		ob_start();
		$this->class->maybe_generate_image_from_pdf( $mpdf, $form, $entry, $pdf, $pdf_wrapper );
		$this->assertContains( 'not been configured', ob_get_clean() );

		/* Test password protected */
		$pdf = array_merge(
			$pdf,
			[
				'security'            => 'Yes',
				'pdf_to_image_toggle' => 1,
				'password'            => 'test',
			]
		);

		ob_start();
		$this->class->maybe_generate_image_from_pdf( $mpdf, $form, $entry, $pdf, $pdf_wrapper );

		$this->assertContains( 'Password protected', ob_get_clean() );

		/* Test image inline generation */
		$pdf['security'] = 'No';

		ob_start();
		$this->class->maybe_generate_image_from_pdf( $mpdf, $form, $entry, $pdf, $pdf_wrapper );
		$this->assertTrue( is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Content-Type: image/jpeg', Header::$headers[0] );

		/* Test image download generation */
		Header::$headers                         = [];
		$GLOBALS['wp']->query_vars['sub_action'] = 'download';

		ob_start();
		$this->class->maybe_generate_image_from_pdf( $mpdf, $form, $entry, $pdf, $pdf_wrapper );
		$this->assertTrue( is_jpeg( ob_get_clean() ) );
		$this->assertSame( 'Content-Description: File Transfer', Header::$headers[1] );
	}

	/**
	 * @since 1.0
	 */
	public function test_is_pdf_image_url() {
		$this->assertTrue( $this->class->is_pdf_image_url() );

		unset( $GLOBALS['wp']->query_vars['action'] );
		$this->assertFalse( $this->class->is_pdf_image_url() );

		$GLOBALS['wp']->query_vars['action'] = 'other';
		$this->assertFalse( $this->class->is_pdf_image_url() );
	}

	/**
	 * @since 1.0
	 */
	public function test_get_pdf_image_url_config() {
		$results = $this->class->get_pdf_image_url_config();

		$this->assertSame( '', $results[0] );
		$this->assertSame( 0, $results[1] );

		$GLOBALS['wp']->query_vars['sub_action'] = 'download';
		$GLOBALS['wp']->query_vars['page']       = 1;

		$results = $this->class->get_pdf_image_url_config();

		$this->assertSame( 'download', $results[0] );
		$this->assertSame( 1, $results[1] );
	}
}

