<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\filter\NcsLastUpdated.
 */

namespace Drupal\comment\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter handler for the newer of last comment / node updated.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("ncs_last_updated")
 */
class NcsLastUpdated extends Date {

  public function query() {
    $this->ensureMyTable();
    $this->node_table = $this->query->ensureTable('node', $this->relationship);

    $field = "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_comment_timestamp)";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
