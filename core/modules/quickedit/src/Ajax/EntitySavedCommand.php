<?php

/**
 * @file
 * Contains \Drupal\quickedit\Ajax\EntitySavedCommand.
 */

namespace Drupal\quickedit\Ajax;

/**
 * AJAX command to indicate the entity was loaded from PrivateTempStore and
 * saved into the database.
 */
class EntitySavedCommand extends BaseCommand {

  /**
   * Constructs a EntitySaveCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('quickeditEntitySaved', $data);
  }

}
