<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use WP_UnitTestCase;

/**
 * Class TestAddImageLinkToEntryList
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 *
 * @group   Entry
 */
class TestAddImageLinkToEntryList extends WP_UnitTestCase {

	/**
	 * @var AddImageLinkToEntryList
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$this->class = new AddImageLinkToEntryList( new Common( new PdfSecurity(), sys_get_temp_dir() ) );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();

		$this->assertFalse( has_action( 'gfpdf_get_pdf_display_list', [ $this->class, 'add_image_link_to_entry_list' ] ) );

		$_GET['page'] = 'gf_entries';
		$this->class->init();

		$this->assertSame( 10, has_action( 'gfpdf_get_pdf_display_list', [ $this->class, 'add_image_link_to_entry_list' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_add_image_link_to_entry_list() {
		$list = [
			[
				'name'     => 'test',
				'entry_id' => 1,
				'settings' => [
					'id'                  => '12345678',
					'pdf_to_image_toggle' => 0,
					'pdf_to_image_page'   => 1,
				],
			],
		];

		$this->assertCount( 1, $this->class->add_image_link_to_entry_list( $list ) );

		$list[0]['settings']['pdf_to_image_toggle'] = 1;

		$results = $this->class->add_image_link_to_entry_list( $list );
		$this->assertCount( 2, $results );
		$this->assertStringStartsWith( 'Image:', $results[1]['name'] );
		$this->assertRegExp( '/action=img/', $results[1]['view'] );
		$this->assertRegExp( '/sub_action=download/', $results[1]['download'] );
	}
}
