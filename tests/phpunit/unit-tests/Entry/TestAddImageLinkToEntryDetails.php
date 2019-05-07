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
		$this->class = new AddImageLinkToEntryDetails( new Common( new PdfSecurity(), sys_get_temp_dir() ) );

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

	}
}
