<?php

namespace Drupal\Tests\workspace\Functional\EntityResource;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;

/**
 * Test workspace entities for JSON requests via basic auth.
 *
 * @group workspace
 */
class WorkspaceJsonBasicAuthTest extends WorkspaceResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

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
