<?php

namespace Drupal\corefake\Driver\Database\corefake;

use Drupal\Driver\Database\fake\Connection as BaseConnection;

class Connection extends BaseConnection {

  /**
   * {@inheritdoc}
   */
  public $driver = 'corefake';

}
