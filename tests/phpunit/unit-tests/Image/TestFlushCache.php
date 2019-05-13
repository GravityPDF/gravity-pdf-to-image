<?php

namespace GFPDF\Plugins\PdfToImage\Image;

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

		$this->assertSame( 10, has_action( 'gfpdf_form_update_pdf', [ $this->class, 'flush_image_cache' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_flush_image_cache() {
		$pdf = [
			'id' => '123abc',
		];

		$form_id = 15;

		$image_path = $this->image_common->get_image_path_from_pdf( 'sample.pdf', $form_id, $pdf['id'], 105, 1 );

		wp_mkdir_p( dirname( $image_path ) );
		touch( $image_path );

		$this->class->flush_image_cache( $pdf, $form_id );

		$this->assertFileNotExists( $image_path );
	}
}
