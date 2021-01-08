/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal) {
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
    props.navWrapper.addEventListener('keydown', function (e) {
      if (e.key === 'Tab') {
        if (e.shiftKey) {
          if (document.activeElement === props.firstFocusableEl && !props.olivero.isDesktopNav()) {
            props.navButton.focus();
            e.preventDefault();
          }
        } else if (document.activeElement === props.lastFocusableEl && !props.olivero.isDesktopNav()) {
          props.navButton.focus();
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
    attach: function attach(context, settings) {
      var navWrapperId = 'header-nav';
      var navWrapper = context.querySelector("#".concat(navWrapperId, ":not(.").concat(navWrapperId, "-processed)"));

      if (navWrapper) {
        navWrapper.classList.add("".concat(navWrapperId, "-processed"));
        var olivero = Drupal.olivero;
        var navButton = context.querySelector('.mobile-nav-button');
        var body = context.querySelector('body');
        var overlay = context.querySelector('.overlay');
        var focusableNavElements = navWrapper.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        var firstFocusableEl = focusableNavElements[0];
        var lastFocusableEl = focusableNavElements[focusableNavElements.length - 1];
        init({
          settings: settings,
          olivero: olivero,
          navWrapperId: navWrapperId,
          navWrapper: navWrapper,
          navButton: navButton,
          body: body,
          overlay: overlay,
          firstFocusableEl: firstFocusableEl,
          lastFocusableEl: lastFocusableEl
        });
      }
    }
  };
})(Drupal);