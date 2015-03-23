<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\RequestFormatCacheContext.
 */

namespace Drupal\Core\Cache;

/**
 * Defines the RequestFormatCacheContext service, for "per format" caching.
 */
class RequestFormatCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Request format');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->requestStack->getCurrentRequest()->getRequestFormat();
  }

}
