/**
 * PDF Settings
 * Dependancies: jQuery
 */

(function ($) {

  /**
   * Show the Watermark Text fields
   *
   * @since 1.0
   */
  function show () {
    $('.gfpdf-pdf-to-image').show()
  }

  /**
   * Hide the Watermark Text fields
   *
   * @since 1.0
   */
  function hide () {
    $('.gfpdf-pdf-to-image').hide()
  }

  $(function () {
    var checkbox = $('#gfpdf_settings\\[pdf_to_image_toggle\\]')
    checkbox.click(function () {
      $(this).is(':checked') ? show() : hide()
    })

    if (checkbox.is(':checked')) {
      show()
    }
  })
})(jQuery)
