<?php

namespace Drupal\Tests\media\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * @group rest
 */
class MediaJsonBasicAuthTest extends MediaResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
