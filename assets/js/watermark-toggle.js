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
    $('tr.gfpdf-watermark').show()
  }

  /**
   * Hide the Watermark Text fields
   *
   * @since 1.0
   */
  function hide () {
    $('tr.gfpdf-watermark').hide()
  }

  $(function () {
    var checkbox = $('#gfpdf_settings\\[watermark_toggle\\]')
    checkbox.click(function () {
      $(this).is(':checked') ? show() : hide()
    })

    if (checkbox.is(':checked')) {
      show()
    }
  })
})(jQuery)