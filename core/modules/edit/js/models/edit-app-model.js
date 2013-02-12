/**
 * @file
 * A Backbone Model that models the current Edit application state.
 */
(function(Backbone, Drupal) {

"use strict";

Drupal.edit = Drupal.edit || {};
Drupal.edit.models = Drupal.edit.models || {};
Drupal.edit.models.EditAppModel = Backbone.Model.extend({
  defaults: {
    activeEntity: null,
    highlightedEditor: null,
    activeEditor: null,
    // Reference to a ModalView-instance if a transition requires confirmation.
    activeModal: null
  }
});

})(Backbone, Drupal);
