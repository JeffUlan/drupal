/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.MediaLibraryHover = {
    attach: function attach(context) {
      $('.media-library-item .js-click-to-select-trigger,.media-library-item .js-click-to-select-checkbox', context).once('media-library-item-hover').on('mouseover mouseout', function (_ref) {
        var currentTarget = _ref.currentTarget,
            type = _ref.type;

        $(currentTarget).closest('.media-library-item').toggleClass('is-hover', type === 'mouseover');
      });
    }
  };

  Drupal.behaviors.MediaLibraryFocus = {
    attach: function attach(context) {
      $('.media-library-item .js-click-to-select-checkbox input', context).once('media-library-item-focus').on('focus blur', function (_ref2) {
        var currentTarget = _ref2.currentTarget,
            type = _ref2.type;

        $(currentTarget).closest('.media-library-item').toggleClass('is-focus', type === 'focus');
      });
    }
  };

  Drupal.behaviors.MediaLibrarySelectAll = {
    attach: function attach(context) {
      var $view = $('.media-library-view', context).once('media-library-select-all');
      if ($view.length && $view.find('.media-library-item').length) {
        var $checkbox = $('<input type="checkbox" class="form-checkbox" />').on('click', function (_ref3) {
          var currentTarget = _ref3.currentTarget;

          var $checkboxes = $(currentTarget).closest('.media-library-view').find('.media-library-item input[type="checkbox"]');
          $checkboxes.prop('checked', $(currentTarget).prop('checked')).trigger('change');

          var announcement = $(currentTarget).prop('checked') ? Drupal.t('Zero items selected') : Drupal.t('All @count items selected', {
            '@count': $checkboxes.length
          });
          Drupal.announce(announcement);
        });
        var $label = $('<label class="media-library-select-all"></label>').text(Drupal.t('Select all media'));
        $label.prepend($checkbox);
        $view.find('.media-library-item').first().before($label);
      }
    }
  };
})(jQuery, Drupal);