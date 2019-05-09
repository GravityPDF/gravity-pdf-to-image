<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Exception\PdfGenerationAndSave;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * Class TestCommon
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 *
 * @group   Image
 */
class TestCommon extends WP_UnitTestCase {

	/**
	 * @var Common
	 */
	protected $class;

	/**
	 * @var @string
	 */
	protected $original_font_location;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data = \GPDFAPI::get_data_class();

		$this->class = new Common( new PdfSecurity(), $data->template_tmp_location );

		$this->original_font_location = $data->template_font_location;
		$data->template_font_location = __DIR__ . '/../../assets/fonts/';

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		$data                         = \GPDFAPI::get_data_class();
		$data->template_font_location = $this->original_font_location;

		parent::tearDown();
	}

	/**
	 * @since 1.0
	 */
	public function test_has_active_image_settings() {
		$settings = [ 'pdf_to_image_toggle' => 0 ];
		$this->assertFalse( $this->class->has_active_image_settings( $settings ) );

		$settings = [ 'pdf_to_image_toggle' => 1 ];
		$this->assertTrue( $this->class->has_active_image_settings( $settings ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_is_attachment() {
		$settings = [];
		$this->assertFalse( $this->class->is_attachment( 'PDF', $settings ) );

		$settings = [ 'pdf_to_image_notifications' => 'Image' ];
		$this->assertFalse( $this->class->is_attachment( 'PDF', $settings ) );
		$this->assertTrue( $this->class->is_attachment( 'Image', $settings ) );
	}

	/*
	 * @since 1.0
	 */
	public function test_get_settings() {
		$expected_keys = [ 'page', 'dpi', 'quality', 'width', 'height', 'crop' ];
		$this->assertCount( 0, array_diff( $expected_keys, array_keys( $this->class->get_settings( [] ) ) ) );

		$settings = [
			'pdf_to_image_page'            => 3,
			'pdf_to_image_dpi'             => 300,
			'pdf_to_image_quality'         => 80,
			'pdf_to_image_resize_and_crop' => [ 500, 400, 1 ],
		];

		$results = $this->class->get_settings( $settings );

		$this->assertSame( 3, $results['page'] );
		$this->assertSame( 300, $results['dpi'] );
		$this->assertSame( 80, $results['quality'] );
		$this->assertSame( 500, $results['width'] );
		$this->assertSame( 400, $results['height'] );
		$this->assertSame( true, $results['crop'] );
	}

	/**
	 * @since 1.0
	 */
	public function test_get_url() {

		/* Test with disabled permalinks */
		$results = $this->class->get_url( '12345678', '1', 3, false, false );
		$this->assertContains( '/?gpdf=1&pid=12345678&lid=1&action=img&page=3', $results );

		$results = $this->class->get_url( '12345678', '1', 3, true, false );
		$this->assertContains( '/?gpdf=1&pid=12345678&lid=1&action=img&page=3&sub_action=download', $results );

		global $wp_rewrite;
		$wp_rewrite->permalink_structure = '%year%';

		$results = $this->class->get_url( '12345678', '1', 3, false, false );
		$this->assertContains( '/pdf/12345678/1/img/3/', $results );

		$results = $this->class->get_url( '12345678', '1', 3, true, false );
		$this->assertContains( '/pdf/12345678/1/img/3/download/', $results );

		$wp_rewrite->permalink_structure = null;
	}

	/**
	 * @since 1.0
	 */
	public function test_maybe_generate_tmp_pdf() {
		$form_id = \GFAPI::add_form( json_decode( file_get_contents( __DIR__ . '/../../assets/json/form.json' ), true )[0] );

		$entry_id = \GFAPI::add_entry(
			[
				'form_id' => $form_id,
				'1'       => 'Full Name',
			]
		);

		$entry = \GFAPI::get_entry( $entry_id );

		$settings = [
			'id'       => '5cd3279ba65c9',
			'filename' => 'Zadani',
			'template' => 'zadani',
			'font'     => 'dejavusans',
			'format'   => 'A4',
			'security' => 'No',
		];

		/* Create and save PDF */
		try {
			$results = $this->class->maybe_generate_tmp_pdf( $entry, $settings );
		} catch ( PdfGenerationAndSave $e ) {
			$this->fail( 'Could not generate and save PDF' );
		}

		$this->assertSame( 'Zadani.pdf', $results->get_filename() );

		/* Create and save tmp PDF */
		$settings['security'] = 'Yes';

		try {
			$results = $this->class->maybe_generate_tmp_pdf( $entry, $settings );
		} catch ( PdfGenerationAndSave $e ) {
			$this->fail( 'Could not generate and save PDF' );
		}

		$this->assertContains( '@@Zadani.pdf', $results->get_filename() );
	}

	/**
	 * @since 1.0
	 */
	public function test_get_original_pdf_filename() {
		$this->assertSame( 'Zadani.pdf', $this->class->get_original_pdf_filename( 'Zadani.pdf' ) );
		$this->assertSame( 'Zadani.pdf', $this->class->get_original_pdf_filename( '123456789@@Zadani.pdf' ) );
	}
}
