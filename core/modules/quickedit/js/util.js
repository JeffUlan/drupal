/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.quickedit.util = Drupal.quickedit.util || {};
  Drupal.quickedit.util.constants = {};
  Drupal.quickedit.util.constants.transitionEnd = 'transitionEnd.quickedit webkitTransitionEnd.quickedit transitionend.quickedit msTransitionEnd.quickedit oTransitionEnd.quickedit';

  Drupal.quickedit.util.buildUrl = function (id, urlFormat) {
    const parts = id.split('/');
    return Drupal.formatString(decodeURIComponent(urlFormat), {
      '!entity_type': parts[0],
      '!id': parts[1],
      '!field_name': parts[2],
      '!langcode': parts[3],
      '!view_mode': parts[4]
    });
  };

  Drupal.quickedit.util.networkErrorModal = function (title, message) {
    const $message = $(`<div>${message}</div>`);
    const networkErrorModal = Drupal.dialog($message.get(0), {
      title,
      dialogClass: 'quickedit-network-error',
      buttons: [{
        text: Drupal.t('OK'),

        click() {
          networkErrorModal.close();
        },

        primary: true
      }],

      create() {
        $(this).parent().find('.ui-dialog-titlebar-close').remove();
      },

      close(event) {
        $(event.target).remove();
      }

    });
    networkErrorModal.showModal();
  };

  Drupal.quickedit.util.form = {
    load(options, callback) {
      const fieldID = options.fieldID;
      const formLoaderAjax = Drupal.ajax({
        url: Drupal.quickedit.util.buildUrl(fieldID, Drupal.url('quickedit/form/!entity_type/!id/!field_name/!langcode/!view_mode')),
        submit: {
          nocssjs: options.nocssjs,
          reset: options.reset
        },

        error(xhr, url) {
          const fieldLabel = Drupal.quickedit.metadata.get(fieldID, 'label');
          const message = Drupal.t('Could not load the form for <q>@field-label</q>, either due to a website problem or a network connection problem.<br>Please try again.', {
            '@field-label': fieldLabel
          });
          Drupal.quickedit.util.networkErrorModal(Drupal.t('Network problem!'), message);
          const fieldModel = Drupal.quickedit.app.model.get('activeField');
          fieldModel.set('state', 'candidate');
        }

      });

      formLoaderAjax.commands.quickeditFieldForm = function (ajax, response, status) {
        callback(response.data, ajax);
        Drupal.ajax.instances[this.instanceIndex] = null;
      };

      formLoaderAjax.execute();
    },

    ajaxifySaving(options, $submit) {
      const settings = {
        url: $submit.closest('form').attr('action'),
        setClick: true,
        event: 'click.quickedit',
        progress: false,
        submit: {
          nocssjs: options.nocssjs,
          other_view_modes: options.other_view_modes
        },

        success(response, status) {
          Object.keys(response || {}).forEach(i => {
            if (response[i].command && this.commands[response[i].command]) {
              this.commands[response[i].command](this, response[i], status);
            }
          });
        },

        base: $submit.attr('id'),
        element: $submit[0]
      };
      return Drupal.ajax(settings);
    },

    unajaxifySaving(ajax) {
      $(ajax.element).off('click.quickedit');
    }

  };
})(jQuery, Drupal);