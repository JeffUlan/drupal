/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.color = {
    callback: function callback(context, settings, form, farb, height, width) {
      var accum = void 0;
      var delta = void 0;

      form.find('.color-preview').css('backgroundColor', form.find('.color-palette input[name="palette[base]"]').val());

      form.find('#text').css('color', form.find('.color-palette input[name="palette[text]"]').val());
      form.find('#text a, #text h2').css('color', form.find('.color-palette input[name="palette[link]"]').val());

      function gradientLineColor(i, element) {
        Object.keys(accum || {}).forEach(function (k) {
          accum[k] += delta[k];
        });
        element.style.backgroundColor = farb.pack(accum);
      }

      var colorStart = void 0;
      var colorEnd = void 0;
      Object.keys(settings.gradients || {}).forEach(function (i) {
        colorStart = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[0] + ']"]').val());
        colorEnd = farb.unpack(form.find('.color-palette input[name="palette[' + settings.gradients[i].colors[1] + ']"]').val());
        if (colorStart && colorEnd) {
          delta = [];
          Object.keys(colorStart || {}).forEach(function (colorStartKey) {
            delta[colorStartKey] = (colorEnd[colorStartKey] - colorStart[colorStartKey]) / (settings.gradients[i].vertical ? height[i] : width[i]);
          });
          accum = colorStart;

          form.find('#gradient-' + i + ' > div').each(gradientLineColor);
        }
      });
    }
  };
})(jQuery, Drupal);