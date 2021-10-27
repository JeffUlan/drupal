/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  var handleFragmentLinkClickOrHashChange = function handleFragmentLinkClickOrHashChange(e, $target) {
    $target.parents('.js-vertical-tabs-pane').each(function (index, pane) {
      $(pane).data('verticalTab').focus();
    });
  };

  Drupal.behaviors.claroVerticalTabs = {
    attach: function attach(context) {
      $(once('vertical-tabs-fragments', 'body')).on('formFragmentLinkClickOrHashChange.verticalTabs', handleFragmentLinkClickOrHashChange);
      once('vertical-tabs', '[data-vertical-tabs-panes]', context).forEach(function (panes) {
        var $this = $(panes).addClass('vertical-tabs__items--processed');
        var focusID = $this.find(':hidden.vertical-tabs__active-tab').val();
        var tabFocus;
        var $details = $this.find('> details');

        if ($details.length === 0) {
          return;
        }

        var tabList = $(Drupal.theme.verticalTabListWrapper());
        $this.wrap($(Drupal.theme.verticalTabsWrapper()).addClass('js-vertical-tabs')).before(tabList);
        $details.each(function initializeVerticalTabItems() {
          var $that = $(this);
          var verticalTab = new Drupal.verticalTab({
            title: $that.find('> summary').text(),
            details: $that
          });
          tabList.append(verticalTab.item);
          $that.removeAttr('open').addClass('js-vertical-tabs-pane').data('verticalTab', verticalTab);

          if (this.id === focusID) {
            tabFocus = $that;
          }
        });

        if (!tabFocus) {
          var $locationHash = $this.find(window.location.hash);

          if (window.location.hash && $locationHash.length) {
            tabFocus = $locationHash.is('.js-vertical-tabs-pane') ? $locationHash : $locationHash.closest('.js-vertical-tabs-pane');
          } else {
            tabFocus = $this.find('> .js-vertical-tabs-pane').eq(0);
          }
        }

        if (tabFocus.length) {
          tabFocus.data('verticalTab').focus(false);
        }
      });
    }
  };

  Drupal.verticalTab = function verticalTab(settings) {
    var self = this;
    $.extend(this, settings, Drupal.theme('verticalTab', settings));
    this.item.addClass('js-vertical-tabs-menu-item');
    this.link.attr('href', "#".concat(settings.details.attr('id')));
    this.link.on('click', function (event) {
      event.preventDefault();
      self.focus();
    });
    this.details.on('toggle', function (event) {
      event.preventDefault();
    });
    this.details.find('> summary').on('click', function (event) {
      event.preventDefault();
      self.details.attr('open', true);

      if (self.details.hasClass('collapse-processed')) {
        setTimeout(function () {
          self.focus();
        }, 10);
      } else {
        self.focus();
      }
    }).on('keydown', function (event) {
      if (event.keyCode === 13) {
        setTimeout(function () {
          self.details.find(':input:visible:enabled').eq(0).trigger('focus');
        }, 10);
      }
    });
    this.link.on('keydown', function (event) {
      if (event.keyCode === 13) {
        event.preventDefault();
        self.focus();
        self.details.find(':input:visible:enabled').eq(0).trigger('focus');
      }
    });
    this.details.on('summaryUpdated', function () {
      self.updateSummary();
    }).trigger('summaryUpdated');
  };

  Drupal.verticalTab.prototype = {
    focus: function focus() {
      var triggerFocus = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      this.details.siblings('.js-vertical-tabs-pane').each(function closeOtherTabs() {
        var tab = $(this).data('verticalTab');

        if (tab.details.attr('open')) {
          tab.details.removeAttr('open').find('> summary').attr({
            'aria-expanded': 'false',
            'aria-pressed': 'false'
          });
          tab.item.removeClass('is-selected');
        }
      }).end().siblings(':hidden.vertical-tabs__active-tab').val(this.details.attr('id'));
      this.details.attr('open', true).find('> summary').attr({
        'aria-expanded': 'true',
        'aria-pressed': 'true'
      }).closest('.js-vertical-tabs').find('.js-vertical-tab-active').remove();

      if (triggerFocus) {
        var $summary = this.details.find('> summary');

        if ($summary.is(':visible')) {
          $summary.trigger('focus');
        }
      }

      this.item.addClass('is-selected');
      this.title.after($(Drupal.theme.verticalTabActiveTabIndicator()).addClass('js-vertical-tab-active'));
    },
    updateSummary: function updateSummary() {
      var summary = this.details.drupalGetSummary();
      this.summary.html(summary);
    },
    tabShow: function tabShow() {
      this.item.removeClass('vertical-tabs__menu-item--hidden').show();
      this.item.closest('.js-form-type-vertical-tabs').show();
      this.details.removeClass('vertical-tab--hidden js-vertical-tab-hidden').show();
      this.details.parent().children('.js-vertical-tabs-pane').removeClass('vertical-tabs__item--first vertical-tabs__item--last').filter(':visible').eq(0).addClass('vertical-tabs__item--first');
      this.details.parent().children('.js-vertical-tabs-pane').filter(':visible').eq(-1).addClass('vertical-tabs__item--last');
      this.focus(false);
      return this;
    },
    tabHide: function tabHide() {
      this.item.addClass('vertical-tabs__menu-item--hidden').hide();
      this.details.addClass('vertical-tab--hidden js-vertical-tab-hidden').hide();
      this.details.parent().children('.js-vertical-tabs-pane').removeClass('vertical-tabs__item--first vertical-tabs__item--last').filter(':visible').eq(0).addClass('vertical-tabs__item--first');
      this.details.parent().children('.js-vertical-tabs-pane').filter(':visible').eq(-1).addClass('vertical-tabs__item--last');
      var $firstTab = this.details.siblings('.js-vertical-tabs-pane:not(.js-vertical-tab-hidden)').eq(0);

      if ($firstTab.length) {
        $firstTab.data('verticalTab').focus(false);
      } else {
        this.item.closest('.js-form-type-vertical-tabs').hide();
      }

      return this;
    }
  };

  Drupal.theme.verticalTab = function (settings) {
    var tab = {};
    tab.item = $('<li class="vertical-tabs__menu-item" tabindex="-1"></li>').append(tab.link = $('<a href="#" class="vertical-tabs__menu-link"></a>').append($('<span class="vertical-tabs__menu-link-content"></span>').append(tab.title = $('<strong class="vertical-tabs__menu-link-title"></strong>').text(settings.title)).append(tab.summary = $('<span class="vertical-tabs__menu-link-summary"></span>'))));
    return tab;
  };

  Drupal.theme.verticalTabsWrapper = function () {
    return '<div class="vertical-tabs clearfix"></div>';
  };

  Drupal.theme.verticalTabListWrapper = function () {
    return '<ul class="vertical-tabs__menu"></ul>';
  };

  Drupal.theme.verticalTabActiveTabIndicator = function () {
    return "<span class=\"visually-hidden\">".concat(Drupal.t('(active tab)'), "</span>");
  };
})(jQuery, Drupal);