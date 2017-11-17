/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, _ref) {
  var ajax = _ref.ajax,
      behaviors = _ref.behaviors;

  behaviors.layoutBuilder = {
    attach: function attach(context) {
      $(context).find('.layout-builder--layout__region').sortable({
        items: '> .draggable',
        connectWith: '.layout-builder--layout__region',

        update: function update(event, ui) {
          if (ui.sender) {
            ajax({
              url: [ui.item.closest('[data-layout-update-url]').data('layout-update-url'), ui.sender.closest('[data-layout-delta]').data('layout-delta'), ui.item.closest('[data-layout-delta]').data('layout-delta'), ui.sender.data('region'), $(this).data('region'), ui.item.data('layout-block-uuid'), ui.item.prev('[data-layout-block-uuid]').data('layout-block-uuid')].filter(function (element) {
                return element !== undefined;
              }).join('/')
            }).execute();
          }
        }
      });
    }
  };
})(jQuery, Drupal);