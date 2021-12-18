/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal, Backbone) {
  Drupal.contextual.KeyboardView = Backbone.View.extend({
    events: {
      'focus .trigger': 'focus',
      'focus .contextual-links a': 'focus',
      'blur .trigger': function () {
        this.model.blur();
      },
      'blur .contextual-links a': function () {
        const that = this;
        this.timer = window.setTimeout(() => {
          that.model.close().blur();
        }, 150);
      }
    },

    initialize() {
      this.timer = NaN;
    },

    focus() {
      window.clearTimeout(this.timer);
      this.model.focus();
    }

  });
})(Drupal, Backbone);