/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.negotiationLanguage = {
    attach: function attach() {
      var $configForm = $('#language-negotiation-configure-form');
      var inputSelector = 'input[name$="[configurable]"]';

      function toggleTable(checkbox) {
        var $checkbox = $(checkbox);

        $checkbox.closest('.table-language-group').find('table, .tabledrag-toggle-weight').toggle($checkbox.prop('checked'));
      }

      $configForm.once('negotiation-language-admin-bind').on('change', inputSelector, function (event) {
        toggleTable(event.target);
      });

      $configForm.find(inputSelector + ':not(:checked)').each(function (index, element) {
        toggleTable(element);
      });
    }
  };
})(jQuery, Drupal);