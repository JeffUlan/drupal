<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\sqlite\Select
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Query\Select as QuerySelect;

class Select extends QuerySelect {
  public function forUpdate($set = TRUE) {
    // SQLite does not support FOR UPDATE so nothing to do.
    return $this;
  }
}