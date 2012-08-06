<?php

/**
 * @file
 * Definition of views_handler_filter_locale_group.
 */

namespace Drupal\locale\Plugin\views\filter;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by locale group.
 *
 * @ingroup views_filter_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "locale_group"
 * )
 */
class Group extends InOperator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Group');
      $groups = module_invoke_all('locale', 'groups');
      // Sort the list.
      asort($groups);
      $this->value_options = $groups;
    }
  }
}
