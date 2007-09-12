// $Id$

Drupal.behaviors.tableSelect = function (context) {
  $('form table:has(th.select-all):not(.tableSelect-processed)', context).each(Drupal.tableSelect);
};

Drupal.tableSelect = function() {
  // Keep track of the table, which checkbox is checked and alias the settings.
  var table = this, selectAll, checkboxes, lastChecked;
  var strings = { 'selectAll': Drupal.t('Select all rows in this table'), 'selectNone': Drupal.t('Deselect all rows in this table') };

  // Store the select all checkbox in a variable as we need it quite often.
  selectAll = $('<input type="checkbox" class="form-checkbox" />').attr('title', strings.selectAll).click(function() {
    // Loop through all checkboxes and set their state to the select all checkbox' state.
    checkboxes.each(function() {
      this.checked = selectAll[0].checked;
      // Either add or remove the selected class based on the state of the check all checkbox.
      $(this).parents('tr:first')[ this.checked ? 'addClass' : 'removeClass' ]('selected');
    });
    // Update the title and the state of the check all box.
    selectAll.attr('title', selectAll[0].checked ? strings.selectNone : strings.selectAll);
  });

  // Find all <th> with class select-all, and insert the check all checkbox.
  $('th.select-all', table).prepend(selectAll);

  // For each of the checkboxes within the table.
  checkboxes = $('td input:checkbox', table).click(function(e) {
    // Either add or remove the selected class based on the state of the check all checkbox.
    $(this).parents('tr:first')[ this.checked ? 'addClass' : 'removeClass' ]('selected');

    // If this is a shift click, we need to highlight everything in the range.
    // Also make sure that we are actually checking checkboxes over a range and
    // that a checkbox has been checked or unchecked before.
    if (e.shiftKey && lastChecked && lastChecked != e.target) {
      // We use the checkbox's parent TR to do our range searching.
      Drupal.tableSelectRange($(e.target).parents('tr')[0], $(lastChecked).parents('tr')[0], e.target.checked);
    }

    // If all checkboxes are checked, make sure the select-all one is checked too, otherwise keep unchecked.
    selectAll[0].checked = (checkboxes.length == $(checkboxes).filter(':checked').length);
    // Set the title to the current action.
    selectAll.attr('title', selectAll[0].checked ? strings.selectNone : strings.selectAll);

    // Keep track of the last checked checkbox.
    lastChecked = e.target;
  });
  $(this).addClass('tableSelect-processed');
};

Drupal.tableSelectRange = function(from, to, state) {
  // We determine the looping mode based on the the order of from and to.
  var mode = from.rowIndex > to.rowIndex ? 'previousSibling' : 'nextSibling';

  // Traverse through the sibling nodes.
  for (var i = from[mode]; i; i = i[mode]) {
    // Make sure that we're only dealing with elements.
    if (i.nodeType != 1) continue;

    // Either add or remove the selected class based on the state of the target checkbox.
    $(i)[ state ? 'addClass' : 'removeClass' ]('selected');
    $('input:checkbox', i).each(function() {
      this.checked = state;
    });

    if (to.nodeType) {
      // If we are at the end of the range, stop.
      if (i == to) break;
    }
    // A faster alternative to doing $(i).filter(to).length.
    else if (jQuery.filter(to, [i]).r.length) break;

  }
};
