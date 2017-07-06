/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {
  var dateFormats = drupalSettings.dateFormats;

  Drupal.behaviors.dateFormat = {
    attach: function attach(context) {
      var $context = $(context);
      var $source = $context.find('[data-drupal-date-formatter="source"]').once('dateFormat');
      var $target = $context.find('[data-drupal-date-formatter="preview"]').once('dateFormat');
      var $preview = $target.find('em');

      if (!$source.length || !$target.length) {
        return;
      }

      function dateFormatHandler(e) {
        var baseValue = $(e.target).val() || '';
        var dateString = baseValue.replace(/\\?(.?)/gi, function (key, value) {
          return dateFormats[key] ? dateFormats[key] : value;
        });

        $preview.html(dateString);
        $target.toggleClass('js-hide', !dateString.length);
      }

      $source.on('keyup.dateFormat change.dateFormat input.dateFormat', dateFormatHandler).trigger('keyup');
    }
  };
})(jQuery, Drupal, drupalSettings);