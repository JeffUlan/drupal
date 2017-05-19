/**
 * @file
 * A Backbone View acting as a controller for CKEditor toolbar configuration.
 */

(function ($, Drupal, Backbone, CKEDITOR, _) {

  'use strict';

  Drupal.ckeditor.ControllerView = Backbone.View.extend(/** @lends Drupal.ckeditor.ControllerView# */{

    /**
     * @type {object}
     */
    events: {},

    /**
     * Backbone View acting as a controller for CKEditor toolbar configuration.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize: function () {
      this.getCKEditorFeatures(this.model.get('hiddenEditorConfig'), this.disableFeaturesDisallowedByFilters.bind(this));

      // Push the active editor configuration to the textarea.
      this.model.listenTo(this.model, 'change:activeEditorConfig', this.model.sync);
      this.listenTo(this.model, 'change:isDirty', this.parseEditorDOM);
    },

    /**
     * Converts the active toolbar DOM structure to an object representation.
     *
     * @param {Drupal.ckeditor.ConfigurationModel} model
     *   The state model for the CKEditor configuration.
     * @param {bool} isDirty
     *   Tracks whether the active toolbar DOM structure has been changed.
     *   isDirty is toggled back to false in this method.
     * @param {object} options
     *   An object that includes:
     * @param {bool} [options.broadcast]
     *   A flag that controls whether a CKEditorToolbarChanged event should be
     *   fired for configuration changes.
     *
     * @fires event:CKEditorToolbarChanged
     */
    parseEditorDOM: function (model, isDirty, options) {
      if (isDirty) {
        var currentConfig = this.model.get('activeEditorConfig');

        // Process the rows.
        var rows = [];
        this.$el
          .find('.ckeditor-active-toolbar-configuration')
          .children('.ckeditor-row').each(function () {
            var groups = [];
            // Process the button groups.
            $(this).find('.ckeditor-toolbar-group').each(function () {
              var $group = $(this);
              var $buttons = $group.find('.ckeditor-button');
              if ($buttons.length) {
                var group = {
                  name: $group.attr('data-drupal-ckeditor-toolbar-group-name'),
                  items: []
                };
                $group.find('.ckeditor-button, .ckeditor-multiple-button').each(function () {
                  group.items.push($(this).attr('data-drupal-ckeditor-button-name'));
                });
                groups.push(group);
              }
            });
            if (groups.length) {
              rows.push(groups);
            }
          });
        this.model.set('activeEditorConfig', rows);
        // Mark the model as clean. Whether or not the sync to the textfield
        // occurs depends on the activeEditorConfig attribute firing a change
        // event. The DOM has at least been processed and posted, so as far as
        // the model is concerned, it is clean.
        this.model.set('isDirty', false);

        // Determine whether we should trigger an event.
        if (options.broadcast !== false) {
          var prev = this.getButtonList(currentConfig);
          var next = this.getButtonList(rows);
          if (prev.length !== next.length) {
            this.$el
              .find('.ckeditor-toolbar-active')
              .trigger('CKEditorToolbarChanged', [
                (prev.length < next.length) ? 'added' : 'removed',
                _.difference(_.union(prev, next), _.intersection(prev, next))[0]
              ]);
          }
        }
      }
    },

    /**
     * Asynchronously retrieve the metadata for all available CKEditor features.
     *
     * In order to get a list of all features needed by CKEditor, we create a
     * hidden CKEditor instance, then check the CKEditor's "allowedContent"
     * filter settings. Because creating an instance is expensive, a callback
     * must be provided that will receive a hash of {@link Drupal.EditorFeature}
     * features keyed by feature (button) name.
     *
     * @param {object} CKEditorConfig
     *   An object that represents the configuration settings for a CKEditor
     *   editor component.
     * @param {function} callback
     *   A function to invoke when the instanceReady event is fired by the
     *   CKEditor object.
     */
    getCKEditorFeatures: function (CKEditorConfig, callback) {
      var getProperties = function (CKEPropertiesList) {
        return (_.isObject(CKEPropertiesList)) ? _.keys(CKEPropertiesList) : [];
      };

      var convertCKERulesToEditorFeature = function (feature, CKEFeatureRules) {
        for (var i = 0; i < CKEFeatureRules.length; i++) {
          var CKERule = CKEFeatureRules[i];
          var rule = new Drupal.EditorFeatureHTMLRule();

          // Tags.
          var tags = getProperties(CKERule.elements);
          rule.required.tags = (CKERule.propertiesOnly) ? [] : tags;
          rule.allowed.tags = tags;
          // Attributes.
          rule.required.attributes = getProperties(CKERule.requiredAttributes);
          rule.allowed.attributes = getProperties(CKERule.attributes);
          // Styles.
          rule.required.styles = getProperties(CKERule.requiredStyles);
          rule.allowed.styles = getProperties(CKERule.styles);
          // Classes.
          rule.required.classes = getProperties(CKERule.requiredClasses);
          rule.allowed.classes = getProperties(CKERule.classes);
          // Raw.
          rule.raw = CKERule;

          feature.addHTMLRule(rule);
        }
      };

      // Create hidden CKEditor with all features enabled, retrieve metadata.
      // @see \Drupal\ckeditor\Plugin\Editor\CKEditor::buildConfigurationForm().
      var hiddenCKEditorID = 'ckeditor-hidden';
      if (CKEDITOR.instances[hiddenCKEditorID]) {
        CKEDITOR.instances[hiddenCKEditorID].destroy(true);
      }
      // Load external plugins, if any.
      var hiddenEditorConfig = this.model.get('hiddenEditorConfig');
      if (hiddenEditorConfig.drupalExternalPlugins) {
        var externalPlugins = hiddenEditorConfig.drupalExternalPlugins;
        for (var pluginName in externalPlugins) {
          if (externalPlugins.hasOwnProperty(pluginName)) {
            CKEDITOR.plugins.addExternal(pluginName, externalPlugins[pluginName], '');
          }
        }
      }
      CKEDITOR.inline($('#' + hiddenCKEditorID).get(0), CKEditorConfig);

      // Once the instance is ready, retrieve the allowedContent filter rules
      // and convert them to Drupal.EditorFeature objects.
      CKEDITOR.once('instanceReady', function (e) {
        if (e.editor.name === hiddenCKEditorID) {
          // First collect all CKEditor allowedContent rules.
          var CKEFeatureRulesMap = {};
          var rules = e.editor.filter.allowedContent;
          var rule;
          var name;
          for (var i = 0; i < rules.length; i++) {
            rule = rules[i];
            name = rule.featureName || ':(';
            if (!CKEFeatureRulesMap[name]) {
              CKEFeatureRulesMap[name] = [];
            }
            CKEFeatureRulesMap[name].push(rule);
          }

          // Now convert these to Drupal.EditorFeature objects. And track which
          // buttons are mapped to which features.
          // @see getFeatureForButton()
          var features = {};
          var buttonsToFeatures = {};
          for (var featureName in CKEFeatureRulesMap) {
            if (CKEFeatureRulesMap.hasOwnProperty(featureName)) {
              var feature = new Drupal.EditorFeature(featureName);
              convertCKERulesToEditorFeature(feature, CKEFeatureRulesMap[featureName]);
              features[featureName] = feature;
              var command = e.editor.getCommand(featureName);
              if (command) {
                buttonsToFeatures[command.uiItems[0].name] = featureName;
              }
            }
          }

          callback(features, buttonsToFeatures);
        }
      });
    },

    /**
     * Retrieves the feature for a given button from featuresMetadata. Returns
     * false if the given button is in fact a divider.
     *
     * @param {string} button
     *   The name of a CKEditor button.
     *
     * @return {object}
     *   The feature metadata object for a button.
     */
    getFeatureForButton: function (button) {
      // Return false if the button being added is a divider.
      if (button === '-') {
        return false;
      }

      // Get a Drupal.editorFeature object that contains all metadata for
      // the feature that was just added or removed. Not every feature has
      // such metadata.
      var featureName = this.model.get('buttonsToFeatures')[button.toLowerCase()];
      // Features without an associated command do not have a 'feature name' by
      // default, so we use the lowercased button name instead.
      if (!featureName) {
        featureName = button.toLowerCase();
      }
      var featuresMetadata = this.model.get('featuresMetadata');
      if (!featuresMetadata[featureName]) {
        featuresMetadata[featureName] = new Drupal.EditorFeature(featureName);
        this.model.set('featuresMetadata', featuresMetadata);
      }
      return featuresMetadata[featureName];
    },

    /**
     * Checks buttons against filter settings; disables disallowed buttons.
     *
     * @param {object} features
     *   A map of {@link Drupal.EditorFeature} objects.
     * @param {object} buttonsToFeatures
     *   Object containing the button-to-feature mapping.
     *
     * @see Drupal.ckeditor.ControllerView#getFeatureForButton
     */
    disableFeaturesDisallowedByFilters: function (features, buttonsToFeatures) {
      this.model.set('featuresMetadata', features);
      // Store the button-to-feature mapping. Needs to happen only once, because
      // the same buttons continue to have the same features; only the rules for
      // specific features may change.
      // @see getFeatureForButton()
      this.model.set('buttonsToFeatures', buttonsToFeatures);

      // Ensure that toolbar configuration changes are broadcast.
      this.broadcastConfigurationChanges(this.$el);

      // Initialization: not all of the default toolbar buttons may be allowed
      // by the current filter settings. Remove any of the default toolbar
      // buttons that require more permissive filter settings. The remaining
      // default toolbar buttons are marked as "added".
      var existingButtons = [];
      // Loop through each button group after flattening the groups from the
      // toolbar row arrays.
      var buttonGroups = _.flatten(this.model.get('activeEditorConfig'));
      for (var i = 0; i < buttonGroups.length; i++) {
        // Pull the button names from each toolbar button group.
        var buttons = buttonGroups[i].items;
        for (var k = 0; k < buttons.length; k++) {
          existingButtons.push(buttons[k]);
        }
      }
      // Remove duplicate buttons.
      existingButtons = _.unique(existingButtons);
      // Prepare the active toolbar and available-button toolbars.
      for (var n = 0; n < existingButtons.length; n++) {
        var button = existingButtons[n];
        var feature = this.getFeatureForButton(button);
        // Skip dividers.
        if (feature === false) {
          continue;
        }

        if (Drupal.editorConfiguration.featureIsAllowedByFilters(feature)) {
          // Existing toolbar buttons are in fact "added features".
          this.$el.find('.ckeditor-toolbar-active').trigger('CKEditorToolbarChanged', ['added', existingButtons[n]]);
        }
        else {
          // Move the button element from the active the active toolbar to the
          // list of available buttons.
          $('.ckeditor-toolbar-active li[data-drupal-ckeditor-button-name="' + button + '"]')
            .detach()
            .appendTo('.ckeditor-toolbar-disabled > .ckeditor-toolbar-available > ul');
          // Update the toolbar value field.
          this.model.set({isDirty: true}, {broadcast: false});
        }
      }
    },

    /**
     * Sets up broadcasting of CKEditor toolbar configuration changes.
     *
     * @param {jQuery} $ckeditorToolbar
     *   The active toolbar DOM element wrapped in jQuery.
     */
    broadcastConfigurationChanges: function ($ckeditorToolbar) {
      var view = this;
      var hiddenEditorConfig = this.model.get('hiddenEditorConfig');
      var getFeatureForButton = this.getFeatureForButton.bind(this);
      var getCKEditorFeatures = this.getCKEditorFeatures.bind(this);
      $ckeditorToolbar
        .find('.ckeditor-toolbar-active')
        // Listen for CKEditor toolbar configuration changes. When a button is
        // added/removed, call an appropriate Drupal.editorConfiguration method.
        .on('CKEditorToolbarChanged.ckeditorAdmin', function (event, action, button) {
          var feature = getFeatureForButton(button);

          // Early-return if the button being added is a divider.
          if (feature === false) {
            return;
          }

          // Trigger a standardized text editor configuration event to indicate
          // whether a feature was added or removed, so that filters can react.
          var configEvent = (action === 'added') ? 'addedFeature' : 'removedFeature';
          Drupal.editorConfiguration[configEvent](feature);
        })
        // Listen for CKEditor plugin settings changes. When a plugin setting is
        // changed, rebuild the CKEditor features metadata.
        .on('CKEditorPluginSettingsChanged.ckeditorAdmin', function (event, settingsChanges) {
          // Update hidden CKEditor configuration.
          for (var key in settingsChanges) {
            if (settingsChanges.hasOwnProperty(key)) {
              hiddenEditorConfig[key] = settingsChanges[key];
            }
          }

          // Retrieve features for the updated hidden CKEditor configuration.
          getCKEditorFeatures(hiddenEditorConfig, function (features) {
            // Trigger a standardized text editor configuration event for each
            // feature that was modified by the configuration changes.
            var featuresMetadata = view.model.get('featuresMetadata');
            for (var name in features) {
              if (features.hasOwnProperty(name)) {
                var feature = features[name];
                if (featuresMetadata.hasOwnProperty(name) && !_.isEqual(featuresMetadata[name], feature)) {
                  Drupal.editorConfiguration.modifiedFeature(feature);
                }
              }
            }
            // Update the CKEditor features metadata.
            view.model.set('featuresMetadata', features);
          });
        });
    },

    /**
     * Returns the list of buttons from an editor configuration.
     *
     * @param {object} config
     *   A CKEditor configuration object.
     *
     * @return {Array}
     *   A list of buttons in the CKEditor configuration.
     */
    getButtonList: function (config) {
      var buttons = [];
      // Remove the rows.
      config = _.flatten(config);

      // Loop through the button groups and pull out the buttons.
      config.forEach(function (group) {
        group.items.forEach(function (button) {
          buttons.push(button);
        });
      });

      // Remove the dividing elements if any.
      return _.without(buttons, '-');
    }
  });

})(jQuery, Drupal, Backbone, CKEDITOR, _);
