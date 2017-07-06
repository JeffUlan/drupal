/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.simpleTestGroupCollapse = {
    attach: function attach(context) {
      $(context).find('.simpletest-group').once('simpletest-group-collapse').each(function () {
        var $group = $(this);
        var $image = $group.find('.simpletest-image');
        $image.html(drupalSettings.simpleTest.images[0]).on('click', function () {
          var $tests = $group.nextUntil('.simpletest-group');
          var expand = !$group.hasClass('expanded');
          $group.toggleClass('expanded', expand);
          $tests.toggleClass('js-hide', !expand);
          $image.html(drupalSettings.simpleTest.images[+expand]);
        });
      });
    }
  };

  Drupal.behaviors.simpleTestSelectAll = {
    attach: function attach(context) {
      $(context).find('.simpletest-group').once('simpletest-group-select-all').each(function () {
        var $group = $(this);
        var $cell = $group.find('.simpletest-group-select-all');
        var $groupCheckbox = $('<input type="checkbox" id="' + $cell.attr('id') + '-group-select-all" class="form-checkbox" />');
        var $testCheckboxes = $group.nextUntil('.simpletest-group').find('input[type=checkbox]');
        $cell.append($groupCheckbox);

        $groupCheckbox.on('change', function () {
          var checked = $(this).prop('checked');
          $testCheckboxes.prop('checked', checked);
        });

        function updateGroupCheckbox() {
          var allChecked = true;
          $testCheckboxes.each(function () {
            if (!$(this).prop('checked')) {
              allChecked = false;
              return false;
            }
          });
          $groupCheckbox.prop('checked', allChecked);
        }

        $testCheckboxes.on('change', updateGroupCheckbox);
      });
    }
  };

  Drupal.behaviors.simpletestTableFilterByText = {
    attach: function attach(context) {
      var $input = $('input.table-filter-text').once('table-filter-text');
      var $table = $($input.attr('data-table'));
      var $rows = void 0;
      var searched = false;

      function filterTestList(e) {
        var query = $(e.target).val().toLowerCase();

        function showTestRow(index, row) {
          var $row = $(row);
          var $sources = $row.find('.table-filter-text-source');
          var textMatch = $sources.text().toLowerCase().indexOf(query) !== -1;
          $row.closest('tr').toggle(textMatch);
        }

        if (query.length >= 3) {
          searched = true;
          $('#simpletest-form-table thead th.select-all input').hide();

          $rows.each(showTestRow);
        } else if (searched) {
            searched = false;
            $('#simpletest-form-table thead th.select-all input').show();

            $rows.css('display', '');
          }
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $input.trigger('focus').on('keyup', Drupal.debounce(filterTestList, 200));
      }
    }
  };
})(jQuery, Drupal, drupalSettings);