(function ($, Drupal, drupalSettings) {

"use strict";

/**
 * Store the state of weight columns display for all tables.
 * Default value is to hide weight columns.
 */
var showWeight = JSON.parse(localStorage.getItem('Drupal.tableDrag.showWeight'));

/**
 * Drag and drop table rows with field manipulation.
 *
 * Using the drupal_add_tabledrag() function, any table with weights or parent
 * relationships may be made into draggable tables. Columns containing a field
 * may optionally be hidden, providing a better user experience.
 *
 * Created tableDrag instances may be modified with custom behaviors by
 * overriding the .onDrag, .onDrop, .row.onSwap, and .row.onIndent methods.
 * See blocks.js for an example of adding additional functionality to tableDrag.
 */
Drupal.behaviors.tableDrag = {
  attach: function (context, settings) {
    function initTableDrag(table, base) {
      if (table.length) {
        // Create the new tableDrag instance. Save in the Drupal variable
        // to allow other scripts access to the object.
        Drupal.tableDrag[base] = new Drupal.tableDrag(table[0], settings.tableDrag[base]);
      }
    }

    for (var base in settings.tableDrag) {
      if (settings.tableDrag.hasOwnProperty(base)) {
        initTableDrag($(context).find('#' + base).once('tabledrag'), base);
      }
    }
  }
};

/**
 * Constructor for the tableDrag object. Provides table and field manipulation.
 *
 * @param table
 *   DOM object for the table to be made draggable.
 * @param tableSettings
 *   Settings for the table added via drupal_add_dragtable().
 */
Drupal.tableDrag = function (table, tableSettings) {
  var self = this;
  var $table = $(table);

  // Required object variables.
  this.$table = $(table);
  this.table = table;
  this.tableSettings = tableSettings;
  this.dragObject = null; // Used to hold information about a current drag operation.
  this.rowObject = null; // Provides operations for row manipulation.
  this.oldRowElement = null; // Remember the previous element.
  this.oldY = 0; // Used to determine up or down direction from last mouse move.
  this.changed = false; // Whether anything in the entire table has changed.
  this.maxDepth = 0; // Maximum amount of allowed parenting.
  this.rtl = $(this.table).css('direction') === 'rtl' ? -1 : 1; // Direction of the table.

  // Configure the scroll settings.
  this.scrollSettings = { amount: 4, interval: 50, trigger: 70 };
  this.scrollInterval = null;
  this.scrollY = 0;
  this.windowHeight = 0;

  // Check this table's settings to see if there are parent relationships in
  // this table. For efficiency, large sections of code can be skipped if we
  // don't need to track horizontal movement and indentations.
  this.indentEnabled = false;
  for (var group in tableSettings) {
    if (tableSettings.hasOwnProperty(group)) {
      for (var n in tableSettings[group]) {
        if (tableSettings[group].hasOwnProperty(n)) {
          if (tableSettings[group][n].relationship === 'parent') {
            this.indentEnabled = true;
          }
          if (tableSettings[group][n].limit > 0) {
            this.maxDepth = tableSettings[group][n].limit;
          }
        }
      }
    }
  }
  if (this.indentEnabled) {
    this.indentCount = 1; // Total width of indents, set in makeDraggable.
    // Find the width of indentations to measure mouse movements against.
    // Because the table doesn't need to start with any indentations, we
    // manually append 2 indentations in the first draggable row, measure
    // the offset, then remove.
    var indent = Drupal.theme('tableDragIndentation');
    var testRow = $('<tr/>').addClass('draggable').appendTo(table);
    var testCell = $('<td/>').appendTo(testRow).prepend(indent).prepend(indent);
    var $indentation = testCell.find('.indentation');
    this.indentAmount = $indentation.get(1).offsetLeft - $indentation.get(0).offsetLeft;
    testRow.remove();
  }

  // Make each applicable row draggable.
  // Match immediate children of the parent element to allow nesting.
  $table.find('> tr.draggable, > tbody > tr.draggable').each(function () { self.makeDraggable(this); });

  // Add a link before the table for users to show or hide weight columns.
  $table.before($('<button type="button" class="link tabledrag-toggle-weight"></button>')
    .attr('title', Drupal.t('Re-order rows by numerical weight instead of dragging.'))
    .click($.proxy(function (e) {
      e.preventDefault();
      this.toggleColumns();
    }, this))
    .wrap('<div class="tabledrag-toggle-weight-wrapper"></div>')
    .parent()
  );

  // Initialize the specified columns (for example, weight or parent columns)
  // to show or hide according to user preference. This aids accessibility
  // so that, e.g., screen reader users can choose to enter weight values and
  // manipulate form elements directly, rather than using drag-and-drop..
  self.initColumns();

  // Add event bindings to the document. The self variable is passed along
  // as event handlers do not have direct access to the tableDrag object.
  if (Modernizr.touch) {
    $(document).bind('touchmove', function (event) { return self.dragRow(event.originalEvent.touches[0], self); });
    $(document).bind('touchend', function (event) { return self.dropRow(event.originalEvent.touches[0], self); });
  }
  else {
    $(document).bind('mousemove', function (event) { return self.dragRow(event, self); });
    $(document).bind('mouseup', function (event) { return self.dropRow(event, self); });
  }

  // React to localStorage event showing or hiding weight columns.
  $(window).bind('storage', $.proxy(function (e) {
    // Only react to 'Drupal.tableDrag.showWeight' value change.
    if (e.originalEvent.key === 'Drupal.tableDrag.showWeight') {
      // This was changed in another window, get the new value for this window.
      showWeight = JSON.parse(e.originalEvent.newValue);
      this.displayColumns(showWeight);
    }
  }, this));
};

/**
 * Initialize columns containing form elements to be hidden by default,
 * according to the settings for this tableDrag instance.
 *
 * Identify and mark each cell with a CSS class so we can easily toggle
 * show/hide it. Finally, hide columns if user does not have a
 * 'Drupal.tableDrag.showWeight' localStorage value.
 */
Drupal.tableDrag.prototype.initColumns = function () {
  var $table = this.$table;
  var hidden, cell, columnIndex;
  for (var group in this.tableSettings) {
    if (this.tableSettings.hasOwnProperty(group)) { // Find the first field in this group.
      for (var d in this.tableSettings[group]) {
        if (this.tableSettings[group].hasOwnProperty(d)) {
          var field = $table.find('.' + this.tableSettings[group][d].target + ':first');
          if (field.length && this.tableSettings[group][d].hidden) {
            hidden = this.tableSettings[group][d].hidden;
            cell = field.closest('td');
            break;
          }
        }
      }

      // Mark the column containing this field so it can be hidden.
      if (hidden && cell[0]) {
        // Add 1 to our indexes. The nth-child selector is 1 based, not 0 based.
        // Match immediate children of the parent element to allow nesting.
        columnIndex = cell.parent().find('> td').index(cell.get(0)) + 1;
        $table.find('> thead > tr, > tbody > tr, > tr').each(this.addColspanClass(columnIndex));
      }
    }
  }
  this.displayColumns(showWeight);
};

/**
 * Mark cells that have colspan so we can adjust the colspan
 * instead of hiding them altogether.
 */
Drupal.tableDrag.prototype.addColspanClass = function(columnIndex) {
  return function () {
    // Get the columnIndex and adjust for any colspans in this row.
    var $row = $(this);
    var index = columnIndex;
    var cells = $row.children();
    var cell;
    cells.each(function (n) {
      if (n < index && this.colSpan && this.colSpan > 1) {
        index -= this.colSpan - 1;
      }
    });
    if (index > 0) {
      cell = cells.filter(':nth-child(' + index + ')');
      if (cell[0].colSpan && cell[0].colSpan > 1) {
        // If this cell has a colspan, mark it so we can reduce the colspan.
        cell.addClass('tabledrag-has-colspan');
      }
      else {
        // Mark this cell so we can hide it.
        cell.addClass('tabledrag-hide');
      }
    }
  };
};

/**
 * Hide or display weight columns. Triggers an event on change.
 *
 * @param bool displayWeight
 *   'true' will show weight columns.
 */
Drupal.tableDrag.prototype.displayColumns = function (displayWeight) {
  if (displayWeight) {
    this.showColumns();
  }
  // Default action is to hide columns.
  else {
    this.hideColumns();
  }
  // Trigger an event to allow other scripts to react to this display change.
  // Force the extra parameter as a bool.
  $('table.tabledrag-processed').trigger('columnschange', !!displayWeight);
};

/**
 * Toggle the weight column depending on 'showWeight' value.
 * Store only default override.
 */
Drupal.tableDrag.prototype.toggleColumns = function () {
  showWeight = !showWeight;
  this.displayColumns(showWeight);
  if (showWeight) {
    // Save default override.
    localStorage.setItem('Drupal.tableDrag.showWeight', showWeight);
  }
  else {
    // Reset the value to its default.
    localStorage.removeItem('Drupal.tableDrag.showWeight');
  }
};

/**
 * Hide the columns containing weight/parent form elements.
 * Undo showColumns().
 */
Drupal.tableDrag.prototype.hideColumns = function () {
  var $tables = $('table.tabledrag-processed');
  // Hide weight/parent cells and headers.
  $tables.find('.tabledrag-hide').css('display', 'none');
  // Show TableDrag handles.
  $tables.find('.tabledrag-handle').css('display', '');
  // Reduce the colspan of any effected multi-span columns.
  $tables.find('.tabledrag-has-colspan').each(function () {
    this.colSpan = this.colSpan - 1;
  });
  // Change link text.
  $('.tabledrag-toggle-weight').text(Drupal.t('Show row weights'));
};

/**
 * Show the columns containing weight/parent form elements
 * Undo hideColumns().
 */
Drupal.tableDrag.prototype.showColumns = function () {
  var $tables = $('table.tabledrag-processed');
  // Show weight/parent cells and headers.
  $tables.find('.tabledrag-hide').css('display', '');
  // Hide TableDrag handles.
  $tables.find('.tabledrag-handle').css('display', 'none');
  // Increase the colspan for any columns where it was previously reduced.
  $tables.find('.tabledrag-has-colspan').each(function () {
    this.colSpan = this.colSpan + 1;
  });
  // Change link text.
  $('.tabledrag-toggle-weight').text(Drupal.t('Hide row weights'));
};

/**
 * Find the target used within a particular row and group.
 */
Drupal.tableDrag.prototype.rowSettings = function (group, row) {
  var field = $(row).find('.' + group);
  var tableSettingsGroup = this.tableSettings[group];
  for (var delta in tableSettingsGroup) {
    if (tableSettingsGroup.hasOwnProperty(delta)) {
      var targetClass = tableSettingsGroup[delta].target;
      if (field.is('.' + targetClass)) {
        // Return a copy of the row settings.
        var rowSettings = {};
        for (var n in tableSettingsGroup[delta]) {
          if (tableSettingsGroup[delta].hasOwnProperty(n)) {
            rowSettings[n] = tableSettingsGroup[delta][n];
          }
        }
        return rowSettings;
      }
    }
  }
};

/**
 * Take an item and add event handlers to make it become draggable.
 */
Drupal.tableDrag.prototype.makeDraggable = function (item) {
  var self = this;
  var $item = $(item);
  //Add a class to the title link
  $item.find('td:first a').addClass('menu-item__link');
  // Create the handle.
  var handle = $('<a href="#" class="tabledrag-handle"><div class="handle">&nbsp;</div></a>').attr('title', Drupal.t('Drag to re-order'));
  // Insert the handle after indentations (if any).
  var $indentationLast = $item.find('td:first .indentation:last');
  if ($indentationLast.length) {
    $indentationLast.after(handle);
    // Update the total width of indentation in this entire table.
    self.indentCount = Math.max($item.find('.indentation').length, self.indentCount);
  }
  else {
    $item.find('td:first').prepend(handle);
  }

  if (Modernizr.touch) {
    handle.on('touchstart', function (event) {
      event.preventDefault();
      event = event.originalEvent.touches[0];
      self.dragStart(event, self, item);
    });
  }
  else {
    handle.mousedown(function (event) {
      event.preventDefault();
      self.dragStart(event, self, item);
    });
  }

  // Prevent the anchor tag from jumping us to the top of the page.
  handle.click(function (e) {
    e.preventDefault();
  });

  // Set blur cleanup when a handle is focused.
  handle.focus(function () {
    self.safeBlur = true;
  });

  // On blur, fire the same function as a touchend/mouseup. This is used to
  // update values after a row has been moved through the keyboard support.
  handle.blur(function (event) {
    if (self.rowObject && self.safeBlur) {
      self.dropRow(event, self);
    }
  });

  // Add arrow-key support to the handle.
  handle.keydown(function (event) {
    // If a rowObject doesn't yet exist and this isn't the tab key.
    if (event.keyCode !== 9 && !self.rowObject) {
      self.rowObject = new self.row(item, 'keyboard', self.indentEnabled, self.maxDepth, true);
    }

    var keyChange = false;
    var groupHeight;
    switch (event.keyCode) {
      case 37: // Left arrow.
      case 63234: // Safari left arrow.
        keyChange = true;
        self.rowObject.indent(-1 * self.rtl);
        break;
      case 38: // Up arrow.
      case 63232: // Safari up arrow.
        var $previousRow = $(self.rowObject.element).prev('tr').eq(0);
        var previousRow = $previousRow.get(0);
        while (previousRow && $previousRow.is(':hidden')) {
          $previousRow = $(previousRow).prev('tr').eq(0);
          previousRow = $previousRow.get(0);
        }
        if (previousRow) {
          self.safeBlur = false; // Do not allow the onBlur cleanup.
          self.rowObject.direction = 'up';
          keyChange = true;

          if ($(item).is('.tabledrag-root')) {
            // Swap with the previous top-level row.
            groupHeight = 0;
            while (previousRow && $previousRow.find('.indentation').length) {
              $previousRow = $(previousRow).prev('tr').eq(0);
              previousRow = $previousRow.get(0);
              groupHeight += $previousRow.is(':hidden') ? 0 : previousRow.offsetHeight;
            }
            if (previousRow) {
              self.rowObject.swap('before', previousRow);
              // No need to check for indentation, 0 is the only valid one.
              window.scrollBy(0, -groupHeight);
            }
          }
          else if (self.table.tBodies[0].rows[0] !== previousRow || $previousRow.is('.draggable')) {
            // Swap with the previous row (unless previous row is the first one
            // and undraggable).
            self.rowObject.swap('before', previousRow);
            self.rowObject.interval = null;
            self.rowObject.indent(0);
            window.scrollBy(0, -parseInt(item.offsetHeight, 10));
          }
          handle.get(0).focus(); // Regain focus after the DOM manipulation.
        }
        break;
      case 39: // Right arrow.
      case 63235: // Safari right arrow.
        keyChange = true;
        self.rowObject.indent(self.rtl);
        break;
      case 40: // Down arrow.
      case 63233: // Safari down arrow.
        var $nextRow = $(self.rowObject.group).filter(':last').next('tr').eq(0);
        var nextRow = $nextRow.get(0);
        while (nextRow && $nextRow.is(':hidden')) {
          $nextRow = $(nextRow).next('tr').eq(0);
          nextRow = $nextRow.get(0);
        }
        if (nextRow) {
          self.safeBlur = false; // Do not allow the onBlur cleanup.
          self.rowObject.direction = 'down';
          keyChange = true;

          if ($(item).is('.tabledrag-root')) {
            // Swap with the next group (necessarily a top-level one).
            groupHeight = 0;
            var nextGroup = new self.row(nextRow, 'keyboard', self.indentEnabled, self.maxDepth, false);
            if (nextGroup) {
              $(nextGroup.group).each(function () {
                groupHeight += $(this).is(':hidden') ? 0 : this.offsetHeight;
              });
              var nextGroupRow = $(nextGroup.group).filter(':last').get(0);
              self.rowObject.swap('after', nextGroupRow);
              // No need to check for indentation, 0 is the only valid one.
              window.scrollBy(0, parseInt(groupHeight, 10));
            }
          }
          else {
            // Swap with the next row.
            self.rowObject.swap('after', nextRow);
            self.rowObject.interval = null;
            self.rowObject.indent(0);
            window.scrollBy(0, parseInt(item.offsetHeight, 10));
          }
          handle.get(0).focus(); // Regain focus after the DOM manipulation.
        }
        break;
    }

    if (self.rowObject && self.rowObject.changed === true) {
      $(item).addClass('drag');
      if (self.oldRowElement) {
        $(self.oldRowElement).removeClass('drag-previous');
      }
      self.oldRowElement = item;
      self.restripeTable();
      self.onDrag();
    }

    // Returning false if we have an arrow key to prevent scrolling.
    if (keyChange) {
      return false;
    }
  });

  // Compatibility addition, return false on keypress to prevent unwanted scrolling.
  // IE and Safari will suppress scrolling on keydown, but all other browsers
  // need to return false on keypress. http://www.quirksmode.org/js/keys.html
  handle.keypress(function (event) {
    switch (event.keyCode) {
      case 37: // Left arrow.
      case 38: // Up arrow.
      case 39: // Right arrow.
      case 40: // Down arrow.
        return false;
    }
  });
};

/**
 * Pointer event initiator, creates drag object and information.
 *
 * @param jQuery.Event event
 *   The event object that trigger the drag.
 * @param Drupal.tableDrag self
 *   The drag handle.
 * @param DOM item
 *   The item that that is being dragged.
 */
Drupal.tableDrag.prototype.dragStart = function (event, self, item) {
  // Create a new dragObject recording the pointer information.
  self.dragObject = {};
  self.dragObject.initOffset = self.getPointerOffset(item, event);
  self.dragObject.initPointerCoords = self.pointerCoords(event);
  if (self.indentEnabled) {
    self.dragObject.indentPointerPos = self.dragObject.initPointerCoords;
  }

  // If there's a lingering row object from the keyboard, remove its focus.
  if (self.rowObject) {
    $(self.rowObject.element).find('a.tabledrag-handle').blur();
  }

  // Create a new rowObject for manipulation of this row.
  self.rowObject = new self.row(item, 'pointer', self.indentEnabled, self.maxDepth, true);

  // Save the position of the table.
  self.table.topY = $(self.table).offset().top;
  self.table.bottomY = self.table.topY + self.table.offsetHeight;

  // Add classes to the handle and row.
  $(item).addClass('drag');

  // Set the document to use the move cursor during drag.
  $('body').addClass('drag');
  if (self.oldRowElement) {
    $(self.oldRowElement).removeClass('drag-previous');
  }
};

/**
 * Pointer movement handler, bound to document.
 */
Drupal.tableDrag.prototype.dragRow = function (event, self) {
  if (self.dragObject) {
    self.currentPointerCoords = self.pointerCoords(event);
    var y = self.currentPointerCoords.y - self.dragObject.initOffset.y;
    var x = self.currentPointerCoords.x - self.dragObject.initOffset.x;

    // Check for row swapping and vertical scrolling.
    if (y !== self.oldY) {
      self.rowObject.direction = y > self.oldY ? 'down' : 'up';
      self.oldY = y; // Update the old value.

      // Check if the window should be scrolled (and how fast).
      var scrollAmount = self.checkScroll(self.currentPointerCoords.y);
      // Stop any current scrolling.
      clearInterval(self.scrollInterval);
      // Continue scrolling if the mouse has moved in the scroll direction.
      if (scrollAmount > 0 && self.rowObject.direction === 'down' || scrollAmount < 0 && self.rowObject.direction === 'up') {
        self.setScroll(scrollAmount);
      }

      // If we have a valid target, perform the swap and restripe the table.
      var currentRow = self.findDropTargetRow(x, y);
      if (currentRow) {
        if (self.rowObject.direction === 'down') {
          self.rowObject.swap('after', currentRow, self);
        }
        else {
          self.rowObject.swap('before', currentRow, self);
        }
        self.restripeTable();
      }
    }

    // Similar to row swapping, handle indentations.
    if (self.indentEnabled) {
      var xDiff = self.currentPointerCoords.x - self.dragObject.indentPointerPos.x;
      // Set the number of indentations the pointer has been moved left or right.
      var indentDiff = Math.round(xDiff / self.indentAmount * self.rtl);
      // Indent the row with our estimated diff, which may be further
      // restricted according to the rows around this row.
      var indentChange = self.rowObject.indent(indentDiff);
      // Update table and pointer indentations.
      self.dragObject.indentPointerPos.x += self.indentAmount * indentChange * self.rtl;
      self.indentCount = Math.max(self.indentCount, self.rowObject.indents);
    }

    return false;
  }
};

/**
 * Pointerup behaviour.
 */
Drupal.tableDrag.prototype.dropRow = function (event, self) {
  var droppedRow, $droppedRow;

  // Drop row functionality.
  if (self.rowObject !== null) {
    droppedRow = self.rowObject.element;
    $droppedRow = $(droppedRow);
    // The row is already in the right place so we just release it.
    if (self.rowObject.changed === true) {
      // Update the fields in the dropped row.
      self.updateFields(droppedRow);

      // If a setting exists for affecting the entire group, update all the
      // fields in the entire dragged group.
      for (var group in self.tableSettings) {
        if (self.tableSettings.hasOwnProperty(group)) {
          var rowSettings = self.rowSettings(group, droppedRow);
          if (rowSettings.relationship === 'group') {
            for (var n in self.rowObject.children) {
              if (self.rowObject.children.hasOwnProperty(n)) {
                self.updateField(self.rowObject.children[n], group);
              }
            }
          }
        }
      }

      self.rowObject.markChanged();
      if (self.changed === false) {
        $(Drupal.theme('tableDragChangedWarning')).insertBefore(self.table).hide().fadeIn('slow');
        self.changed = true;
      }
    }

    if (self.indentEnabled) {
      self.rowObject.removeIndentClasses();
    }
    if (self.oldRowElement) {
      $(self.oldRowElement).removeClass('drag-previous');
    }
    $droppedRow.removeClass('drag').addClass('drag-previous');
    self.oldRowElement = droppedRow;
    self.onDrop();
    self.rowObject = null;
  }

  // Functionality specific only to pointerup events.
  if (self.dragObject !== null) {
    self.dragObject = null;
    $('body').removeClass('drag');
    clearInterval(self.scrollInterval);
  }
};

/**
 * Get the coordinates from the event (allowing for browser differences).
 */
Drupal.tableDrag.prototype.pointerCoords = function (event) {
  if (event.pageX || event.pageY) {
    return { x: event.pageX, y: event.pageY };
  }
  return {
    x: event.clientX + document.body.scrollLeft - document.body.clientLeft,
    y: event.clientY + document.body.scrollTop  - document.body.clientTop
  };
};

/**
 * Given a target element and a pointer event, get the event offset from that
 * element. To do this we need the element's position and the target position.
 */
Drupal.tableDrag.prototype.getPointerOffset = function (target, event) {
  var docPos = $(target).offset();
  var pointerPos = this.pointerCoords(event);
  return { x: pointerPos.x - docPos.left, y: pointerPos.y - docPos.top };
};

/**
 * Find the row the mouse is currently over. This row is then taken and swapped
 * with the one being dragged.
 *
 * @param x
 *   The x coordinate of the mouse on the page (not the screen).
 * @param y
 *   The y coordinate of the mouse on the page (not the screen).
 */
Drupal.tableDrag.prototype.findDropTargetRow = function (x, y) {
  var rows = $(this.table.tBodies[0].rows).not(':hidden');
  for (var n = 0; n < rows.length; n++) {
    var row = rows[n];
    var $row = $(row);
    var rowY = $row.offset().top;
    var rowHeight;
    // Because Safari does not report offsetHeight on table rows, but does on
    // table cells, grab the firstChild of the row and use that instead.
    // http://jacob.peargrove.com/blog/2006/technical/table-row-offsettop-bug-in-safari.
    if (row.offsetHeight === 0) {
      rowHeight = parseInt(row.firstChild.offsetHeight, 10) / 2;
    }
    // Other browsers.
    else {
      rowHeight = parseInt(row.offsetHeight, 10) / 2;
    }

    // Because we always insert before, we need to offset the height a bit.
    if ((y > (rowY - rowHeight)) && (y < (rowY + rowHeight))) {
      if (this.indentEnabled) {
        // Check that this row is not a child of the row being dragged.
        for (n in this.rowObject.group) {
          if (this.rowObject.group[n] === row) {
            return null;
          }
        }
      }
      else {
        // Do not allow a row to be swapped with itself.
        if (row === this.rowObject.element) {
          return null;
        }
      }

      // Check that swapping with this row is allowed.
      if (!this.rowObject.isValidSwap(row)) {
        return null;
      }

      // We may have found the row the mouse just passed over, but it doesn't
      // take into account hidden rows. Skip backwards until we find a draggable
      // row.
      while ($row.is(':hidden') && $row.prev('tr').is(':hidden')) {
        $row = $row.prev('tr').eq(0);
        row = $row.get(0);
      }
      return row;
    }
  }
  return null;
};

/**
 * After the row is dropped, update the table fields according to the settings
 * set for this table.
 *
 * @param changedRow
 *   DOM object for the row that was just dropped.
 */
Drupal.tableDrag.prototype.updateFields = function (changedRow) {
  for (var group in this.tableSettings) {
    if (this.tableSettings.hasOwnProperty(group)) {
      // Each group may have a different setting for relationship, so we find
      // the source rows for each separately.
      this.updateField(changedRow, group);
    }
  }
};

/**
 * After the row is dropped, update a single table field according to specific
 * settings.
 *
 * @param changedRow
 *   DOM object for the row that was just dropped.
 * @param group
 *   The settings group on which field updates will occur.
 */
Drupal.tableDrag.prototype.updateField = function (changedRow, group) {
  var rowSettings = this.rowSettings(group, changedRow);
  var $changedRow = $(changedRow);
  var sourceRow;
  var $previousRow;
  var previousRow;
  var useSibling;
  // Set the row as its own target.
  if (rowSettings.relationship === 'self' || rowSettings.relationship === 'group') {
    sourceRow = changedRow;
  }
  // Siblings are easy, check previous and next rows.
  else if (rowSettings.relationship === 'sibling') {
    $previousRow = $changedRow.prev('tr').eq(0);
    previousRow = $previousRow.get(0);
    var $nextRow = $changedRow.next('tr').eq(0);
    var nextRow = $nextRow.get(0);
    sourceRow = changedRow;
    if ($previousRow.is('.draggable') && $previousRow.find('.' + group).length) {
      if (this.indentEnabled) {
        if ($previousRow.find('.indentations').length === $changedRow.find('.indentations').length) {
          sourceRow = previousRow;
        }
      }
      else {
        sourceRow = previousRow;
      }
    }
    else if ($nextRow.is('.draggable') && $nextRow.find('.' + group).length) {
      if (this.indentEnabled) {
        if ($nextRow.find('.indentations').length === $changedRow.find('.indentations').length) {
          sourceRow = nextRow;
        }
      }
      else {
        sourceRow = nextRow;
      }
    }
  }
  // Parents, look up the tree until we find a field not in this group.
  // Go up as many parents as indentations in the changed row.
  else if (rowSettings.relationship === 'parent') {
    $previousRow = $changedRow.prev('tr');
    previousRow = $previousRow;
    while ($previousRow.length && $previousRow.find('.indentation').length >= this.rowObject.indents) {
      $previousRow = $previousRow.prev('tr');
      previousRow = $previousRow;
    }
    // If we found a row.
    if ($previousRow.length) {
      sourceRow = $previousRow.get(0);
    }
    // Otherwise we went all the way to the left of the table without finding
    // a parent, meaning this item has been placed at the root level.
    else {
      // Use the first row in the table as source, because it's guaranteed to
      // be at the root level. Find the first item, then compare this row
      // against it as a sibling.
      sourceRow = $(this.table).find('tr.draggable:first').get(0);
      if (sourceRow === this.rowObject.element) {
        sourceRow = $(this.rowObject.group[this.rowObject.group.length - 1]).next('tr.draggable').get(0);
      }
      useSibling = true;
    }
  }

  // Because we may have moved the row from one category to another,
  // take a look at our sibling and borrow its sources and targets.
  this.copyDragClasses(sourceRow, changedRow, group);
  rowSettings = this.rowSettings(group, changedRow);

  // In the case that we're looking for a parent, but the row is at the top
  // of the tree, copy our sibling's values.
  if (useSibling) {
    rowSettings.relationship = 'sibling';
    rowSettings.source = rowSettings.target;
  }

  var targetClass = '.' + rowSettings.target;
  var targetElement = $changedRow.find(targetClass).get(0);

  // Check if a target element exists in this row.
  if (targetElement) {
    var sourceClass = '.' + rowSettings.source;
    var sourceElement = $(sourceClass, sourceRow).get(0);
    switch (rowSettings.action) {
      case 'depth':
        // Get the depth of the target row.
        targetElement.value = $(sourceElement).closest('tr').find('.indentation').length;
        break;
      case 'match':
        // Update the value.
        targetElement.value = sourceElement.value;
        break;
      case 'order':
        var siblings = this.rowObject.findSiblings(rowSettings);
        if ($(targetElement).is('select')) {
          // Get a list of acceptable values.
          var values = [];
          $(targetElement).find('option').each(function () {
            values.push(this.value);
          });
          var maxVal = values[values.length - 1];
          // Populate the values in the siblings.
          $(siblings).find(targetClass).each(function () {
            // If there are more items than possible values, assign the maximum value to the row.
            if (values.length > 0) {
              this.value = values.shift();
            }
            else {
              this.value = maxVal;
            }
          });
        }
        else {
          // Assume a numeric input field.
          var weight = parseInt($(siblings[0]).find(targetClass).val(), 10) || 0;
          $(siblings).find(targetClass).each(function () {
            this.value = weight;
            weight++;
          });
        }
        break;
    }
  }
};

/**
 * Copy all special tableDrag classes from one row's form elements to a
 * different one, removing any special classes that the destination row
 * may have had.
 */
Drupal.tableDrag.prototype.copyDragClasses = function (sourceRow, targetRow, group) {
  var sourceElement = $(sourceRow).find('.' + group);
  var targetElement = $(targetRow).find('.' + group);
  if (sourceElement.length && targetElement.length) {
    targetElement[0].className = sourceElement[0].className;
  }
};

Drupal.tableDrag.prototype.checkScroll = function (cursorY) {
  var de  = document.documentElement;
  var b  = document.body;

  var windowHeight = this.windowHeight = window.innerHeight || (de.clientHeight && de.clientWidth !== 0 ? de.clientHeight : b.offsetHeight);
  var scrollY = this.scrollY = (document.all ? (!de.scrollTop ? b.scrollTop : de.scrollTop) : (window.pageYOffset ? window.pageYOffset : window.scrollY));
  var trigger = this.scrollSettings.trigger;
  var delta = 0;

  // Return a scroll speed relative to the edge of the screen.
  if (cursorY - scrollY > windowHeight - trigger) {
    delta = trigger / (windowHeight + scrollY - cursorY);
    delta = (delta > 0 && delta < trigger) ? delta : trigger;
    return delta * this.scrollSettings.amount;
  }
  else if (cursorY - scrollY < trigger) {
    delta = trigger / (cursorY - scrollY);
    delta = (delta > 0 && delta < trigger) ? delta : trigger;
    return -delta * this.scrollSettings.amount;
  }
};

Drupal.tableDrag.prototype.setScroll = function (scrollAmount) {
  var self = this;

  this.scrollInterval = setInterval(function () {
    // Update the scroll values stored in the object.
    self.checkScroll(self.currentPointerCoords.y);
    var aboveTable = self.scrollY > self.table.topY;
    var belowTable = self.scrollY + self.windowHeight < self.table.bottomY;
    if (scrollAmount > 0 && belowTable || scrollAmount < 0 && aboveTable) {
      window.scrollBy(0, scrollAmount);
    }
  }, this.scrollSettings.interval);
};

Drupal.tableDrag.prototype.restripeTable = function () {
  // :even and :odd are reversed because jQuery counts from 0 and
  // we count from 1, so we're out of sync.
  // Match immediate children of the parent element to allow nesting.
  $(this.table).find('> tbody > tr.draggable:visible, > tr.draggable:visible')
    .removeClass('odd even')
    .filter(':odd').addClass('even').end()
    .filter(':even').addClass('odd');
};

/**
 * Stub function. Allows a custom handler when a row begins dragging.
 */
Drupal.tableDrag.prototype.onDrag = function () {
  return null;
};

/**
 * Stub function. Allows a custom handler when a row is dropped.
 */
Drupal.tableDrag.prototype.onDrop = function () {
  return null;
};

/**
 * Constructor to make a new object to manipulate a table row.
 *
 * @param tableRow
 *   The DOM element for the table row we will be manipulating.
 * @param method
 *   The method in which this row is being moved. Either 'keyboard' or 'mouse'.
 * @param indentEnabled
 *   Whether the containing table uses indentations. Used for optimizations.
 * @param maxDepth
 *   The maximum amount of indentations this row may contain.
 * @param addClasses
 *   Whether we want to add classes to this row to indicate child relationships.
 */
Drupal.tableDrag.prototype.row = function (tableRow, method, indentEnabled, maxDepth, addClasses) {
  var $tableRow = $(tableRow);

  this.element = tableRow;
  this.method = method;
  this.group = [tableRow];
  this.groupDepth = $tableRow.find('.indentation').length;
  this.changed = false;
  this.table = $tableRow.closest('table')[0];
  this.indentEnabled = indentEnabled;
  this.maxDepth = maxDepth;
  this.direction = ''; // Direction the row is being moved.

  if (this.indentEnabled) {
    this.indents = $tableRow.find('.indentation').length;
    this.children = this.findChildren(addClasses);
    this.group = $.merge(this.group, this.children);
    // Find the depth of this entire group.
    for (var n = 0; n < this.group.length; n++) {
      this.groupDepth = Math.max($(this.group[n]).find('.indentation').length, this.groupDepth);
    }
  }
};

/**
 * Find all children of rowObject by indentation.
 *
 * @param addClasses
 *   Whether we want to add classes to this row to indicate child relationships.
 */
Drupal.tableDrag.prototype.row.prototype.findChildren = function (addClasses) {
  var parentIndentation = this.indents;
  var currentRow = $(this.element, this.table).next('tr.draggable');
  var rows = [];
  var child = 0;
  function rowIndentation(el, indentNum) {
    var self = $(el);
    if (child === 1 && (indentNum === parentIndentation)) {
      self.addClass('tree-child-first');
    }
    if (indentNum === parentIndentation) {
      self.addClass('tree-child');
    }
    else if (indentNum > parentIndentation) {
      self.addClass('tree-child-horizontal');
    }
  }
  while (currentRow.length) {
    // A greater indentation indicates this is a child.
    if (currentRow.find('.indentation').length > parentIndentation) {
      child++;
      rows.push(currentRow[0]);
      if (addClasses) {
        currentRow.find('.indentation').each(rowIndentation);
      }
    }
    else {
      break;
    }
    currentRow = currentRow.next('tr.draggable');
  }
  if (addClasses && rows.length) {
    $(rows[rows.length - 1]).find('.indentation:nth-child(' + (parentIndentation + 1) + ')').addClass('tree-child-last');
  }
  return rows;
};

/**
 * Ensure that two rows are allowed to be swapped.
 *
 * @param row
 *   DOM object for the row being considered for swapping.
 */
Drupal.tableDrag.prototype.row.prototype.isValidSwap = function (row) {
  var $row = $(row);
  if (this.indentEnabled) {
    var prevRow, nextRow;
    if (this.direction === 'down') {
      prevRow = row;
      nextRow = $row.next('tr').get(0);
    }
    else {
      prevRow = $row.prev('tr').get(0);
      nextRow = row;
    }
    this.interval = this.validIndentInterval(prevRow, nextRow);

    // We have an invalid swap if the valid indentations interval is empty.
    if (this.interval.min > this.interval.max) {
      return false;
    }
  }

  // Do not let an un-draggable first row have anything put before it.
  if (this.table.tBodies[0].rows[0] === row && $row.is(':not(.draggable)')) {
    return false;
  }

  return true;
};

/**
 * Perform the swap between two rows.
 *
 * @param position
 *   Whether the swap will occur 'before' or 'after' the given row.
 * @param row
 *   DOM element what will be swapped with the row group.
 */
Drupal.tableDrag.prototype.row.prototype.swap = function (position, row) {
  Drupal.detachBehaviors(this.group, drupalSettings, 'move');
  $(row)[position](this.group);
  Drupal.attachBehaviors(this.group, drupalSettings);
  this.changed = true;
  this.onSwap(row);
};

/**
 * Determine the valid indentations interval for the row at a given position
 * in the table.
 *
 * @param prevRow
 *   DOM object for the row before the tested position
 *   (or null for first position in the table).
 * @param nextRow
 *   DOM object for the row after the tested position
 *   (or null for last position in the table).
 */
Drupal.tableDrag.prototype.row.prototype.validIndentInterval = function (prevRow, nextRow) {
  var $prevRow = $(prevRow);
  var minIndent, maxIndent;

  // Minimum indentation:
  // Do not orphan the next row.
  minIndent = nextRow ? $(nextRow).find('.indentation').length : 0;

  // Maximum indentation:
  if (!prevRow || $prevRow.is(':not(.draggable)') || $(this.element).is('.tabledrag-root')) {
    // Do not indent:
    // - the first row in the table,
    // - rows dragged below a non-draggable row,
    // - 'root' rows.
    maxIndent = 0;
  }
  else {
    // Do not go deeper than as a child of the previous row.
    maxIndent = $prevRow.find('.indentation').length + ($prevRow.is('.tabledrag-leaf') ? 0 : 1);
    // Limit by the maximum allowed depth for the table.
    if (this.maxDepth) {
      maxIndent = Math.min(maxIndent, this.maxDepth - (this.groupDepth - this.indents));
    }
  }

  return { 'min': minIndent, 'max': maxIndent };
};

/**
 * Indent a row within the legal bounds of the table.
 *
 * @param indentDiff
 *   The number of additional indentations proposed for the row (can be
 *   positive or negative). This number will be adjusted to nearest valid
 *   indentation level for the row.
 */
Drupal.tableDrag.prototype.row.prototype.indent = function (indentDiff) {
  var $group = $(this.group);
  // Determine the valid indentations interval if not available yet.
  if (!this.interval) {
    var prevRow = $(this.element).prev('tr').get(0);
    var nextRow = $group.filter(':last').next('tr').get(0);
    this.interval = this.validIndentInterval(prevRow, nextRow);
  }

  // Adjust to the nearest valid indentation.
  var indent = this.indents + indentDiff;
  indent = Math.max(indent, this.interval.min);
  indent = Math.min(indent, this.interval.max);
  indentDiff = indent - this.indents;

  for (var n = 1; n <= Math.abs(indentDiff); n++) {
    // Add or remove indentations.
    if (indentDiff < 0) {
      $group.find('.indentation:first').remove();
      this.indents--;
    }
    else {
      $group.find('td:first').prepend(Drupal.theme('tableDragIndentation'));
      this.indents++;
    }
  }
  if (indentDiff) {
    // Update indentation for this row.
    this.changed = true;
    this.groupDepth += indentDiff;
    this.onIndent();
  }

  return indentDiff;
};

/**
 * Find all siblings for a row, either according to its subgroup or indentation.
 * Note that the passed-in row is included in the list of siblings.
 *
 * @param settings
 *   The field settings we're using to identify what constitutes a sibling.
 */
Drupal.tableDrag.prototype.row.prototype.findSiblings = function (rowSettings) {
  var siblings = [];
  var directions = ['prev', 'next'];
  var rowIndentation = this.indents;
  var checkRowIndentation;
  for (var d = 0; d < directions.length; d++) {
    var checkRow = $(this.element)[directions[d]]();
    while (checkRow.length) {
      // Check that the sibling contains a similar target field.
      if (checkRow.find('.' + rowSettings.target)) {
        // Either add immediately if this is a flat table, or check to ensure
        // that this row has the same level of indentation.
        if (this.indentEnabled) {
          checkRowIndentation = checkRow.find('.indentation').length;
        }

        if (!(this.indentEnabled) || (checkRowIndentation === rowIndentation)) {
          siblings.push(checkRow[0]);
        }
        else if (checkRowIndentation < rowIndentation) {
          // No need to keep looking for siblings when we get to a parent.
          break;
        }
      }
      else {
        break;
      }
      checkRow = checkRow[directions[d]]();
    }
    // Since siblings are added in reverse order for previous, reverse the
    // completed list of previous siblings. Add the current row and continue.
    if (directions[d] === 'prev') {
      siblings.reverse();
      siblings.push(this.element);
    }
  }
  return siblings;
};

/**
 * Remove indentation helper classes from the current row group.
 */
Drupal.tableDrag.prototype.row.prototype.removeIndentClasses = function () {
  for (var n in this.children) {
    if (this.children.hasOwnProperty(n)) {
      $(this.children[n]).find('.indentation')
        .removeClass('tree-child')
        .removeClass('tree-child-first')
        .removeClass('tree-child-last')
        .removeClass('tree-child-horizontal');
    }
  }
};

/**
 * Add an asterisk or other marker to the changed row.
 */
Drupal.tableDrag.prototype.row.prototype.markChanged = function () {
  var marker = Drupal.theme('tableDragChangedMarker');
  var cell = $(this.element).find('td:first');
  if (cell.find('abbr.tabledrag-changed').length === 0) {
    cell.append(marker);
  }
};

/**
 * Stub function. Allows a custom handler when a row is indented.
 */
Drupal.tableDrag.prototype.row.prototype.onIndent = function () {
  return null;
};

/**
 * Stub function. Allows a custom handler when a row is swapped.
 */
Drupal.tableDrag.prototype.row.prototype.onSwap = function (swappedRow) {
  return null;
};

$.extend(Drupal.theme, {
  tableDragChangedMarker: function () {
    return '<abbr class="warning tabledrag-changed" title="' + Drupal.t('Changed') + '">*</abbr>';
  },
  tableDragIndentation: function () {
    return '<div class="indentation">&nbsp;</div>';
  },
  tableDragChangedWarning: function () {
    return '<div class="tabledrag-changed-warning messages messages--warning" role="alert">' + Drupal.theme('tableDragChangedMarker') + ' ' + Drupal.t('You have unsaved changes.') + '</div>';
  }
});

})(jQuery, Drupal, drupalSettings);
