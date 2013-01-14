/**
 * @file
 * Determines which editor (Create.js PropertyEditor widget) to use.
 */
(function (jQuery, Drupal, drupalSettings) {

"use strict";

  jQuery.widget('Drupal.createEditable', jQuery.Midgard.midgardEditable, {
    _create: function() {
      this.vie = this.options.vie;

      this.options.domService = 'edit';
      this.options.predicateSelector = '*'; //'.edit-field.edit-allowed';

      // The Create.js PropertyEditor widget configuration is not hardcoded; it
      // is generated by the server.
      this.options.propertyEditorWidgetsConfiguration = drupalSettings.edit.editors;

      jQuery.Midgard.midgardEditable.prototype._create.call(this);
    },

    _propertyEditorName: function(data) {
      // Pick a PropertyEditor widget for a property depending on its metadata.
      var propertyID = Drupal.edit.util.calcPropertyID(data.entity, data.property);
      return Drupal.edit.metadataCache[propertyID].editor;
    }
  });

})(jQuery, Drupal, drupalSettings);
