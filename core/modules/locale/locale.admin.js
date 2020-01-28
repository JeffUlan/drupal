/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.localeTranslateDirty = {
    attach: function attach() {
      var $form = $('#locale-translate-edit-form').once('localetranslatedirty');
      if ($form.length) {
        $form.one('formUpdated.localeTranslateDirty', 'table', function () {
          var $marker = $(Drupal.theme('localeTranslateChangedWarning')).hide();
          $(this).addClass('changed').before($marker);
          $marker.fadeIn('slow');
        });

        $form.on('formUpdated.localeTranslateDirty', 'tr', function () {
          var $row = $(this);
          var $rowToMark = $row.once('localemark');
          var marker = Drupal.theme('localeTranslateChangedMarker');

          $row.addClass('changed');

          if ($rowToMark.length) {
            $rowToMark.find('td:first-child .js-form-item').append(marker);
          }
        });
      }
    },
    detach: function detach(context, settings, trigger) {
      if (trigger === 'unload') {
        var $form = $('#locale-translate-edit-form').removeOnce('localetranslatedirty');
        if ($form.length) {
          $form.off('formUpdated.localeTranslateDirty');
        }
      }
    }
  };

  Drupal.behaviors.hideUpdateInformation = {
    attach: function attach(context, settings) {
      var $table = $('#locale-translation-status-form').once('expand-updates');
      if ($table.length) {
        var $tbodies = $table.find('tbody');

        $tbodies.on('click keydown', '.description', function (e) {
          if (e.keyCode && e.keyCode !== 13 && e.keyCode !== 32) {
            return;
          }
          e.preventDefault();
          var $tr = $(this).closest('tr');

          $tr.toggleClass('expanded');

          $tr.find('.locale-translation-update__prefix').text(function () {
            if ($tr.hasClass('expanded')) {
              return Drupal.t('Hide description');
            }

            return Drupal.t('Show description');
          });
        });
        $table.find('.requirements, .links').hide();
      }
    }
  };

  $.extend(Drupal.theme, {
    localeTranslateChangedMarker: function localeTranslateChangedMarker() {
      return '<abbr class="warning ajax-changed" title="' + Drupal.t('Changed') + '">*</abbr>';
    },
    localeTranslateChangedWarning: function localeTranslateChangedWarning() {
      return '<div class="clearfix messages messages--warning">' + Drupal.theme('localeTranslateChangedMarker') + ' ' + Drupal.t('Changes made in this table will not be saved until the form is submitted.') + '</div>';
    }
  });
})(jQuery, Drupal);