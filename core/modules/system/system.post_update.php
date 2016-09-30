<?php

/**
 * @file
 * Post update functions for System.
 */

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * @addtogroup updates-8.0.0-beta
 * @{
 */

/**
 * Re-save all configuration entities to recalculate dependencies.
 */
function system_post_update_recalculate_configuration_entity_dependencies(&$sandbox = NULL) {
  if (!isset($sandbox['config_names'])) {
    $sandbox['config_names'] = \Drupal::configFactory()->listAll();
    $sandbox['count'] = count($sandbox['config_names']);
  }
  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service('config.manager');

  $count = 0;
  foreach ($sandbox['config_names'] as $key => $config_name) {
    if ($entity = $config_manager->loadConfigEntityByName($config_name)) {
      $entity->save();
    }
    unset($sandbox['config_names'][$key]);
    $count++;
    // Do 50 at a time.
    if ($count == 50) {
      break;
    }
  }

  $sandbox['#finished'] = empty($sandbox['config_names']) ? 1 : ($sandbox['count'] - count($sandbox['config_names'])) / $sandbox['count'];
  return t('Configuration dependencies recalculated');
}

/**
 * @} End of "addtogroup updates-8.0.0-beta".
 */

/**
 * Update entity displays to contain the region for each field.
 */
function system_post_update_add_region_to_entity_displays() {
  $entity_save = function (EntityDisplayInterface $entity) {
    foreach ($entity->getComponents() as $name => $component) {
      // setComponent() will fill in the correct region based on the 'type'.
      $entity->setComponent($name, $component);
    }
    $entity->save();
  };
  array_map($entity_save, EntityViewDisplay::loadMultiple());
  array_map($entity_save, EntityFormDisplay::loadMultiple());
}
