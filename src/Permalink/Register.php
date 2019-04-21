<?php

namespace GFPDF\Plugins\PdfToImage\Permalink;

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

/*
	This file is part of Gravity PDF to Image.

	Copyright (c) 2019, Blue Liquid Designs

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Class Register
 *
 * @package GFPDF\Plugins\PdfToImage\Permalink
 */
class Register {

	/**
	 * @since 1.0
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_permalink' ], 5 ); /* run before Gravity PDF registers its endpoints */

		add_filter( 'query_vars', [ $this, 'maybe_register_rewrite_tags' ], 20 );
	}

	/**
	 * Register the custom image endpoint for the PDF
	 *
	 * @Internal `page` is already a pre-generated tag in WordPress
	 *
	 * @since    1.0
	 */
	public function register_permalink() {
		global $wp_rewrite;

		/** @var \GFPDF\Model\Model_Install $install */
		$install = \GPDFAPI::get_mvc_class( 'Model_Install' );

		/* Get image permalink */
		$base_permalink  = str_replace( '?(download)?/?', '', $install->get_permalink_regex() );

		$image_base = $base_permalink . '(img)/?';
		$image_permalink = $image_base . '(-?[0-9]+)/?(download)?/?';

		/* Create two regex rules to account for users with "index.php" in the URL */
		$query = [
			'^' . $image_base,
			'^' . $wp_rewrite->index . '/' . $image_base,
			'^' . $image_permalink,
			'^' . $wp_rewrite->index . '/' . $image_permalink,
		];

		$rewrite_to = 'index.php?gpdf=1&pid=$matches[1]&lid=$matches[2]&action=$matches[3]&page=$matches[4]&sub_action=$matches[5]';

		/* Add our endpoint */
		add_rewrite_rule( $query[0], $rewrite_to, 'top' );
		add_rewrite_rule( $query[1], $rewrite_to, 'top' );

		/* check to see if we need to flush the rewrite rules */
		$install->maybe_flush_rewrite_rules( $query );
	}

	/**
	 * @param array $tags
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function maybe_register_rewrite_tags( $tags ) {

		if ( in_array( 'gpdf', $tags ) ) {
			$tags[] = 'sub_action';
		}

		return $tags;
	}
}