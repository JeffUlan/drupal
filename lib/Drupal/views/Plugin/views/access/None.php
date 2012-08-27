<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\access\None.
 */

namespace Drupal\views\Plugin\views\access;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Annotation\Plugin;

/**
 * Access plugin that provides no access control at all.
 *
 * @ingroup views_access_plugins
 *
 * @Plugin(
 *   id = "none",
 *   title = @Translation("None"),
 *   help = @Translation("Will be available to all users.")
 * )
 */
class None extends AccessPluginBase {

  function summary_title() {
    return t('Unrestricted');
  }

}
