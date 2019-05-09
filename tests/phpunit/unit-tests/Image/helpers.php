<?php

namespace GFPDF\Plugins\PdfToImage\Image;

/**
 * @package     Gravity PDF to Image
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

function wp_die( $string = '' ) {
	echo $string;
}

function header( $string ) {
	array_push( Header::$headers, $string );
}

/**
 * Check the magic bytes of the data and verify it is a JPG
 *
 * @param string $data
 *
 * @return bool
 */
function is_jpeg( $data ) {
	return ( bin2hex( $data[0] ) == 'ff' && bin2hex( $data[1] ) == 'd8' );
}

class Header {
	/**
	 * @var array
	 */
	static $headers = [];
}
