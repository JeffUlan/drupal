/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.batch = {
    attach(context, settings) {
      const batch = settings.batch;
      const $progress = $(once('batch', '[data-drupal-progress]'));
      let progressBar;

      function updateCallback(progress, status, pb) {
        if (progress === '100') {
          pb.stopMonitoring();
          window.location = `${batch.uri}&op=finished`;
        }
      }

      function errorCallback(pb) {
        $progress.prepend($('<p class="error"></p>').html(batch.errorMessage));
        $('#wait').hide();
      }

      if ($progress.length) {
        progressBar = new Drupal.ProgressBar('updateprogress', updateCallback, 'POST', errorCallback);
        progressBar.setProgress(-1, batch.initMessage);
        progressBar.startMonitoring(`${batch.uri}&op=do`, 10);
        $progress.empty();
        $progress.append(progressBar.element);
      }
    }

  };
})(jQuery, Drupal);