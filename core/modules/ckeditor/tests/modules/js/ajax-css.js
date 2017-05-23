/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal, ckeditor, editorSettings, $) {

  'use strict';

  Drupal.behaviors.ajaxCssForm = {

    attach: function attach(context) {
      $(context).find('#edit-inline').not('[contenteditable]').each(function () {
        ckeditor.attachInlineEditor(this, editorSettings.formats.test_format);
      });
    }
  };
})(Drupal, Drupal.editors.ckeditor, drupalSettings.editor, jQuery);