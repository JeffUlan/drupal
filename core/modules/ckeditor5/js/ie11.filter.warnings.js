/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

((Drupal, once, Modernizr) => {
  Drupal.behaviors.ckEditor5warn = {
    attach: function attach() {
      const isIE11 = Modernizr.mq('(-ms-high-contrast: active), (-ms-high-contrast: none)');
      const editorSelect = once('editor-ie11-warning', '[data-drupal-selector="filter-format-edit-form"] [data-drupal-selector="edit-editor-editor"], [data-drupal-selector="filter-format-add-form"] [data-drupal-selector="edit-editor-editor"]');

      if (typeof editorSelect[0] !== 'undefined') {
        const select = editorSelect[0];
        const selectMessageContainer = document.createElement('div');
        select.parentNode.after(selectMessageContainer, select);
        const selectMessages = new Drupal.Message(selectMessageContainer);
        const editorSettings = document.querySelector('#editor-settings-wrapper');

        const addIE11Warning = () => {
          selectMessages.add(Drupal.t('CKEditor 5 is not compatible with Internet Explorer. Text fields using CKEditor 5 will fall back to plain HTML editing without CKEditor for users of Internet Explorer.'), {
            type: 'warning',
            id: 'ie_11_warning'
          });

          if (isIE11) {
            selectMessages.add(Drupal.t('Text editor toolbar settings are not available in Internet Explorer. They will be available in other <a href="@supported-browsers">supported browsers</a>.', {
              '@supported-browsers': 'https://www.drupal.org/docs/system-requirements/browser-requirements'
            }), {
              type: 'error',
              id: 'ie_11_error'
            });
            editorSettings.hidden = true;
          }
        };

        const updateWarningStatus = () => {
          if (select.value === 'ckeditor5' && !select.hasAttribute('data-error-switching-to-ckeditor5')) {
            addIE11Warning();
          } else {
            if (selectMessages.select('ie_11_warning')) {
              selectMessages.remove('ie_11_warning');
            }

            if (selectMessages.select('ie_11_error')) {
              selectMessages.remove('ie_11_error');
            }
          }
        };

        updateWarningStatus();
        const editorSelectObserver = new MutationObserver(mutations => {
          for (let i = 0; i < mutations.length; i++) {
            const switchToCKEditor5Complete = mutations[i].type === 'attributes' && mutations[i].attributeName === 'disabled' && !select.disabled;
            const fixedErrorsPreventingSwitchToCKEditor5 = mutations[i].type === 'attributes' && mutations[i].attributeName === 'data-error-switching-to-ckeditor5' && !select.hasAttribute('data-error-switching-to-ckeditor5');

            if (switchToCKEditor5Complete || fixedErrorsPreventingSwitchToCKEditor5) {
              updateWarningStatus();
            }
          }
        });
        editorSelectObserver.observe(select, {
          attributes: true
        });
      }
    }
  };
})(Drupal, once, Modernizr);