<?php

namespace GFPDF\Plugins\PdfToImage;

use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Plugins\PdfToImage\Entry\AddImageLinkToEntryDetails;
use GFPDF\Plugins\PdfToImage\Entry\AddImageLinkToEntryList;
use GFPDF\Plugins\PdfToImage\Entry\AddImageToNotification;
use GFPDF\Plugins\PdfToImage\Entry\AlwaysSaveImage;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Image\FlushCache;
use GFPDF\Plugins\PdfToImage\Options\AddPdfToImageFields;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;
use GFPDF\Plugins\PdfToImage\Permalink\Register;
use GFPDF\Plugins\PdfToImage\Image\Listener;
use GFPDF\Plugins\PdfToImage\Shortcode\AddImageShortcodeToPdfList;
use GFPDF\Plugins\PdfToImage\Shortcode\GravityPdfImage;

use GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater;
use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Singleton;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GPDFAPI;

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

/* Load Composer */
require_once( __DIR__ . '/../vendor/autoload.php' );

/**
 * Class Bootstrap
 *
 * @package GFPDF\Plugins\PdfToImage
 */
class Bootstrap extends Helper_Abstract_Addon {

	/**
	 * Initialise the plugin classes and pass them to our parent class to
	 * handle the rest of the bootstrapping (licensing ect)
	 *
	 * @param array $classes An array of classes to store in our singleton
	 *
	 * @since 1.0
	 */
	public function init( $classes = [] ) {

		/* Setup a temporary location for the PDF to Images files */
		$this->data->pdf_to_images_tmp_location = $this->data->template_tmp_location . 'pdf-to-images/';

		$pdf_security = new PdfSecurity();
		$image_common = new Common( $pdf_security, $this->data->pdf_to_images_tmp_location );

		$shortcode = new GravityPdfImage( GPDFAPI::get_form_class(), $this->log, $this->options, GPDFAPI::get_misc_class(), new Helper_Url_Signer() );
		$shortcode->set_debug_mode( $this->options->get_option( 'debug_mode', 'No' ) === 'Yes' );
		$shortcode->set_image( $image_common );

		/* Register our classes and pass back up to the parent initialiser */
		$classes = array_merge(
			$classes,
			[
				new Register(),
				new Listener( $image_common, $pdf_security ),
				new AddPdfToImageFields( GPDFAPI::get_misc_class(), GPDFAPI::get_options_class() ),
				new AddImageLinkToEntryList( $image_common ),
				new AddImageLinkToEntryDetails( $image_common ),
				new AddImageToNotification( $image_common, $pdf_security ),
				new AddImageShortcodeToPdfList( $image_common ),
				$shortcode,
				new AlwaysSaveImage( $image_common, $pdf_security ),
				new FlushCache( $image_common ),
			]
		);

		/* Run the setup */
		parent::init( $classes );
	}

	/**
	 * @return string
	 */
	public function get_short_name() {
		return $this->get_name();
	}

	/**
	 * Check the plugin's license is active and initialise the EDD Updater
	 *
	 * @since 1.0
	 */
	public function plugin_updater() {

		$license_info = $this->get_license_info();

		new EDD_SL_Plugin_Updater(
			$this->data->store_url,
			$this->get_main_plugin_file(),
			[
				'version'   => $this->get_version(),
				'license'   => $license_info['license'],
				'item_name' => $this->get_short_name(),
				'author'    => $this->get_author(),
				'beta'      => false,
			]
		);

		$this->log->notice( sprintf( '%s plugin updater initialised', $this->get_name() ) );
	}
}

/* Use the filter below to replace and extend our Bootstrap class if needed */
$name = 'Gravity PDF to Image';
$slug = 'gravity-pdf-to-image';

$plugin = apply_filters(
	'gfpdf_pdf_to_image_initialise',
	new Bootstrap(
		$slug,
		$name,
		'Gravity PDF',
		GFPDF_PDF_TO_IMAGE_VERSION,
		GFPDF_PDF_TO_IMAGE_FILE,
		GPDFAPI::get_data_class(),
		GPDFAPI::get_options_class(),
		new Helper_Singleton(),
		new Helper_Logger( $slug, $name ),
		new Helper_Notices()
	)
);

$plugin->set_edd_download_id( '31130' );
$plugin->set_addon_documentation_slug( 'shop-plugin-pdf-to-image-add-on' );
$plugin->init();

/* Use the action below to access our Bootstrap class, and any singletons saved in $plugin->singleton */
do_action( 'gfpdf_pdf_to_image_bootrapped', $plugin );
