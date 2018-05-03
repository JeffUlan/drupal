<?php

namespace Drupal\Core\Installer;

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extend DrupalKernel to handle force some kernel behaviors.
 */
class InstallerKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  protected function initializeContainer() {
    // Always force a container rebuild.
    $this->containerNeedsRebuild = TRUE;
    $container = parent::initializeContainer();
    return $container;
  }

  /**
   * Reset the bootstrap config storage.
   *
   * Use this from a database driver runTasks() if the method overrides the
   * bootstrap config storage. Normally the bootstrap config storage is not
   * re-instantiated during a single install request. Most drivers will not
   * need this method.
   *
   * @see \Drupal\Core\Database\Install\Tasks::runTasks()
   */
  public function resetConfigStorage() {
    $this->configStorage = NULL;
  }

  /**
   * Returns the active configuration storage used during early install.
   *
   * This override changes the visibility so that the installer can access
   * config storage before the container is properly built.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  public function getConfigStorage() {
    return parent::getConfigStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallProfile() {
    global $install_state;
    if ($install_state && empty($install_state['installation_finished'])) {
      // If the profile has been selected return it.
      if (isset($install_state['parameters']['profile'])) {
        $profile = $install_state['parameters']['profile'];
      }
      else {
        $profile = NULL;
      }
    }
    else {
      $profile = parent::getInstallProfile();
    }
    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromRequest(Request $request, $class_loader, $environment, $allow_dumping = TRUE, $app_root = NULL) {
    // This override exists because we don't need to initialize the settings
    // again as they already are in install_begin_request().
    $kernel = new static($environment, $class_loader, $allow_dumping, $app_root);
    static::bootEnvironment($app_root);
    return $kernel;
  }

}
