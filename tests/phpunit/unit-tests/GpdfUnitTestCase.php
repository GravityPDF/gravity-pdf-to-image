<?php

namespace GFPDF\Plugins\PdfToImage;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Class GpdfUnitTestCase
 *
 * @package GFPDF\Plugins\PdfToImage
 */
class GpdfUnitTestCase extends \WP_UnitTestCase {

	protected $template_tmp_location;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data = \GPDFAPI::get_data_class();
		wp_mkdir_p( $data->template_tmp_location );
		$this->template_tmp_location = $data->template_tmp_location;

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function tearDown() {
		$data = \GPDFAPI::get_data_class();
		$misc = \GPDFAPI::get_misc_class();
		$misc->rmdir( $data->template_tmp_location );

		parent::tearDown();
	}
}
