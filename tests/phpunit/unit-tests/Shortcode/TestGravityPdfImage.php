<?php

namespace GFPDF\Plugins\PdfToImage\Shortcode;

use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Class TestGravityPdfImage
 *
 * @package GFPDF\Plugins\PdfToImage\Shortcode
 *
 * @group   Shortcode
 */
class TestGravityPdfImage extends WP_UnitTestCase {

	/**
	 * @var GravityPdfImage
	 */
	protected $class;

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var int
	 */
	protected $entry_id;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data = GPDFAPI::get_data_class();

		$pdf_security = new PdfSecurity();
		$image_common = new Common( $pdf_security, $data->template_tmp_location );

		$shortcode = new GravityPdfImage( GPDFAPI::get_form_class(), GPDFAPI::get_log_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), new Helper_Url_Signer() );
		$shortcode->set_debug_mode( true );
		$shortcode->set_image( $image_common );

		$this->class = $shortcode;

		$user = wp_get_current_user();
		$user->add_role( 'administrator' );
		$user->remove_role( 'subscriber' );

		$this->form_id = \GFAPI::add_form( json_decode( file_get_contents( __DIR__ . '/../../assets/json/form.json' ), true )[0] );

		$this->entry_id = \GFAPI::add_entry(
			[
				'form_id' => $this->form_id,
				'1'       => 'Full Name',
			]
		);

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		$user = wp_get_current_user();
		$user->remove_role( 'administrator' );
		$user->add_role( 'subscriber' );

		parent::tearDown();
	}

	/**
	 * @since 1.0
	 */
	public function test_process() {
		$attributes = [
			'entry' => $this->entry_id,
			'id'    => '5cd3279ba65c9',
		];

		$pdf                        = GPDFAPI::get_pdf( $this->form_id, $attributes['id'] );
		$pdf['pdf_to_image_toggle'] = 1;
		GPDFAPI::update_pdf( $this->form_id, $attributes['id'], $pdf );

		$this->assertContains( '<img src=', $this->class->process( $attributes ) );

		$attributes['raw'] = 1;

		$this->assertStringStartsWith( 'http://', $this->class->process( $attributes ) );

		$attributes['signed'] = 1;
		$url                  = $this->class->process( $attributes );

		$this->assertStringStartsWith( 'http://', $url );
		$this->assertContains( 'signature=', parse_url( $url, PHP_URL_QUERY ) );

		unset( $attributes['raw'] );
		$attributes['type'] = 'view';
		$html               = $this->class->process( $attributes );
		$this->assertContains( '<a href=', $html );
		$this->assertNotContains( 'sub_action=download', $html );

		$attributes['type'] = 'download';
		$html               = $this->class->process( $attributes );
		$this->assertContains( '<a href=', $html );
		$this->assertContains( 'sub_action=download', $html );
	}

	/**
	 * @since 1.0
	 */
	public function test_process_errors() {
		$this->assertContains( 'No Gravity Form entry ID', $this->class->process( [] ) );

		$this->assertContains( 'Could not get Gravity PDF configuration', $this->class->process( [ 'entry' => -1 ] ) );

		$attributes = [
			'entry' => $this->entry_id,
			'id'    => '5cd3279ba65c9',
		];

		$this->assertContains( 'not been configured to convert', $this->class->process( $attributes ) );

		$pdf                        = GPDFAPI::get_pdf( $this->form_id, $attributes['id'] );
		$pdf['active']              = false;
		$pdf['pdf_to_image_toggle'] = 1;
		GPDFAPI::update_pdf( $this->form_id, $attributes['id'], $pdf );

		$this->assertContains( 'PDF is inactive', $this->class->process( $attributes ) );

		$pdf['active']           = true;
		$pdf['conditional']      = 1;
		$pdf['conditionalLogic'] = [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => 1,
					'operator' => 'is',
					'value'    => 'sample',
				],
			],
		];
		GPDFAPI::update_pdf( $this->form_id, $attributes['id'], $pdf );

		$this->assertContains( 'conditional logic requirements', $this->class->process( $attributes ) );
	}

}
