/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(Modernizr => {
  Modernizr.addTest('touchevents', () => {
    let bool;

    if ('ontouchstart' in window || window.DocumentTouch && document instanceof window.DocumentTouch) {
      bool = true;
    } else {
      const query = ['@media (', Modernizr._prefixes.join('touch-enabled),('), 'heartz', ')', '{#modernizr{top:9px;position:absolute}}'].join('');
      Modernizr.testStyles(query, node => {
        bool = node.offsetTop === 9;
      });
    }

    return bool;
  });
})(Modernizr);