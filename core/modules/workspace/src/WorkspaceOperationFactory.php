<?php

namespace Drupal\workspace;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a factory class for workspace operations.
 *
 * @see \Drupal\workspace\WorkspaceOperationInterface
 * @see \Drupal\workspace\WorkspacePublisherInterface
 *
 * @internal
 */
class WorkspaceOperationFactory {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new WorkspacePublisher.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Gets the workspace publisher.
   *
   * @param \Drupal\workspace\WorkspaceInterface $source
   *   A workspace entity.
   *
   * @return \Drupal\workspace\WorkspacePublisherInterface
   *   A workspace publisher object.
   */
  public function getPublisher(WorkspaceInterface $source) {
    return new WorkspacePublisher($this->entityTypeManager, $this->database, $source);
  }

}
