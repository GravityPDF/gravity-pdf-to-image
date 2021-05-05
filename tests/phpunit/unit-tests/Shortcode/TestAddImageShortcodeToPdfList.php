<?php

namespace GFPDF\Plugins\PdfToImage\Shortcode;

use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Class TestAddImageShortcodeToPdfList
 *
 * @package GFPDF\Plugins\PdfToImage\Shortcode
 *
 * @group   Shortcode
 */
class TestAddImageShortcodeToPdfList extends WP_UnitTestCase {

	/**
	 * @var AddImageShortcodeToPdfList
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data        = \GPDFAPI::get_data_class();
		$this->class = new AddImageShortcodeToPdfList( new Common( new PdfSecurity(), $data->template_tmp_location ) );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();
		$this->assertSame( 10, has_action( 'gfpdf_post_pdf_list_shortcode_column', [ $this->class, 'add_image_shortcode_to_pdf_list' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_add_image_shortcode_to_pdf_list() {
		ob_start();
		$this->class->add_image_shortcode_to_pdf_list( [] );
		$this->assertEmpty( ob_get_clean() );

		$settings = [
			'id'                  => '12345678',
			'name'                => 'Sample',
			'pdf_to_image_toggle' => 1,
		];

		ob_start();
		$this->class->add_image_shortcode_to_pdf_list( $settings );
		$this->assertStringStartsWith( '<input type=', ob_get_clean() );
	}
}
