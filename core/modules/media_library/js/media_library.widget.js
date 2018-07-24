/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.MediaLibraryWidgetSortable = {
    attach: function attach(context) {
      $('.js-media-library-selection', context).once('media-library-sortable').sortable({
        tolerance: 'pointer',
        helper: 'clone',
        handle: '.js-media-library-item-preview',
        stop: function stop(_ref) {
          var target = _ref.target;

          $(target).children().each(function (index, child) {
            $(child).find('.js-media-library-item-weight').val(index);
          });
        }
      });
    }
  };

  Drupal.behaviors.MediaLibraryWidgetToggleWeight = {
    attach: function attach(context) {
      var strings = {
        show: Drupal.t('Show media item weights'),
        hide: Drupal.t('Hide media item weights')
      };
      $('.js-media-library-widget-toggle-weight', context).once('media-library-toggle').on('click', function (e) {
        e.preventDefault();
        $(e.currentTarget).toggleClass('active').text($(e.currentTarget).hasClass('active') ? strings.hide : strings.show).parent().find('.js-media-library-item-weight').parent().toggle();
      }).text(strings.show);
      $('.js-media-library-item-weight', context).once('media-library-toggle').parent().hide();
    }
  };

  Drupal.behaviors.MediaLibraryWidgetWarn = {
    attach: function attach(context) {
      $('.js-media-library-item a[href]', context).once('media-library-warn-link').on('click', function (e) {
        var message = Drupal.t('Unsaved changes to the form will be lost. Are you sure you want to leave?');
        var confirmation = window.confirm(message);
        if (!confirmation) {
          e.preventDefault();
        }
      });
    }
  };

  Drupal.behaviors.MediaLibraryWidgetRemaining = {
    attach: function attach(context, settings) {
      var $view = $('.js-media-library-view', context).once('media-library-remaining');
      $view.find('.js-media-library-item input[type="checkbox"]').on('change', function () {
        if (settings.media_library && settings.media_library.selection_remaining) {
          var $checkboxes = $view.find('.js-media-library-item input[type="checkbox"]');
          if ($checkboxes.filter(':checked').length === settings.media_library.selection_remaining) {
            $checkboxes.not(':checked').prop('disabled', true).closest('.js-media-library-item').addClass('media-library-item--disabled');
          } else {
            $checkboxes.prop('disabled', false).closest('.js-media-library-item').removeClass('media-library-item--disabled');
          }
        }
      });
    }
  };
})(jQuery, Drupal);