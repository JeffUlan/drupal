<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\cache\None.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Caching plugin that provides no caching at all.
 *
 * @ingroup views_cache_plugins
 *
 * @Plugin(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("No caching of Views data."),
 *   help_topic = "cache-none"
 * )
 */
class None extends CachePluginBase {

  function cache_start() { /* do nothing */ }

  function summary_title() {
    return t('None');
  }

  function cache_get($type) {
    return FALSE;
  }

  function cache_set($type) { }

}
