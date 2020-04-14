<?php

namespace GFPDF\Plugins\PdfToImage\Image;

use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * Class TestFlushCache
 *
 * @package GFPDF\Plugins\PdfToImage\Image
 *
 * @group   Image
 */
class TestFlushCache extends WP_UnitTestCase {

	/**
	 * @var FlushCache
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
		$this->class        = new FlushCache( $this->image_common );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();

		$this->assertSame( 10, has_action( 'gfpdf_form_update_pdf', [ $this->class, 'flush_pdf_image_cache' ] ) );
		$this->assertSame( 10, has_action( 'gform_after_update_entry', [ $this->class, 'gform_after_update_entry' ] ) );
		$this->assertSame( 10, has_action( 'gform_post_update_entry', [ $this->class, 'gform_post_update_entry' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_flush_pdf_image_cache() {
		$pdf = [
			'id' => '123abc',
		];

		$form_id = 15;

		$image_path = $this->image_common->get_image_path_from_pdf( 'sample.pdf', $form_id, $pdf['id'], 105, 1 );

		wp_mkdir_p( dirname( $image_path ) );
		touch( $image_path );

		$this->class->flush_pdf_image_cache( $pdf, $form_id );

		$this->assertFileNotExists( $image_path );
	}

	/**
	 * @since 1.0
	 */
	public function test_flush_entry_image_cache() {
		$form_id = \GFAPI::add_form( json_decode( file_get_contents( __DIR__ . '/../../assets/json/form.json' ), true )[0] );

		$pdf = [
			'id' => '5cd3279ba65c9',
		];

		$entry_id = 105;

		$image_path = $this->image_common->get_image_path_from_pdf( 'sample.pdf', $form_id, $pdf['id'], $entry_id, 1 );

		wp_mkdir_p( dirname( $image_path ) );
		touch( $image_path );

		$this->class->flush_entry_image_cache( $form_id, $entry_id );
		$this->assertFileNotExists( $image_path );
	}
}
