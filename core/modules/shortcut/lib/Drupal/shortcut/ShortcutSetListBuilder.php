<?php

/**
 * @file
 * Contains \Drupal\shortcut\ShortcutSetListBuilder.
 */

namespace Drupal\shortcut;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of shortcut set entities.
 *
 * @see \Drupal\shortcut\Entity\ShortcutSet
 */
class ShortcutSetListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit shortcut set');
    }

    $operations['list'] = array(
      'title' => t('List links'),
    ) + $entity->urlInfo('customize-form')->toArray();
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['name'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

}
