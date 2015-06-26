<?php

/**
 * @file
 * Contains \Drupal\Core\StreamWrapper\PublicStream.
 */

namespace Drupal\Core\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a Drupal public (public://) stream wrapper class.
 *
 * Provides support for storing publicly accessible files with the Drupal file
 * interface.
 */
class PublicStream extends LocalStream {

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::LOCAL_NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Public files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Public local files served by the webserver.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return static::basePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    return $GLOBALS['base_url'] . '/' . self::getDirectoryPath() . '/' . UrlHelper::encodePath($path);
  }

  /**
   * Returns the base path for public://.
   *
   * If we have a setting for the public:// scheme's path, we use that.
   * Otherwise we build a reasonable default based on the site.path service if
   * it's available, or a default behavior based on the request.
   *
   * The site path is injectable from the site.path service:
   * @code
   * $base_path = PublicStream::basePath(\Drupal::service('site.path'));
   * @endcode
   *
   * @param \SplString $site_path
   *   (optional) The site.path service parameter, which is typically the path
   *   to sites/ in a Drupal installation. This allows you to inject the site
   *   path using services from the caller. If omitted, this method will use the
   *   global service container or the kernel's default behavior to determine
   *   the site path.
   *
   * @return string
   *   The base path for public:// typically sites/default/files.
   */
  public static function basePath(\SplString $site_path = NULL) {
    if ($site_path === NULL) {
      // Find the site path. Kernel service is not always available at this
      // point, but is preferred, when available.
      if (\Drupal::hasService('kernel')) {
        $site_path = \Drupal::service('site.path');
      }
      else {
        // If there is no kernel available yet, we call the static
        // findSitePath().
        $site_path = DrupalKernel::findSitePath(Request::createFromGlobals());
      }
    }
    return Settings::get('file_public_path', $site_path . '/files');
  }

}
