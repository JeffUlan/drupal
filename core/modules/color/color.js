/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.color = {
    attach: function attach(context, settings) {
      var i;
      var j;
      var colors;
      var form = $(context).find('#system-theme-settings .color-form').once('color');

      if (form.length === 0) {
        return;
      }

      var inputs = [];
      var hooks = [];
      var locks = [];
      var focused = null;
      $('<div class="color-placeholder"></div>').once('color').prependTo(form);
      var farb = $.farbtastic('.color-placeholder');
      var reference = settings.color.reference;
      Object.keys(reference || {}).forEach(function (color) {
        reference[color] = farb.RGBToHSL(farb.unpack(reference[color]));
      });
      var height = [];
      var width = [];

      function preview() {
        Drupal.color.callback(context, settings, form, farb, height, width);
      }

      function resetScheme() {
        form.find('#edit-scheme').each(function () {
          this.selectedIndex = this.options.length - 1;
        });
      }

      function shiftColor(given, ref1, ref2) {
        var d;
        given = farb.RGBToHSL(farb.unpack(given));
        given[0] += ref2[0] - ref1[0];

        if (ref1[1] === 0 || ref2[1] === 0) {
          given[1] = ref2[1];
        } else {
          d = ref1[1] / ref2[1];

          if (d > 1) {
            given[1] /= d;
          } else {
            given[1] = 1 - (1 - given[1]) * d;
          }
        }

        if (ref1[2] === 0 || ref2[2] === 0) {
          given[2] = ref2[2];
        } else {
          d = ref1[2] / ref2[2];

          if (d > 1) {
            given[2] /= d;
          } else {
            given[2] = 1 - (1 - given[2]) * d;
          }
        }

        return farb.pack(farb.HSLToRGB(given));
      }

      function callback(input, color, propagate, colorScheme) {
        var matched;
        $(input).css({
          backgroundColor: color,
          color: farb.RGBToHSL(farb.unpack(color))[2] > 0.5 ? '#000' : '#fff'
        });

        if ($(input).val() && $(input).val() !== color) {
          $(input).val(color);

          if (propagate) {
            i = input.i;

            for (j = i + 1;; ++j) {
              if (!locks[j - 1] || $(locks[j - 1]).is('.is-unlocked')) {
                break;
              }

              matched = shiftColor(color, reference[input.key], reference[inputs[j].key]);
              callback(inputs[j], matched, false);
            }

            for (j = i - 1;; --j) {
              if (!locks[j] || $(locks[j]).is('.is-unlocked')) {
                break;
              }

              matched = shiftColor(color, reference[input.key], reference[inputs[j].key]);
              callback(inputs[j], matched, false);
            }

            preview();
          }

          if (!colorScheme) {
            resetScheme();
          }
        }
      }

      Object.keys(settings.gradients || {}).forEach(function (i) {
        $('.color-preview').once('color').append("<div id=\"gradient-".concat(i, "\"></div>"));
        var gradient = $(".color-preview #gradient-".concat(i));
        height.push(parseInt(gradient.css('height'), 10) / 10);
        width.push(parseInt(gradient.css('width'), 10) / 10);

        for (j = 0; j < (settings.gradients[i].direction === 'vertical' ? height[i] : width[i]); ++j) {
          gradient.append('<div class="gradient-line"></div>');
        }
      });
      form.find('#edit-scheme').on('change', function () {
        var schemes = settings.color.schemes;
        var colorScheme = this.options[this.selectedIndex].value;

        if (colorScheme !== '' && schemes[colorScheme]) {
          colors = schemes[colorScheme];
          Object.keys(colors || {}).forEach(function (fieldName) {
            callback($("#edit-palette-".concat(fieldName)), colors[fieldName], false, true);
          });
          preview();
        }
      });

      function focus(e) {
        var input = e.target;

        if (focused) {
          $(focused).off('keyup', farb.updateValue).off('keyup', preview).off('keyup', resetScheme).parent().removeClass('item-selected');
        }

        focused = input;
        farb.linkTo(function (color) {
          callback(input, color, true, false);
        });
        farb.setColor(input.value);
        $(focused).on('keyup', farb.updateValue).on('keyup', preview).on('keyup', resetScheme).parent().addClass('item-selected');
      }

      form.find('.js-color-palette input.form-text').each(function () {
        this.key = this.id.substring(13);
        farb.linkTo(function () {}).setColor('#000').linkTo(this);
        var i = inputs.length;

        if (inputs.length) {
          var toggleClick = true;
          var lock = $("<button class=\"color-palette__lock\">".concat(Drupal.t('Unlock'), "</button>")).on('click', function (e) {
            e.preventDefault();

            if (toggleClick) {
              $(this).addClass('is-unlocked').html(Drupal.t('Lock'));
              $(hooks[i - 1]).attr('class', locks[i - 2] && $(locks[i - 2]).is(':not(.is-unlocked)') ? 'color-palette__hook is-up' : 'color-palette__hook');
              $(hooks[i]).attr('class', locks[i] && $(locks[i]).is(':not(.is-unlocked)') ? 'color-palette__hook is-down' : 'color-palette__hook');
            } else {
              $(this).removeClass('is-unlocked').html(Drupal.t('Unlock'));
              $(hooks[i - 1]).attr('class', locks[i - 2] && $(locks[i - 2]).is(':not(.is-unlocked)') ? 'color-palette__hook is-both' : 'color-palette__hook is-down');
              $(hooks[i]).attr('class', locks[i] && $(locks[i]).is(':not(.is-unlocked)') ? 'color-palette__hook is-both' : 'color-palette__hook is-up');
            }

            toggleClick = !toggleClick;
          });
          $(this).after(lock);
          locks.push(lock);
        }

        var hook = $('<div class="color-palette__hook"></div>');
        $(this).after(hook);
        hooks.push(hook);
        $(this).parent().find('.color-palette__lock').trigger('click');
        this.i = i;
        inputs.push(this);
      }).on('focus', focus);
      form.find('.js-color-palette label');
      inputs[0].focus();
      preview();
    }
  };
})(jQuery, Drupal);