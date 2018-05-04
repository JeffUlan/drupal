<?php

namespace Drupal\workspace;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the WorkspaceCacheContext service, for "per workspace" caching.
 *
 * Cache context ID: 'workspace'.
 */
class WorkspaceCacheContext implements CacheContextInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new WorkspaceCacheContext service.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Workspace');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->workspaceManager->getActiveWorkspace()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($type = NULL) {
    return new CacheableMetadata();
  }

}
