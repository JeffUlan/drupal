<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\TypeLinkManagerInterface.
 */

namespace Drupal\rest\LinkManager;

interface TypeLinkManagerInterface {

  /**
   * Gets the URI that corresponds to a bundle.
   *
   * When using hypermedia formats, this URI can be used to indicate which
   * bundle the data represents. Documentation about required and optional
   * fields can also be provided at this URI.
   *
   * @param $entity_type
   *   The bundle's entity type.
   * @param $bundle
   *   The bundle name.
   *
   * @return string
   *   The corresponding URI for the bundle.
   */
  public function getTypeUri($entity_type, $bundle);
}
