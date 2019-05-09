<?php

namespace GFPDF\Plugins\PdfToImage\Options;

use WP_UnitTestCase;

/**
 * Class TestAddPdfToImageFields
 *
 * @package GFPDF\Plugins\PdfToImage\Options
 *
 * @group   Options
 */
class TestAddPdfToImageFields extends WP_UnitTestCase {

	/**
	 * @var AddPdfToImageFields
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$this->class = new AddPdfToImageFields( \GPDFAPI::get_misc_class(), \GPDFAPI::get_options_class() );
		$this->class->set_logger( \GPDFAPI::get_log_class() );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_add_options() {
		$this->assertNotCount( 0, $this->class->add_options( [] ) );

		add_filter( 'gfpdf_display_pdf_to_image_options', '__return_false' );
		$this->assertCount( 0, $this->class->add_options( [] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_resize_and_crop_callback() {
		ob_start();
		$this->class->resize_and_crop_callback( [ 'id' => 'name', 'desc' => '' ] );
		$results = ob_get_clean();

		$this->assertSame( 3, preg_match_all( '/<input type\=/', $results ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_sanitize_resize_and_crop_field() {
		$this->assertSame( 'test', $this->class->sanitize_resize_and_crop_field( 'test', 'other' ) );

		$value = [
			-20.5,
			25,
			5,
		];

		$new_value = $this->class->sanitize_resize_and_crop_field( $value, 'pdf_to_image_resize_and_crop' );

		$this->assertSame( 20, $new_value[0] );
		$this->assertSame( 25, $new_value[1] );
		$this->assertSame( 1, $new_value[2] );

		$value = [
			15.75,
			10.25,
		];

		$new_value = $this->class->sanitize_resize_and_crop_field( $value, 'pdf_to_image_resize_and_crop' );

		$this->assertSame( 15, $new_value[0] );
		$this->assertSame( 10, $new_value[1] );
		$this->assertSame( 0, $new_value[2] );
	}

	/**
	 * @since 1.0
	 */
	public function test_load_admin_assets() {

		/* Fail the test */
		$wp_scripts = wp_scripts();
		$wp_styles  = wp_styles();

		$this->assertArrayNotHasKey( 'gfpdf_js_pdf_to_image', $wp_scripts->registered );
		$this->assertArrayNotHasKey( 'gfpdf_css_pdf_to_image', $wp_styles->registered );

		/* Replicate the Gravity PDF settings admin page */
		set_current_screen( 'dashboard' );
		$_GET['page'] = 'gfpdf-';
		$_GET['id']   = 1;
		$_GET['pid']  = 1;

		$this->class->load_admin_assets();

		$wp_scripts = wp_scripts();
		$wp_styles  = wp_styles();

		$this->assertArrayHasKey( 'gfpdf_js_pdf_to_image', $wp_scripts->registered );
		$this->assertArrayHasKey( 'gfpdf_css_pdf_to_image', $wp_styles->registered );
	}
}
