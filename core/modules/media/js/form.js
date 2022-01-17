/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.mediaFormSummaries = {
    attach(context) {
      $(context).find('.media-form-author').drupalSetSummary(context => {
        const nameInput = context.querySelector('.field--name-uid input');
        const name = nameInput && nameInput.value;
        const dateInput = context.querySelector('.field--name-created input');
        const date = dateInput && dateInput.value;

        if (name && date) {
          return Drupal.t('By @name on @date', {
            '@name': name,
            '@date': date
          });
        }

        if (name) {
          return Drupal.t('By @name', {
            '@name': name
          });
        }

        if (date) {
          return Drupal.t('Authored on @date', {
            '@date': date
          });
        }
      });
    }

  };
})(jQuery, Drupal);