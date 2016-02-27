<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\ProfileValues.
 */

namespace Drupal\user\Plugin\migrate;

use Drupal\migrate\Plugin\Migration;

/**
 * Plugin class for user migrations dealing with profile values.
 */
class ProfileValues extends Migration {

  /**
   * @var bool
   */
  protected $init = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition['source'] = [
        'plugin' => 'profile_field',
        'ignore_map' => TRUE,
      ] + $this->source;
      $definition['destination']['plugin'] = 'null';
      try {
        $profile_field_migration = new Migration([], uniqid(), $definition);
        $source_plugin = $profile_field_migration->getSourcePlugin();
        $source_plugin->checkRequirements();
        foreach ($source_plugin as $row) {
          $name = $row->getSourceProperty('name');
          $this->process[$name] = $name;
        }
      }
      catch (\Exception $e) {
        // @TODO https://www.drupal.org/node/2666640
      }
    }
    return parent::getProcess();
  }

}
