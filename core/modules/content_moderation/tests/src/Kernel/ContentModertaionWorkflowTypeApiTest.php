<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the API of the ContentModeration workflow type plugin.
 *
 * @group content_moderation
 *
 * @coversDefaultClass \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration
 */
class ContentModertaionWorkflowTypeApiTest extends KernelTestBase {

  /**
   * A workflow for testing.
   *
   * @var \Drupal\workflows\Entity\Workflow;
   */
  protected $workflow;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'workflows',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->workflow = Workflow::create(['id' => 'test', 'type' => 'content_moderation']);
    $this->workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');
  }

  /**
   * @covers ::getBundlesForEntityType
   * @covers ::addEntityTypeAndBundle
   * @covers ::removeEntityTypeAndBundle
   */
  public function testGetBundlesForEntityType() {
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $workflow_plugin */
    $workflow_plugin = $this->workflow->getTypePlugin();
    // The content moderation plugin does not valid the existence of the entity
    // type or bundle.
    $this->assertEquals([], $workflow_plugin->getBundlesForEntityType('fake_node'));
    $workflow_plugin->addEntityTypeAndBundle('fake_node', 'fake_page');
    $this->assertEquals(['fake_page'], $workflow_plugin->getBundlesForEntityType('fake_node'));
    $this->assertEquals([], $workflow_plugin->getBundlesForEntityType('fake_block'));
    $workflow_plugin->removeEntityTypeAndBundle('fake_node', 'fake_page');
    $this->assertEquals([], $workflow_plugin->getBundlesForEntityType('fake_node'));
  }

  /**
   * @covers ::appliesToEntityTypeAndBundle
   * @covers ::addEntityTypeAndBundle
   * @covers ::removeEntityTypeAndBundle
   */
  public function testAppliesToEntityTypeAndBundle() {
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $workflow_plugin */
    $workflow_plugin = $this->workflow->getTypePlugin();
    // The content moderation plugin does not valid the existence of the entity
    // type or bundle.
    $this->assertFalse($workflow_plugin->appliesToEntityTypeAndBundle('fake_node', 'fake_page'));
    $workflow_plugin->addEntityTypeAndBundle('fake_node', 'fake_page');
    $this->assertTrue($workflow_plugin->appliesToEntityTypeAndBundle('fake_node', 'fake_page'));
    $this->assertFalse($workflow_plugin->appliesToEntityTypeAndBundle('fake_block', 'fake_custom'));
    $workflow_plugin->removeEntityTypeAndBundle('fake_node', 'fake_page');
    $this->assertFalse($workflow_plugin->appliesToEntityTypeAndBundle('fake_node', 'fake_page'));
  }

}
