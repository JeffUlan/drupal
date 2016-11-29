<?php

namespace Drupal\Tests\hal\Functional\EntityResource\Node;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * @group hal
 */
class NodeHalJsonCookieTest extends NodeHalJsonAnonTest {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
