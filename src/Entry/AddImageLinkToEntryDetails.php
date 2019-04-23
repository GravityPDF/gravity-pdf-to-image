<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\Image\ImageUrl;

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
 * Class AddImageLinkToEntryDetails
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 */
class AddImageLinkToEntryDetails {

	/**
	 * @since 1.0
	 */
	public function init() {
		add_action( 'gfpdf_entry_detail_post_pdf_links_markup', [ $this, 'add_image_link_to_entry_details' ] );
	}

	/**
	 * If the PDF is configured, add it as an option to the Entry Details page
	 *
	 * @param array $pdf
	 *
	 * @since 1.0
	 */
	public function add_image_link_to_entry_details( $pdf ) {
		if ( ! empty( $pdf['settings']['pdf_to_image_toggle'] ) ) {
			echo sprintf(
				'<a href="%s" class="button" target="_blank">%s</a>',
				ImageUrl::get( $pdf['settings']['id'], $pdf['entry_id'], $pdf['settings']['pdf_to_image_page'] ),
				__( 'Image', 'gravity-pdf-to-image' )
			);
		}
	}
}