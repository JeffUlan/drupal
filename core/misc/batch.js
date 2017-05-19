/**
* DO NOT EDIT THIS FILE.
* All changes should be applied to ./misc/batch.es6.js
* See the following change record for more information,
* https://www.drupal.org/node/2873849
* @preserve
**/

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.batch = {
    attach: function attach(context, settings) {
      var batch = settings.batch;
      var $progress = $('[data-drupal-progress]').once('batch');
      var progressBar;

      function updateCallback(progress, status, pb) {
        if (progress === '100') {
          pb.stopMonitoring();
          window.location = batch.uri + '&op=finished';
        }
      }

      function errorCallback(pb) {
        $progress.prepend($('<p class="error"></p>').html(batch.errorMessage));
        $('#wait').hide();
      }

      if ($progress.length) {
        progressBar = new Drupal.ProgressBar('updateprogress', updateCallback, 'POST', errorCallback);
        progressBar.setProgress(-1, batch.initMessage);
        progressBar.startMonitoring(batch.uri + '&op=do', 10);

        $progress.empty();

        $progress.append(progressBar.element);
      }
    }
  };
})(jQuery, Drupal);