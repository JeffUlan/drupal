/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal, once, tabbable) {
  function isNavOpen(navWrapper) {
    return navWrapper.classList.contains('is-active');
  }

  function toggleNav(props, state) {
    var value = !!state;
    props.navButton.setAttribute('aria-expanded', value);

    if (value) {
      props.body.classList.add('js-overlay-active');
      props.body.classList.add('js-fixed');
      props.navWrapper.classList.add('is-active');
    } else {
      props.body.classList.remove('js-overlay-active');
      props.body.classList.remove('js-fixed');
      props.navWrapper.classList.remove('is-active');
    }
  }

  function init(props) {
    props.navButton.setAttribute('aria-controls', props.navWrapperId);
    props.navButton.setAttribute('aria-expanded', 'false');
    props.navButton.addEventListener('click', function () {
      toggleNav(props, !isNavOpen(props.navWrapper));
    });
    document.addEventListener('keyup', function (e) {
      if (e.key === 'Escape') {
        if (props.olivero.areAnySubNavsOpen()) {
          props.olivero.closeAllSubNav();
        } else {
          toggleNav(props, false);
        }
      }
    });
    props.overlay.addEventListener('click', function () {
      toggleNav(props, false);
    });
    props.overlay.addEventListener('touchstart', function () {
      toggleNav(props, false);
    });
    props.header.addEventListener('keydown', function (e) {
      if (e.key === 'Tab' && isNavOpen(props.navWrapper)) {
        var tabbableNavElements = tabbable.tabbable(props.navWrapper);
        tabbableNavElements.unshift(props.navButton);
        var firstTabbableEl = tabbableNavElements[0];
        var lastTabbableEl = tabbableNavElements[tabbableNavElements.length - 1];

        if (e.shiftKey) {
          if (document.activeElement === firstTabbableEl && !props.olivero.isDesktopNav()) {
            lastTabbableEl.focus();
            e.preventDefault();
          }
        } else if (document.activeElement === lastTabbableEl && !props.olivero.isDesktopNav()) {
          firstTabbableEl.focus();
          e.preventDefault();
        }
      }
    });
    window.addEventListener('resize', function () {
      if (props.olivero.isDesktopNav()) {
        toggleNav(props, false);
        props.body.classList.remove('js-overlay-active');
        props.body.classList.remove('js-fixed');
      }

      Drupal.olivero.closeAllSubNav();
    });
  }

  Drupal.behaviors.oliveroNavigation = {
    attach: function attach(context) {
      var headerId = 'header';
      var header = once('olivero-navigation', "#".concat(headerId), context).shift();
      var navWrapperId = 'header-nav';

      if (header) {
        var navWrapper = header.querySelector('#header-nav');
        var olivero = Drupal.olivero;
        var navButton = context.querySelector('.mobile-nav-button');
        var body = context.querySelector('body');
        var overlay = context.querySelector('.overlay');
        init({
          olivero: olivero,
          header: header,
          navWrapperId: navWrapperId,
          navWrapper: navWrapper,
          navButton: navButton,
          body: body,
          overlay: overlay
        });
      }
    }
  };
})(Drupal, once, tabbable);