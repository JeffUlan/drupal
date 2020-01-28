/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.js_webassert_test_wait_for_ajax_request = {
    attach: function attach() {
      $('#edit-test-assert-no-element-after-wait-pass').on('click', function (e) {
        e.preventDefault();
        setTimeout(function () {
          $('#edit-test-assert-no-element-after-wait-pass').remove();
        }, 500);
      });

      $('#edit-test-assert-no-element-after-wait-fail').on('click', function (e) {
        e.preventDefault();
        setTimeout(function () {
          $('#edit-test-assert-no-element-after-wait-fail').remove();
        }, 2000);
      });
    }
  };
})(jQuery, Drupal);