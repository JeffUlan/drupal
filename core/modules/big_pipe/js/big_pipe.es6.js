/**
 * @file
 * Renders BigPipe placeholders using Drupal's Ajax system.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Executes Ajax commands in <script type="application/vnd.drupal-ajax"> tag.
   *
   * These Ajax commands replace placeholders with HTML and load missing CSS/JS.
   *
   * @param {number} index
   *   Current index.
   * @param {HTMLScriptElement} placeholderReplacement
   *   Script tag created by BigPipe.
   */
  function bigPipeProcessPlaceholderReplacement(index, placeholderReplacement) {
    const placeholderId = placeholderReplacement.getAttribute('data-big-pipe-replacement-for-placeholder-with-id');
    const content = this.textContent.trim();
    // Ignore any placeholders that are not in the known placeholder list. Used
    // to avoid someone trying to XSS the site via the placeholdering mechanism.
    if (typeof drupalSettings.bigPipePlaceholderIds[placeholderId] !== 'undefined') {
      // If we try to parse the content too early (when the JSON containing Ajax
      // commands is still arriving), textContent will be empty which will cause
      // JSON.parse() to fail. Remove once so that it can be processed again
      // later.
      // @see bigPipeProcessDocument()
      if (content === '') {
        $(this).removeOnce('big-pipe');
      }
      else {
        const response = JSON.parse(content);
        // Create a Drupal.Ajax object without associating an element, a
        // progress indicator or a URL.
        const ajaxObject = Drupal.ajax({
          url: '',
          base: false,
          element: false,
          progress: false,
        });
        // Then, simulate an AJAX response having arrived, and let the Ajax
        // system handle it.
        ajaxObject.success(response, 'success');
      }
    }
  }

  /**
   * Processes a streamed HTML document receiving placeholder replacements.
   *
   * @param {HTMLDocument} context
   *   The HTML document containing <script type="application/vnd.drupal-ajax">
   *   tags generated by BigPipe.
   *
   * @return {bool}
   *   Returns true when processing has been finished and a stop signal has been
   *   found.
   */
  function bigPipeProcessDocument(context) {
    // Make sure we have BigPipe-related scripts before processing further.
    if (!context.querySelector('script[data-big-pipe-event="start"]')) {
      return false;
    }

    $(context).find('script[data-big-pipe-replacement-for-placeholder-with-id]')
      .once('big-pipe')
      .each(bigPipeProcessPlaceholderReplacement);

    // If we see the stop signal, clear the timeout: all placeholder
    // replacements are guaranteed to be received and processed.
    if (context.querySelector('script[data-big-pipe-event="stop"]')) {
      if (timeoutID) {
        clearTimeout(timeoutID);
      }
      return true;
    }

    return false;
  }

  function bigPipeProcess() {
    timeoutID = setTimeout(() => {
      if (!bigPipeProcessDocument(document)) {
        bigPipeProcess();
      }
    }, interval);
  }

  // The frequency with which to check for newly arrived BigPipe placeholders.
  // Hence 50 ms means we check 20 times per second. Setting this to 100 ms or
  // more would cause the user to see content appear noticeably slower.
  var interval = drupalSettings.bigPipeInterval || 50;
  // The internal ID to contain the watcher service.
  let timeoutID;

  bigPipeProcess();

  // If something goes wrong, make sure everything is cleaned up and has had a
  // chance to be processed with everything loaded.
  $(window).on('load', () => {
    if (timeoutID) {
      clearTimeout(timeoutID);
    }
    bigPipeProcessDocument(document);
  });
}(jQuery, Drupal, drupalSettings));
