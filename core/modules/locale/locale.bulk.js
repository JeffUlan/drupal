/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.importLanguageCodeSelector = {
    attach: function attach(context, settings) {
      var form = once('autodetect-lang', '#locale-translate-import-form');

      if (form.length) {
        var $form = $(form);
        var $langcode = $form.find('.langcode-input');
        $form.find('.file-import-input').on('change', function () {
          var matches = $(this).val().match(/([^.][.]*)([\w-]+)\.po$/);

          if (matches && $langcode.find("option[value=\"".concat(matches[2], "\"]")).length) {
            $langcode.val(matches[2]);
          }
        });
      }
    }
  };
})(jQuery, Drupal);