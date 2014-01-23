(function ($, Drupal, drupalSettings) {

"use strict";

/**
 * Attach the machine-readable name form element behavior.
 */
Drupal.behaviors.machineName = {
  /**
   * Attaches the behavior.
   *
   * @param settings.machineName
   *   A list of elements to process, keyed by the HTML ID of the form element
   *   containing the human-readable value. Each element is an object defining
   *   the following properties:
   *   - target: The HTML ID of the machine name form element.
   *   - suffix: The HTML ID of a container to show the machine name preview in
   *     (usually a field suffix after the human-readable name form element).
   *   - label: The label to show for the machine name preview.
   *   - replace_pattern: A regular expression (without modifiers) matching
   *     disallowed characters in the machine name; e.g., '[^a-z0-9]+'.
   *   - replace: A character to replace disallowed characters with; e.g., '_'
   *     or '-'.
   *   - standalone: Whether the preview should stay in its own element rather
   *     than the suffix of the source element.
   *   - field_prefix: The #field_prefix of the form element.
   *   - field_suffix: The #field_suffix of the form element.
   */
  attach: function (context, settings) {
    var self = this;
    var $context = $(context);
    var source_id, options, machine, eventData;

    function clickEditHandler(e) {
      var data = e.data;
      e.preventDefault();
      data.$wrapper.show();
      data.$target.trigger('focus');
      data.$suffix.hide();
      data.$source.off('.machineName');
    }

    function machineNameHandler(e) {
      var data = e.data;
      var settings = data.options;
      var baseValue = $(e.target).val();

      var rx = new RegExp(settings.replace_pattern, 'g');
      var expected = baseValue.toLowerCase().replace(rx, settings.replace).substr(0, settings.maxlength);

      if(baseValue.toLowerCase() !== expected) {
        self.transliterate(baseValue, settings).done(function (machine) {
          self.showMachineName(machine.substr(0, settings.maxlength), data);
        });
      }
      else {
        self.showMachineName(expected, data);
      }
    }

    for (source_id in settings.machineName) {
      if (settings.machineName.hasOwnProperty(source_id)) {
        options =  settings.machineName[source_id];

        var $source = $context.find(source_id).addClass('machine-name-source').once('machine-name');
        var $target = $context.find(options.target).addClass('machine-name-target');
        var $suffix = $context.find(options.suffix);
        var $wrapper = $target.closest('.form-item');
        // All elements have to exist.
        if (!$source.length || !$target.length || !$suffix.length || !$wrapper.length) {
          return;
        }
        // Skip processing upon a form validation error on the machine name.
        if ($target.hasClass('error')) {
          return;
        }
        // Figure out the maximum length for the machine name.
        options.maxlength = $target.attr('maxlength');
        // Hide the form item container of the machine name form element.
        $wrapper.hide();
        // Determine the initial machine name value. Unless the machine name form
        // element is disabled or not empty, the initial default value is based on
        // the human-readable form element value.
        if ($target.is(':disabled') || $target.val() !== '') {
          machine = $target.val();
        }
        else {
          machine = self.transliterate($source.val(), options);
        }
        // Append the machine name preview to the source field.
        var $preview = $('<span class="machine-name-value">' + options.field_prefix + Drupal.checkPlain(machine) + options.field_suffix + '</span>');
        $suffix.empty();
        if (options.label) {
          $suffix.append(' ').append('<span class="machine-name-label">' + options.label + ':</span>');
        }
        $suffix.append(' ').append($preview);

        // If the machine name cannot be edited, stop further processing.
        if ($target.is(':disabled')) {
          return;
        }

        eventData = {
          $source: $source,
          $target: $target,
          $suffix: $suffix,
          $wrapper: $wrapper,
          $preview: $preview,
          options: options
        };
        // If it is editable, append an edit link.
        var $link = $('<span class="admin-link"><button type="button" class="link">' + Drupal.t('Edit') + '</button></span>').on('click', eventData, clickEditHandler);
        $suffix.append(' ').append($link);

        // Preview the machine name in realtime when the human-readable name
        // changes, but only if there is no machine name yet; i.e., only upon
        // initial creation, not when editing.
        if ($target.val() === '') {
          $source.on('keyup.machineName change.machineName input.machineName', eventData, machineNameHandler)
          // Initialize machine name preview.
          .trigger('keyup');
        }
      }
    }
  },

  showMachineName: function (machine, data) {
    var settings = data.options;
    // Set the machine name to the transliterated value.
    if (machine !== '') {
      if (machine !== settings.replace) {
        data.$target.val(machine);
        data.$preview.html(settings.field_prefix + Drupal.checkPlain(machine) + settings.field_suffix);
      }
      data.$suffix.show();
    }
    else {
      data.$suffix.hide();
      data.$target.val(machine);
      data.$preview.empty();
    }
  },

  /**
   * Transliterate a human-readable name to a machine name.
   *
   * @param source
   *   A string to transliterate.
   * @param settings
   *   The machine name settings for the corresponding field, containing:
   *   - replace_pattern: A regular expression (without modifiers) matching
   *     disallowed characters in the machine name; e.g., '[^a-z0-9]+'.
   *   - replace: A character to replace disallowed characters with; e.g., '_'
   *     or '-'.
   *   - maxlength: The maximum length of the machine name.
   *
   * @return
   *   The transliterated source string.
   */
  transliterate: function (source, settings) {
    return $.get(drupalSettings.path.basePath + 'machine_name/transliterate', {
      text: source,
      langcode: drupalSettings.langcode,
      replace_pattern: settings.replace_pattern,
      replace: settings.replace,
      lowercase: true
    });
  }
};

})(jQuery, Drupal, drupalSettings);
