/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {

  'use strict';

  Drupal.AjaxCommands.prototype.editorDialogSave = function (ajax, response, status) {
    $(window).trigger('editor:dialogsave', [response.values]);
  };
})(jQuery, Drupal);