<?php

/**
 * @file
 * Contains \Drupal\system\DateFormatListBuilder.
 */

namespace Drupal\system;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Datetime\Date as DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of date format entities.
 *
 * @see \Drupal\system\Entity\DateFormat
 */
class DateFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $dateFormatter;

  /**
   * Constructs a new DateFormatListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\Date $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = t('Machine name');
    $header['label'] = t('Name');
    $header['pattern'] = t('Pattern');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if ($entity->isLocked()) {
      $row['id'] =  $this->t('@entity_id (locked)', array('@entity_id' => $entity->id()));
    }
    else {
      $row['id'] = $entity->id();
    }
    $row['label'] = $this->getLabel($entity);
    $row['pattern'] = $this->dateFormatter->format(REQUEST_TIME, $entity->id());
    return $row + parent::buildRow($entity);
  }

}
