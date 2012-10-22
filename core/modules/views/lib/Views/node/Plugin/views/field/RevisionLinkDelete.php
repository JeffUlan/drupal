<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\field\RevisionLinkDelete.
 */

namespace Views\node\Plugin\views\field;

use Views\node\Plugin\views\field\RevisionLink;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present link to delete a node revision.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_revision_link_delete",
 *   module = "node"
 * )
 */
class RevisionLinkDelete extends RevisionLink {

  public function access() {
    return user_access('delete revisions') || user_access('administer nodes');
  }

  function render_link($data, $values) {
    list($node, $vid) = $this->get_revision_entity($values, 'delete');
    if (!isset($vid)) {
      return;
    }

    // Current revision cannot be deleted.
    if ($node->vid == $vid) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'node/' . $node->nid . "/revisions/$vid/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('delete');
  }

}
