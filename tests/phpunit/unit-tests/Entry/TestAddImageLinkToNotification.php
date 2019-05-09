<?php

namespace GFPDF\Plugins\PdfToImage\Entry;

use GFPDF\Plugins\PdfToImage\GpdfUnitTestCase;
use GFPDF\Plugins\PdfToImage\Image\Common;
use GFPDF\Plugins\PdfToImage\Pdf\PdfSecurity;

/**
 * Class TestAddImageToNotification
 *
 * @package GFPDF\Plugins\PdfToImage\Entry
 *
 * @group   Entry
 */
class TestAddImageToNotification extends GpdfUnitTestCase {

	/**
	 * @var AddImageLinkToNotificationWithGetter
	 */
	protected $class;

	/**
	 * @var string
	 */
	protected $template_tmp_location;

	/**
	 * @since 1.0
	 */
	public function setUp() {
		$data                        = \GPDFAPI::get_data_class();
		$this->template_tmp_location = $data->template_tmp_location;

		$this->class = new  AddImageLinkToNotificationWithGetter(
			new Common( new PdfSecurity(), $this->template_tmp_location ),
			new PdfSecurity()
		);

		$this->class->set_logger( \GPDFAPI::get_log_class() );

		parent::setUp();
	}

	/**
	 * @since 1.0
	 */
	public function test_init() {
		$this->class->init();

		$this->assertSame( 10, has_action( 'gfpdf_post_generate_and_save_pdf_notification', [ $this->class, 'register_pdf_to_convert_to_image' ] ) );

		$this->assertSame( 10000, has_action( 'gform_notification', [ $this->class, 'maybe_attach_files_to_notifications' ] ) );
	}

	/**
	 * @since 1.0
	 */
	public function test_register_pdf_to_convert_to_image() {

		$form  = [ 'id' => 1 ];
		$entry = [
			'id'      => 1,
			'form_id' => 1,
		];

		$pdf = [
			'id'       => '12345678',
			'filename' => 'sample',

			'security'            => 0,
			'pdf_to_image_toggle' => 0,
			'pdf_to_image_page'   => 1,
		];

		$notification = [
			'id'   => 'abcdefgh',
			'name' => 'User Notification',
		];

		$this->class->register_pdf_to_convert_to_image( $form, $entry, $pdf, $notification );
		$this->assertCount( 0, $this->class->get_settings() );

		$pdf['pdf_to_image_toggle'] = 1;
		$this->class->register_pdf_to_convert_to_image( $form, $entry, $pdf, $notification );
		$this->assertCount( 1, $this->class->get_settings() );

		$pdf['pdf_to_image_notifications'] = 'PDF';
		$this->class->register_pdf_to_convert_to_image( $form, $entry, $pdf, $notification );
		$this->assertCount( 0, $this->class->get_settings() );

		unset( $pdf['pdf_to_image_notifications'] );

		$pdf['security'] = 'Yes';
		$pdf['password'] = 'test';
		$this->class->register_pdf_to_convert_to_image( $form, $entry, $pdf, $notification );
		$this->assertCount( 0, $this->class->get_settings() );
	}

	/**
	 * @since 1.0
	 */
	public function test_maybe_attach_files_to_notifications() {

		/* Verify attachment with cached copy */
		$form  = [ 'id' => 1 ];
		$entry = [
			'id'      => 1,
			'form_id' => 1,
		];

		$pdf = [
			'id'       => '12345678',
			'filename' => 'sample',

			'security'            => 0,
			'pdf_to_image_toggle' => 1,
			'pdf_to_image_page'   => 1,
		];

		$notification = [
			'id'          => 'abcdefgh',
			'name'        => 'User Notification',
			'attachments' => [
				__DIR__ . '/../pdf/11/sample.pdf',
			],
		];

		/* Verify it's skipped */
		$this->assertCount( 1, $this->class->maybe_attach_files_to_notifications( $notification, $form, $entry )['attachments'] );

		/* Verify it's attached with cached copy */
		wp_mkdir_p( $this->template_tmp_location . '11' );
		copy( __DIR__ . '/../../assets/pdf/sample.pdf', $this->template_tmp_location . '11/sample.pdf' );
		copy( __DIR__ . '/../../assets/image/sample.jpg', $this->template_tmp_location . '11/sample.jpg' );

		$this->class->register_pdf_to_convert_to_image( $form, $entry, $pdf, $notification );
		$results = $this->class->maybe_attach_files_to_notifications( $notification, $form, $entry );

		$this->assertCount( 2, $results['attachments'] );
		$this->assertStringEndsWith( 'sample.jpg', $results['attachments'][1] );

		@unlink( $this->template_tmp_location . '11/sample.jpg' );

		/* Verify a new copy is generated */
		$this->class->register_pdf_to_convert_to_image( $form, $entry, $pdf, $notification );
		$results = $this->class->maybe_attach_files_to_notifications( $notification, $form, $entry );

		$this->assertCount( 2, $results['attachments'] );
		$this->assertStringEndsWith( 'sample.jpg', $results['attachments'][1] );

		@unlink( $this->template_tmp_location . '11/sample.jpg' );
	}

	/**
	 * @since 1.0
	 */
	public function test_handle_attachments() {
		$pdf = [
			'id'       => '12345678',
			'filename' => 'sample',

			'security'            => 0,
			'pdf_to_image_toggle' => 1,
			'pdf_to_image_page'   => 1,
		];

		$attachments = [
			'path/to/file/sample.pdf',
		];

		/* Check the image is added */
		$this->class->add_settings( [ $pdf ] );
		$this->assertCount( 2, $this->class->handle_attachments( $attachments, 'path/to/file/sample.jpg', $attachments[0] ) );

		/* Check the PDF is removed */
		$pdf['pdf_to_image_notifications'] = 'Image';
		$this->class->add_settings( [ $pdf ] );
		$this->assertCount( 1, $this->class->handle_attachments( $attachments, 'path/to/file/sample.jpg', $attachments[0] ) );
	}

}

class AddImageLinkToNotificationWithGetter extends AddImageToNotification {
	public function add_settings( $settings ) {
		$this->settings = $settings;
	}

	public function get_settings() {
		return $this->settings;
	}
}
