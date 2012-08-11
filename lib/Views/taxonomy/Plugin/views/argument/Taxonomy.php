<?php

/**
 * @file
 * Definition of views_handler_argument_taxonomy.
 */

namespace Views\taxonomy\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler for basic taxonomy tid.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @plugin(
 *   id = "taxonomy"
 * )
 */
class Taxonomy extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $term = taxonomy_term_load($this->argument);
      if (!empty($term)) {
        return check_plain($term->name);
      }
    }
    // TODO review text
    return t('No name');
  }
}
