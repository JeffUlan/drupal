/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.commentByViewer = {
    attach: function attach(context) {
      var currentUserID = parseInt(drupalSettings.user.uid, 10);
      $('[data-comment-user-id]').filter(function () {
        return parseInt(this.getAttribute('data-comment-user-id'), 10) === currentUserID;
      }).addClass('by-viewer');
    }
  };
})(jQuery, Drupal, drupalSettings);