<?php

/**
 * @file
 * Post update functions for Responsive Image.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\responsive_image\ResponsiveImageConfigUpdater;
use Drupal\responsive_image\ResponsiveImageStyleInterface;

/**
 * Implements hook_removed_post_updates().
 */
function responsive_image_removed_post_updates() {
  return [
    'responsive_image_post_update_recreate_dependencies' => '9.0.0',
  ];
}

/**
 * Re-order mappings by breakpoint ID and descending numeric multiplier order.
 */
function responsive_image_post_update_order_multiplier_numerically(array &$sandbox = NULL): void {
  $responsive_image_config_updater = \Drupal::classResolver(ResponsiveImageConfigUpdater::class);
  assert($responsive_image_config_updater instanceof ResponsiveImageConfigUpdater);
  $responsive_image_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'responsive_image_style', function (ResponsiveImageStyleInterface $responsive_image_style) use ($responsive_image_config_updater): bool {
    return $responsive_image_config_updater->orderMultipliersNumerically($responsive_image_style);
  });
}
