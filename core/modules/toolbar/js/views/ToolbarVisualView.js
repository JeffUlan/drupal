/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings, Backbone) {
  Drupal.toolbar.ToolbarVisualView = Backbone.View.extend({
    events() {
      const touchEndToClick = function (event) {
        event.preventDefault();
        event.target.click();
      };

      return {
        'click .toolbar-bar .toolbar-tab .trigger': 'onTabClick',
        'click .toolbar-toggle-orientation button': 'onOrientationToggleClick',
        'touchend .toolbar-bar .toolbar-tab .trigger': touchEndToClick,
        'touchend .toolbar-toggle-orientation button': touchEndToClick
      };
    },

    initialize(options) {
      this.strings = options.strings;
      this.listenTo(this.model, 'change:activeTab change:orientation change:isOriented change:isTrayToggleVisible', this.render);
      this.listenTo(this.model, 'change:mqMatches', this.onMediaQueryChange);
      this.listenTo(this.model, 'change:offsets', this.adjustPlacement);
      this.listenTo(this.model, 'change:activeTab change:orientation change:isOriented', this.updateToolbarHeight);
      this.$el.find('.toolbar-tray .toolbar-lining').append(Drupal.theme('toolbarOrientationToggle'));
      this.model.trigger('change:activeTab');
    },

    updateToolbarHeight() {
      const toolbarTabOuterHeight = $('#toolbar-bar').find('.toolbar-tab').outerHeight() || 0;
      const toolbarTrayHorizontalOuterHeight = $('.is-active.toolbar-tray-horizontal').outerHeight() || 0;
      this.model.set('height', toolbarTabOuterHeight + toolbarTrayHorizontalOuterHeight);
      $('body').css({
        'padding-top': this.model.get('height')
      });
      $('html').css({
        'scroll-padding-top': this.model.get('height')
      });
      this.triggerDisplace();
    },

    triggerDisplace() {
      _.defer(() => {
        Drupal.displace(true);
      });
    },

    render() {
      this.updateTabs();
      this.updateTrayOrientation();
      this.updateBarAttributes();
      $('body').removeClass('toolbar-loading');

      if (this.model.changed.orientation === 'vertical' || this.model.changed.activeTab) {
        this.loadSubtrees();
      }

      return this;
    },

    onTabClick(event) {
      if (event.currentTarget.hasAttribute('data-toolbar-tray')) {
        const activeTab = this.model.get('activeTab');
        const clickedTab = event.currentTarget;
        this.model.set('activeTab', !activeTab || clickedTab !== activeTab ? clickedTab : null);
        event.preventDefault();
        event.stopPropagation();
      }
    },

    onOrientationToggleClick(event) {
      const orientation = this.model.get('orientation');
      const antiOrientation = orientation === 'vertical' ? 'horizontal' : 'vertical';
      const locked = antiOrientation === 'vertical';

      if (locked) {
        localStorage.setItem('Drupal.toolbar.trayVerticalLocked', 'true');
      } else {
        localStorage.removeItem('Drupal.toolbar.trayVerticalLocked');
      }

      this.model.set({
        locked,
        orientation: antiOrientation
      }, {
        validate: true,
        override: true
      });
      event.preventDefault();
      event.stopPropagation();
    },

    updateTabs() {
      const $tab = $(this.model.get('activeTab'));
      $(this.model.previous('activeTab')).removeClass('is-active').prop('aria-pressed', false);
      $(this.model.previous('activeTray')).removeClass('is-active');

      if ($tab.length > 0) {
        $tab.addClass('is-active').prop('aria-pressed', true);
        const name = $tab.attr('data-toolbar-tray');
        const id = $tab.get(0).id;

        if (id) {
          localStorage.setItem('Drupal.toolbar.activeTabID', JSON.stringify(id));
        }

        const $tray = this.$el.find(`[data-toolbar-tray="${name}"].toolbar-tray`);

        if ($tray.length) {
          $tray.addClass('is-active');
          this.model.set('activeTray', $tray.get(0));
        } else {
          this.model.set('activeTray', null);
        }
      } else {
        this.model.set('activeTray', null);
        localStorage.removeItem('Drupal.toolbar.activeTabID');
      }
    },

    updateBarAttributes() {
      const isOriented = this.model.get('isOriented');

      if (isOriented) {
        this.$el.find('.toolbar-bar').attr('data-offset-top', '');
      } else {
        this.$el.find('.toolbar-bar').removeAttr('data-offset-top');
      }

      this.$el.toggleClass('toolbar-oriented', isOriented);
    },

    updateTrayOrientation() {
      const orientation = this.model.get('orientation');
      const antiOrientation = orientation === 'vertical' ? 'horizontal' : 'vertical';
      $('body').toggleClass('toolbar-vertical', orientation === 'vertical').toggleClass('toolbar-horizontal', orientation === 'horizontal');
      const removeClass = antiOrientation === 'horizontal' ? 'toolbar-tray-horizontal' : 'toolbar-tray-vertical';
      const $trays = this.$el.find('.toolbar-tray').removeClass(removeClass).addClass(`toolbar-tray-${orientation}`);
      const iconClass = `toolbar-icon-toggle-${orientation}`;
      const iconAntiClass = `toolbar-icon-toggle-${antiOrientation}`;
      const $orientationToggle = this.$el.find('.toolbar-toggle-orientation').toggle(this.model.get('isTrayToggleVisible'));
      $orientationToggle.find('button').val(antiOrientation).attr('title', this.strings[antiOrientation]).text(this.strings[antiOrientation]).removeClass(iconClass).addClass(iconAntiClass);
      const dir = document.documentElement.dir;
      const edge = dir === 'rtl' ? 'right' : 'left';
      $trays.removeAttr('data-offset-left data-offset-right data-offset-top');
      $trays.filter('.toolbar-tray-vertical.is-active').attr(`data-offset-${edge}`, '');
      $trays.filter('.toolbar-tray-horizontal.is-active').attr('data-offset-top', '');
    },

    adjustPlacement() {
      const $trays = this.$el.find('.toolbar-tray');

      if (!this.model.get('isOriented')) {
        $trays.removeClass('toolbar-tray-horizontal').addClass('toolbar-tray-vertical');
      }
    },

    loadSubtrees() {
      const $activeTab = $(this.model.get('activeTab'));
      const orientation = this.model.get('orientation');

      if (!this.model.get('areSubtreesLoaded') && typeof $activeTab.data('drupal-subtrees') !== 'undefined' && orientation === 'vertical') {
        const subtreesHash = drupalSettings.toolbar.subtreesHash;
        const theme = drupalSettings.ajaxPageState.theme;
        const endpoint = Drupal.url(`toolbar/subtrees/${subtreesHash}`);
        const cachedSubtreesHash = localStorage.getItem(`Drupal.toolbar.subtreesHash.${theme}`);
        const cachedSubtrees = JSON.parse(localStorage.getItem(`Drupal.toolbar.subtrees.${theme}`));
        const isVertical = this.model.get('orientation') === 'vertical';

        if (isVertical && subtreesHash === cachedSubtreesHash && cachedSubtrees) {
          Drupal.toolbar.setSubtrees.resolve(cachedSubtrees);
        } else if (isVertical) {
          localStorage.removeItem(`Drupal.toolbar.subtreesHash.${theme}`);
          localStorage.removeItem(`Drupal.toolbar.subtrees.${theme}`);
          Drupal.ajax({
            url: endpoint
          }).execute();
          localStorage.setItem(`Drupal.toolbar.subtreesHash.${theme}`, subtreesHash);
        }
      }
    }

  });
})(jQuery, Drupal, drupalSettings, Backbone);