<?php

namespace GFPDF\Plugins\PdfToImage\Exception;

use InvalidArgumentException;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PdfToImageInvalidArgument
 *
 * @package GFPDF\Plugins\PdfToImage\Exception
 *
 * @since   1.0
 */
class PdfToImageInvalidArgument extends InvalidArgumentException {

}
