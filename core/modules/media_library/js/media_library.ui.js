/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, window) {
  Drupal.MediaLibrary = {
    currentSelection: []
  };

  Drupal.AjaxCommands.prototype.updateMediaLibrarySelection = function (ajax, response, status) {
    Object.values(response.mediaIds).forEach(function (value) {
      Drupal.MediaLibrary.currentSelection.push(value);
    });
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

  Drupal.behaviors.MediaLibraryTabs = {
    attach: function attach(context) {
      var $menu = $('.js-media-library-menu');
      $menu.find('a', context).once('media-library-menu-item').on('keypress', function (e) {
        if (e.which === 32) {
          e.preventDefault();
          e.stopPropagation();
          $(e.currentTarget).trigger('click');
        }
      }).on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var ajaxObject = Drupal.ajax({
          wrapper: 'media-library-content',
          url: e.currentTarget.href,
          dialogType: 'ajax',
          progress: {
            type: 'fullscreen',
            message: Drupal.t('Please wait...')
          }
        });

        ajaxObject.success = function (response, status) {
          var _this = this;

          if (this.progress.element) {
            $(this.progress.element).remove();
          }
          if (this.progress.object) {
            this.progress.object.stopMonitoring();
          }
          $(this.element).prop('disabled', false);

          Object.keys(response || {}).forEach(function (i) {
            if (response[i].command && _this.commands[response[i].command]) {
              _this.commands[response[i].command](_this, response[i], status);
            }
          });

          $('#media-library-content :tabbable:first').focus();

          this.settings = null;
        };
        ajaxObject.execute();

        $menu.find('.active-tab').remove();
        $menu.find('a').removeClass('active');
        $(e.currentTarget).addClass('active').html(Drupal.t('<span class="visually-hidden">Show </span>@title<span class="visually-hidden"> media</span><span class="active-tab visually-hidden"> (selected)</span>', { '@title': $(e.currentTarget).data('title') }));

        Drupal.announce(Drupal.t('Showing @title media.', {
          '@title': $(e.currentTarget).data('title')
        }));
      });
    }
  };

  Drupal.behaviors.MediaLibraryViewsDisplay = {
    attach: function attach(context) {
      var $view = $(context).hasClass('.js-media-library-view') ? $(context) : $('.js-media-library-view', context);

      $view.closest('.views-element-container').attr('id', 'media-library-view');

      $('.views-display-link-widget, .views-display-link-widget_table', context).once('media-library-views-display-link').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $link = $(e.currentTarget);

        var loadingAnnouncement = '';
        var displayAnnouncement = '';
        var focusSelector = '';
        if ($link.hasClass('views-display-link-widget')) {
          loadingAnnouncement = Drupal.t('Loading grid view.');
          displayAnnouncement = Drupal.t('Changed to grid view.');
          focusSelector = '.views-display-link-widget';
        } else if ($link.hasClass('views-display-link-widget_table')) {
          loadingAnnouncement = Drupal.t('Loading table view.');
          displayAnnouncement = Drupal.t('Changed to table view.');
          focusSelector = '.views-display-link-widget_table';
        }

        var ajaxObject = Drupal.ajax({
          wrapper: 'media-library-view',
          url: e.currentTarget.href,
          dialogType: 'ajax',
          progress: {
            type: 'fullscreen',
            message: loadingAnnouncement || Drupal.t('Please wait...')
          }
        });

        if (displayAnnouncement || focusSelector) {
          var success = ajaxObject.success;
          ajaxObject.success = function (response, status) {
            success.bind(this)(response, status);

            if (focusSelector) {
              $(focusSelector).focus();
            }

            if (displayAnnouncement) {
              Drupal.announce(displayAnnouncement);
            }
          };
        }

        ajaxObject.execute();

        if (loadingAnnouncement) {
          Drupal.announce(loadingAnnouncement);
        }
      });
    }
  };

  Drupal.behaviors.MediaLibraryItemSelection = {
    attach: function attach(context, settings) {
      var $form = $('.js-media-library-views-form, .js-media-library-add-form', context);
      var currentSelection = Drupal.MediaLibrary.currentSelection;

      if (!$form.length) {
        return;
      }

      var $mediaItems = $('.js-media-library-item input[type="checkbox"]', $form);

      function disableItems($items) {
        $items.prop('disabled', true).closest('.js-media-library-item').addClass('media-library-item--disabled');
      }

      function enableItems($items) {
        $items.prop('disabled', false).closest('.js-media-library-item').removeClass('media-library-item--disabled');
      }

      function updateSelectionCount(remaining) {
        var selectItemsText = remaining < 0 ? Drupal.formatPlural(currentSelection.length, '1 item selected', '@count items selected') : Drupal.formatPlural(remaining, '@selected of @count item selected', '@selected of @count items selected', {
          '@selected': currentSelection.length
        });

        $('.js-media-library-selected-count').html(selectItemsText);
      }

      $mediaItems.once('media-item-change').on('change', function (e) {
        var id = e.currentTarget.value;

        var position = currentSelection.indexOf(id);
        if (e.currentTarget.checked) {
          if (position === -1) {
            currentSelection.push(id);
          }
        } else if (position !== -1) {
          currentSelection.splice(position, 1);
        }

        $form.find('#media-library-modal-selection').val(currentSelection.join()).trigger('change');

        $('.js-media-library-add-form-current-selection').val(currentSelection.join());
      });

      $('#media-library-modal-selection', $form).once('media-library-selection-change').on('change', function (e) {
        updateSelectionCount(settings.media_library.selection_remaining);

        if (currentSelection.length === settings.media_library.selection_remaining) {
          disableItems($mediaItems.not(':checked'));
          enableItems($mediaItems.filter(':checked'));
        } else {
          enableItems($mediaItems);
        }
      });

      currentSelection.forEach(function (value) {
        $form.find('input[type="checkbox"][value="' + value + '"]').prop('checked', true).trigger('change');
      });

      $(window).once('media-library-selection-info').on('dialog:aftercreate', function () {
        var $buttonPane = $('.media-library-widget-modal .ui-dialog-buttonpane');
        if (!$buttonPane.length) {
          return;
        }
        $buttonPane.append(Drupal.theme('mediaLibrarySelectionCount'));
        updateSelectionCount(settings.media_library.selection_remaining);
      });
    }
  };

  Drupal.behaviors.MediaLibraryModalClearSelection = {
    attach: function attach() {
      $(window).once('media-library-clear-selection').on('dialog:afterclose', function () {
        Drupal.MediaLibrary.currentSelection = [];
      });
    }
  };

  Drupal.theme.mediaLibrarySelectionCount = function () {
    return '<div class="media-library-selected-count js-media-library-selected-count" role="status" aria-live="polite" aria-atomic="true"></div>';
  };
})(jQuery, Drupal, window);