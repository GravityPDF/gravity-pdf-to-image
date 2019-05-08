<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * Class TestAddImageLinkToEntryDetails
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 *
 * @group   Entry
 */
class TestAddImageLinkToEntryDetails extends WP_UnitTestCase {

	/**
	 * @var AddImageLinkToEntryDetails
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$this->class = new AddImageLinkToEntryDetails( new Common( new PdfSecurity(), sys_get_temp_dir() . '/' ) );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();

		$this->assertSame( 10, has_action( 'gfpdf_entry_detail_post_pdf_links_markup', [ $this->class, 'add_image_link_to_entry_details' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_add_image_link_to_entry_details() {
		$pdf = [ 'settings' => [ 'pdf_to_image_toggle' => 0 ] ];
		ob_start();
		$this->class->add_image_link_to_entry_details( $pdf );
		$this->assertEmpty( ob_get_clean() );

		$pdf = [
			'entry_id' => 1,
			'settings' => [
				'id'                  => '12345678',
				'pdf_to_image_toggle' => 1,
				'pdf_to_image_page'   => 1,
			],
		];
		ob_start();
		$this->class->add_image_link_to_entry_details( $pdf );
		$this->assertRegExp( '/\<a href="(.+)"\>(.+)\<\/a\>/', ob_get_clean() );
	}
}
