/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.tableDragTest = {
    attach(context) {
      $(once('tabledrag-test', '.tabledrag-handle', context)).on('keydown.tabledrag-test', event => {
        $(event.currentTarget).removeClass('tabledrag-test-dragging');
      });
    }

  };
})(jQuery, Drupal);