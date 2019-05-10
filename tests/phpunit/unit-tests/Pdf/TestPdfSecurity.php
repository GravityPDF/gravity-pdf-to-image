<?php

namespace GFPDF\Plugins\PdfToImage\Pdf;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Class TestPdfSecurity
 *
 * @package GFPDF\Plugins\PdfToImage\Pdf
 *
 * @group   Pdf
 */
class TestPdfSecurity extends WP_UnitTestCase {
	/**
	 * @var PdfSecurity
	 */
	protected $class;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$this->class = new PdfSecurity();

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
	public function test_is_security_enable() {
		$this->assertFalse( $this->class->is_security_enabled( [] ) );
		$this->assertFalse( $this->class->is_security_enabled( [ 'security' => 'No' ] ) );
		$this->assertTrue( $this->class->is_security_enabled( [ 'security' => 'Yes' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_is_password_protected() {
		$this->assertFalse( $this->class->is_password_protected( [] ) );
		$this->assertFalse( $this->class->is_password_protected( [ 'security' => 'No' ] ) );
		$this->assertFalse( $this->class->is_password_protected( [ 'security' => 'Yes' ] ) );
		$this->assertFalse(
			$this->class->is_password_protected(
				[
					'security' => 'Yes',
					'password' => '',
				]
			)
		);

		$this->assertTrue(
			$this->class->is_password_protected(
				[
					'security' => 'Yes',
					'password' => 'test',
				]
			)
		);
	}

	/**
	 * @since 1.0
	 */
	public function test_has_capability() {
		$this->assertFalse( $this->class->has_capability( 'gform_full_access' ) );

		$user = wp_get_current_user();
		$user->remove_role( 'subscriber' );
		$user->add_role( 'administrator' );

		$this->assertTrue( $this->class->has_capability( 'gform_full_access' ) );
	}
}
