/**
* DO NOT EDIT THIS FILE.
* All changes should be applied to ./modules/image/js/theme.es6.js
* See the following change record for more information,
* https://www.drupal.org/node/2873849
* @preserve
**/

(function (Drupal) {

  'use strict';

  Drupal.theme.quickeditImageErrors = function (settings) {
    return '<div class="quickedit-image-errors">' + settings.errors + '</div>';
  };

  Drupal.theme.quickeditImageDropzone = function (settings) {
    return '<div class="quickedit-image-dropzone ' + settings.state + '">' + '  <i class="quickedit-image-icon"></i>' + '  <span class="quickedit-image-text">' + settings.text + '</span>' + '</div>';
  };

  Drupal.theme.quickeditImageToolbar = function (settings) {
    var html = '<form class="quickedit-image-field-info">';
    if (settings.alt_field) {
      html += '  <div>' + '    <label for="alt" class="' + (settings.alt_field_required ? 'required' : '') + '">' + Drupal.t('Alternative text') + '</label>' + '    <input type="text" placeholder="' + settings.alt + '" value="' + settings.alt + '" name="alt" ' + (settings.alt_field_required ? 'required' : '') + '/>' + '  </div>';
    }
    if (settings.title_field) {
      html += '  <div>' + '    <label for="title" class="' + (settings.title_field_required ? 'form-required' : '') + '">' + Drupal.t('Title') + '</label>' + '    <input type="text" placeholder="' + settings.title + '" value="' + settings.title + '" name="title" ' + (settings.title_field_required ? 'required' : '') + '/>' + '  </div>';
    }
    html += '</form>';

    return html;
  };
})(Drupal);