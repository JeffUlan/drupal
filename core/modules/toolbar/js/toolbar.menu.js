/**
 * Builds a nested accordion widget.
 *
 * Invoke on an HTML list element with the jQuery plugin pattern.
 * - For example, $('.menu').toolbarMenu();
 */

(function ($, Drupal) {

"use strict";

/**
 * Store the open menu tray.
 */
var activeItem = JSON.parse(localStorage.getItem('Drupal.toolbar.menu.activeItem'));

  $.fn.toolbarMenu = function () {

    var ui = {
      'handleOpen': Drupal.t('Extend'),
      'handleClose': Drupal.t('Collapse')
    };
    /**
     * Handle clicks from the disclosure button on an item with sub-items.
     *
     * @param {Object} event
     *   A jQuery Event object.
     */
    function toggleClickHandler (event) {
      var $toggle = $(event.target);
      var $item = $toggle.closest('li');
      // Toggle the list item.
      toggleList($item);
      // Close open siblings and their open children.
      var $openItems = $item.siblings().filter('.open');
      toggleList($openItems, false);
      // Save link of the closest open item through a unique selector.
      var href = $toggle.siblings('a[href]').attr('href');
      if ($toggle.hasClass('open')) {
        localStorage.setItem('Drupal.toolbar.menu.activeItem', JSON.stringify(href));
      }
      else {
        localStorage.removeItem('Drupal.toolbar.menu.activeItem');
      }
    }
    /**
     * Toggle the open/close state of a list is a menu.
     *
     * @param {jQuery} $item
     *   The li item to be toggled.
     *
     * @param {Boolean} switcher
     *   A flag that forces toggleClass to add or a remove a class, rather than
     *   simply toggling its presence.
     */
    function toggleList ($item, switcher) {
      var $toggle = $item.find('> .box > .handle');
      switcher = (typeof switcher !== 'undefined') ? switcher : !$item.hasClass('open');
      // Toggle the item open state.
      $item.toggleClass('open', switcher);
      // Twist the toggle.
      $toggle.toggleClass('open', switcher);
      // Adjust the toggle text.
      $toggle
        .find('.action')
        // Expand Structure, Collapse Structure
        .text((switcher) ?  ui.handleClose : ui.handleOpen);
    }
    /**
     * Add markup to the menu elements.
     *
     * Items with sub-elements have a list toggle attached to them. Menu item
     * links and the corresponding list toggle are wrapped with in a div
     * classed with .box. The .box div provides a positioning context for the
     * item list toggle.
     *
     * @param {jQuery} $menu
     *   The root of the menu to be initialized.
     */
    function initItems ($menu) {
      var options = {
        'class': 'icon handle',
        'action': ui.handleOpen,
        'text': ''
      };
      // Initialize items and their links.
      $menu.find('li > a').wrap('<div class="box">');
        // Add a handle to each list item if it has a menu.
      $menu.find('li').each(function (index, element) {
          var $item = $(element);
          if ($item.find('> ul.menu').length) {
            var $box = $item.find('> .box');
            options.text = Drupal.t('@label', {'@label': $box.find('a').text()});
            $item.find('> .box')
              .append(Drupal.theme('toolbarMenuItemToggle', options));
          }
        });
    }
    /**
     * Adds a level class to each list based on its depth in the menu.
     *
     * This function is called recursively on each sub level of lists elements
     * until the depth of the menu is exhausted.
     *
     * @param {jQuery} $lists
     *   A jQuery object of ul elements.
     *
     * @param {Integer} level
     *   The current level number to be assigned to the list elements.
     */
    function markListLevels ($lists, level) {
      level = (!level) ? 1 : level;
      var $lis = $lists.find('> li').addClass('level-' + level);
      $lists = $lis.find('> ul');
      if ($lists.length) {
        markListLevels($lists, level + 1);
      }
    }
    /**
     * On page load, open the active menu item.
     *
     * Marks the trail of the active link in the menu back to the root of the
     * menu with .active-trail.
     *
     * @param {jQuery} $menu
     *   The root of the menu.
     */
    function openActiveItem ($menu) {
      var pathItem = $menu.find('a[href="' + location.pathname + '"]');
      if (pathItem.length && !activeItem) {
        activeItem = location.pathname;
      }
      if (activeItem) {
        var $activeItem = $menu.find('a[href="' + activeItem + '"]').addClass('active');
        var $activeTrail = $activeItem.parentsUntil('.root', 'li').addClass('active-trail');
        toggleList($activeTrail, true);
      }
    }
    /**
     * Store the last-clicked menu item to decorate the active menu trail.
     *
     * @param {jQuery} event
     *   A jQuery click event.
     */
    function storeActiveItem (event) {
      var href = $(event.target).attr('href');
      if (href) {
        localStorage.setItem('Drupal.toolbar.menu.activeItem', JSON.stringify(href));
      }
      else {
        localStorage.removeItem('Drupal.toolbar.menu.activeItem');
      }
    }
    // Bind event handlers.
    $(document)
      .on('click.toolbar', '.handle', toggleClickHandler)
      .on('click.toolbar', 'li > .box > a', storeActiveItem);
    // Return the jQuery object.
    return this.each(function (selector) {
      var $menu = $(this).once('toolbar-menu');
      if ($menu.length) {
        $menu.addClass('root');
        initItems($menu);
        markListLevels($menu);
        // Restore previous and active states.
        openActiveItem($menu);
      }
    });
  };

  /**
   * A toggle is an interactive element often bound to a click handler.
   *
   * @return {String}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.toolbarMenuItemToggle = function (options) {
    return '<button class="' + options['class'] + '"><span class="action">' + options.action + '</span><span class="label">' + options.text + '</span></button>';
  };

}(jQuery, Drupal));
