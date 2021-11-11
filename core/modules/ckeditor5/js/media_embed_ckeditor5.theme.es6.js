/**
 * @file
 * Theme elements for the Media Embed CKEditor5 plugin.
 */

((Drupal) => {
  /**
   * Themes the error displayed when the media embed preview fails.
   *
   * @return {string}
   *   A string representing a DOM fragment.
   *
   * @see media-embed-error.html.twig
   */
  Drupal.theme.mediaEmbedPreviewError = () =>
    `<div>${Drupal.t(
      'An error occurred while trying to preview the media. Please save your work and reload this page.',
    )}</div>`;
})(Drupal);
