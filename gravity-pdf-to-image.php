<?php

/**
 * Plugin Name:     Gravity PDF to Image
 * Plugin URI:      https://gravitypdf.com/shop/gravity-pdf-to-image-add-on/
 * Description:     Convert Gravity PDF generated documents to images
 * Author:          Gravity PDF
 * Author URI:      https://gravitypdf.com
 * Text Domain:     gravity-pdf-to-image
 * Domain Path:     /languages
 * Version:         1.0.0
 */

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

define( 'GFPDF_PDF_TO_IMAGE_FILE', __FILE__ );
define( 'GFPDF_PDF_TO_IMAGE_VERSION', '1.0.0' );

/**
 * Class Gpdf_To_Images_Checks
 *
 * @since 1.0
 */
class Gpdf_To_Image_Checks {

	/**
	 * Holds any blocker error messages stopping plugin running
	 *
	 * @var array
	 *
	 * @since 1.0
	 */
	private $notices = [];

	/**
	 * @var string
	 *
	 * @since 1.0
	 */
	private $required_gravitypdf_version = '5.1.5';

	/**
	 * Run our pre-checks and if it passes bootstrap the plugin
	 *
	 * @since 1.0
	 */
	public function init() {

		/* Test the minimum version requirements are met */
		$this->check_gravitypdf_version();
		$this->check_imagick();

		/* Check if any errors were thrown, enqueue them and exit early */
		if ( count( $this->notices ) > 0 ) {
			add_action( 'admin_notices', [ $this, 'display_notices' ] );

			return;
		}

		add_action(
			'gfpdf_fully_loaded',
			function() {
				require_once __DIR__ . '/src/bootstrap.php';
			}
		);
	}

	/**
	 * Check if the current version of Gravity PDF is compatible with this add-on
	 *
	 * @since 1.0
	 */
	public function check_gravitypdf_version() {

		/* Check if the Gravity PDF Minimum version requirements are met */
		if ( defined( 'PDF_EXTENDED_VERSION' ) &&
		     version_compare( PDF_EXTENDED_VERSION, $this->required_gravitypdf_version, '>=' )
		) {
			return;
		}

		/* Throw error */
		$this->notices[] = sprintf( esc_html__( 'Gravity PDF Version %s or higher is required to use this add-on. Please upgrade Gravity PDF to the latest version.', 'gravity-pdf-to-image' ), $this->required_gravitypdf_version );
	}

	/**
	 * Do a deep check of all Imagick functionality being utilised to verify it will successfully run on host
	 *
	 * @since 1.0
	 */
	public function check_imagick() {

		if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) ) {
			$this->notices[] = sprintf( esc_html__( 'The PHP Extension Imagick could not be detected. Contact your web hosting provider to fix. %1$sGet more info%2$s.', 'gravity-forms-pdf-extended' ), '<a href="#php-imagick">', '</a>' );

			return;
		}

		if ( version_compare( phpversion( 'imagick' ), '2.2.0', '<' ) ) {
			$this->notices[] = sprintf( esc_html__( 'You are running an outdated version of the PHP Extension Imagick. Contact your web hosting provider to update. %3$sGet more info%4$s.', 'gravity-forms-pdf-extended' ), '<a href="#php-imagick-version">', '</a>' );
		}

		$required_methods = [
			'setResolution',
			'readImage',
			'resetIterator',
			'appendImages',
			'valid',
			'setFilename',
			'setImageFormat',
			'setImageCompressionQuality',
			'setImageCompression',
			'getImageFormat',
			'getImageBlob',
			'getFilename',
			'getImageWidth',
			'getImageHeight',
			'resizeImage',
			'cropImage',
			'getImageColorspace',
			'getImageProfiles',
			'profileImage',
			'stripImage',
		];

		if ( ! defined( 'Imagick::COMPRESSION_JPEG' ) || ! defined( 'Imagick::FILTER_LANCZOS' ) || ! defined( 'Imagick::COLORSPACE_CMYK' ) ) {
			$this->notices[] = sprintf( esc_html__( 'You are running an outdated version of the PHP Extension Imagick. Contact your web hosting provider to update. %3$sGet more info%4$s.', 'gravity-forms-pdf-extended' ), '<a href="#php-imagick-version">', '</a>' );
		}

		$required_methods = array_map( 'strtolower', $required_methods );
		$class_methods    = array_map( 'strtolower', get_class_methods( 'Imagick' ) );
		if ( array_diff( $required_methods, $class_methods ) ) {
			$this->notices[] = sprintf( esc_html__( 'You are running an outdated version of the PHP Extension Imagick. Contact your web hosting provider to update. %3$sGet more info%4$s.', 'gravity-forms-pdf-extended' ), '<a href="#php-imagick-version">', '</a>' );
		}

		$required_formats = [
			'pdf',
			'pdfa',
			'jpeg',
			'jpg',
		];

		$supported_formats = array_map( 'strtolower', Imagick::queryformats() );

		if ( $missing_formats = array_diff( $required_formats, $supported_formats ) ) {
			$this->notices[] = sprintf( esc_html__( 'The PHP Extension Imagick does not support the file format(s): %1$s. %2$sGet more info%3$s.', 'gravity-forms-pdf-extended' ), implode( ', ', $missing_formats ), '<a href="#php-imagick-file-formats">', '</a>' );
		}
	}

	/**
	 * Helper function to easily display error messages
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function display_notices() {
		?>
		<div class="error">
			<p>
				<strong><?php esc_html_e( 'Gravity PDF to Image Installation Problem', 'gravity-pdf-to-image' ); ?></strong>
			</p>

			<p><?php esc_html_e( 'The minimum requirements for the Gravity PDF to Image plugin have not been met. Please fix the issue(s) below to continue:', 'gravity-pdf-to-image' ); ?></p>
			<ul style="padding-bottom: 0.5em">
				<?php foreach ( $this->notices as $notice ): ?>
					<li style="padding-left: 20px;list-style: inside"><?php echo $notice; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

/* Initialise the software */
add_action(
	'plugins_loaded',
	function() {
		$gravitypdf_to_image = new Gpdf_To_Image_Checks();
		$gravitypdf_to_image->init();
	}
);
