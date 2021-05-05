<?php

namespace GFPDF\Plugins\PdfToImage\Permalink;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Class TestRegister
 *
 * @package GFPDF\Plugins\PdfToImage\Permalink
 *
 * @group   Permalink
 */
class TestRegister extends WP_UnitTestCase {

	/**
	 * @var Register
	 */
	protected $class;

	protected $wp_rewrite_backup;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		global $wp_rewrite;
		$this->wp_rewrite_backup = $wp_rewrite;
		$wp_rewrite              = new \WP_Rewrite();

		$this->class = new Register();

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		global $wp_rewrite;
		$wp_rewrite = $this->wp_rewrite_backup;

		parent::tearDown();
	}

	/**
	 * @since 1.0
	 */
	public function test_register_permalink() {
		global $wp_rewrite;
		$wp_rewrite->permalink_structure = '%month%';

		$pre_register_total = count( $wp_rewrite->rewrite_rules() );
		$this->class->register_permalink();
		$post_register_total = count( $wp_rewrite->rewrite_rules() );

		$this->assertGreaterThan( $pre_register_total, $post_register_total );
	}

	/**
	 * @since 1.0
	 */
	public function test_maybe_register_rewrite_tags() {
		$this->assertCount( 0, $this->class->maybe_register_rewrite_tags( [] ) );
		$this->assertCount( 2, $this->class->maybe_register_rewrite_tags( [ 'gpdf' ] ) );
	}
}
