(function ($, Drupal) {
  Drupal.behaviors.transcriptionView = {
    attach: function (context, settings) {
      once('transcriptionView', '#copy-button', context).forEach(function (element) {
        $(element).on('click', function (e) {
          e.preventDefault();
          var copyText = document.getElementById("full-text");
          copyText.select();
          copyText.setSelectionRange(0, 99999); // For mobile devices
          document.execCommand("copy");
          alert(Drupal.t('Text copied to clipboard'));
        });
      });
    }
  };
})(jQuery, Drupal);
