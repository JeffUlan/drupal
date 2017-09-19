<?php

namespace Drupal\Tests\hal\Functional\EntityResource\MediaType;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\MediaType\MediaTypeResourceTestBase;

/**
 * @group hal
 */
class MediaTypeHalJsonBasicAuthTest extends MediaTypeResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'hal_json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/hal+json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
