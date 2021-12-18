/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.mediaTypeFormSummaries = {
    attach(context) {
      const $context = $(context);
      $context.find('#edit-workflow').drupalSetSummary(context => {
        const vals = [];
        $(context).find('input[name^="options"]:checked').parent().each(function () {
          vals.push(Drupal.checkPlain($(this).find('label').text()));
        });

        if (!$(context).find('#edit-options-status').is(':checked')) {
          vals.unshift(Drupal.t('Not published'));
        }

        return vals.join(', ');
      });
      $(context).find('#edit-language').drupalSetSummary(context => {
        const vals = [];
        vals.push($(context).find('.js-form-item-language-configuration-langcode select option:selected').text());
        $(context).find('input:checked').next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });
        return vals.join(', ');
      });
    }

  };
})(jQuery, Drupal);