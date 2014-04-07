<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestCache.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Language\Language;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_cache",
 *   label = @Translation("Test entity with field cache"),
 *   controllers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   field_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestCache extends EntityTest {

}
