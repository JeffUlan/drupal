/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, debounce, once) {
  Drupal.behaviors.blockFilterByText = {
    attach(context, settings) {
      const $input = $(once('block-filter-text', 'input.block-filter-text'));
      const $table = $($input.attr('data-element'));
      let $filterRows;

      function filterBlockList(e) {
        const query = e.target.value.toLowerCase();

        function toggleBlockEntry(index, label) {
          const $row = $(label).parent().parent();
          const textMatch = label.textContent.toLowerCase().includes(query);
          $row.toggle(textMatch);
        }

        if (query.length >= 2) {
          $filterRows.each(toggleBlockEntry);
          Drupal.announce(Drupal.formatPlural($table.find('tr:visible').length - 1, '1 block is available in the modified list.', '@count blocks are available in the modified list.'));
        } else {
          $filterRows.each(function (index) {
            $(this).parent().parent().show();
          });
        }
      }

      if ($table.length) {
        $filterRows = $table.find('div.block-filter-text-source');
        $input.on('keyup', debounce(filterBlockList, 200));
      }
    }

  };
  Drupal.behaviors.blockHighlightPlacement = {
    attach(context, settings) {
      if (settings.blockPlacement && $('.js-block-placed').length) {
        once('block-highlight', '[data-drupal-selector="edit-blocks"]', context).forEach(container => {
          const $container = $(container);
          $('html, body').animate({
            scrollTop: $('.js-block-placed').offset().top - $container.offset().top + $container.scrollTop()
          }, 500);
        });
      }
    }

  };
})(jQuery, Drupal, Drupal.debounce, once);