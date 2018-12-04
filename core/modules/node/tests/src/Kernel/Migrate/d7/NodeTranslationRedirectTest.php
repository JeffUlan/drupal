<?php

namespace Drupal\Tests\node\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests node translation redirections.
 *
 * @group migrate_drupal
 * @group node
 */
class NodeTranslationRedirectTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['key_value']);

    $this->migrateUsers(FALSE);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'language',
      'd7_language_types',
      'd7_language_negotiation_settings',
      'd7_node',
      'd7_node_translation',
    ]);
  }

  /**
   * Tests that not found node translations are redirected.
   */
  public function testNodeTranslationRedirect() {
    $kernel = $this->container->get('http_kernel');
    $request = Request::create('/node/3');
    $response = $kernel->handle($request);
    $this->assertSame(301, $response->getStatusCode());
    $this->assertSame('/node/2', $response->getTargetUrl());
  }

}
