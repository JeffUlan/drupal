<?php

namespace Drupal\Component\Scaffold;

use Composer\Installer\PackageEvent;

/**
 * Interface for post package event listeners.
 *
 * @see \Drupal\Component\Scaffold\Handler::onPostPackageEvent
 */
interface PostPackageEventListenerInterface {

  /**
   * Handles package events during a 'composer require' operation.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Composer package event sent on install/update/remove.
   */
  public function event(PackageEvent $event);

}
