/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.color = {
    logoChanged: false,
    callback: function callback(context, settings, $form) {
      if (!this.logoChanged) {
        $('.color-preview .color-preview-logo img').attr('src', drupalSettings.color.logo);
        this.logoChanged = true;
      }

      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      $colorPreview.css('backgroundColor', $colorPalette.find('input[name="palette[bg]"]').val());

      $colorPreview.find('.color-preview-main h2, .color-preview .preview-content').css('color', $colorPalette.find('input[name="palette[text]"]').val());
      $colorPreview.find('.color-preview-content a').css('color', $colorPalette.find('input[name="palette[link]"]').val());

      var $colorPreviewBlock = $colorPreview.find('.color-preview-sidebar .color-preview-block');
      $colorPreviewBlock.css('background-color', $colorPalette.find('input[name="palette[sidebar]"]').val());
      $colorPreviewBlock.css('border-color', $colorPalette.find('input[name="palette[sidebarborders]"]').val());

      $colorPreview.find('.color-preview-footer-wrapper').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());

      var gradient_start = $colorPalette.find('input[name="palette[top]"]').val();
      var gradient_end = $colorPalette.find('input[name="palette[bottom]"]').val();

      $colorPreview.find('.color-preview-header').attr('style', 'background-color: ' + gradient_start + '; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(' + gradient_start + '), to(' + gradient_end + ')); background-image: -moz-linear-gradient(-90deg, ' + gradient_start + ', ' + gradient_end + ');');

      $colorPreview.find('.color-preview-site-name').css('color', $colorPalette.find('input[name="palette[titleslogan]"]').val());
    }
  };
})(jQuery, Drupal, drupalSettings);