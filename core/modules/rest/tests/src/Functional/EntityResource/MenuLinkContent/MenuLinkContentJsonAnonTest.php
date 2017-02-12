<?php

namespace Drupal\Tests\rest\Functional\EntityResource\MenuLinkContent;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group rest
 */
class MenuLinkContentJsonAnonTest extends MenuLinkContentResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

}
