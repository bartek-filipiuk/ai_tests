(function ($, Drupal) {
  Drupal.behaviors.saladTranscriptionForm = {
    attach: function (context, settings) {
      var $fileUrl = $('#file-url-field', context);
      var $submit = $('#edit-submit', context);

      $fileUrl.on('change', function() {
        $submit.prop('disabled', !$(this).val());
      });

      // Initial state
      $submit.prop('disabled', !$fileUrl.val());
    }
  };
})(jQuery, Drupal);
