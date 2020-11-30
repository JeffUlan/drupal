<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Permissions;
use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test to ensure content moderation permissions are generated correctly.
 *
 * @group content_moderation
 */
class ContentModerationPermissionsTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'workflows',
    'content_moderation',
    'workflow_type_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('workflow');
  }

  /**
   * Test permissions generated by content moderation.
   *
   * @dataProvider permissionsTestCases
   */
  public function testPermissions($workflow, $permissions) {
    Workflow::create($workflow)->save();
    $this->assertEquals($permissions, (new Permissions())->transitionPermissions());
  }

  /**
   * Test cases for ::testPermissions.
   *
   * @return array
   *   Content moderation permissions based test cases.
   */
  public function permissionsTestCases() {
    return [
      'Simple Content Moderation Workflow' => [
        [
          'id' => 'simple_workflow',
          'label' => 'Simple Workflow',
          'type' => 'content_moderation',
        ],
        [
          'use simple_workflow transition publish' => [
            'title' => '<em class="placeholder">Simple Workflow</em> workflow: Use <em class="placeholder">Publish</em> transition.',
          ],
          'use simple_workflow transition create_new_draft' => [
            'title' => '<em class="placeholder">Simple Workflow</em> workflow: Use <em class="placeholder">Create New Draft</em> transition.',
          ],
        ],
      ],
      'Non Content Moderation Workflow' => [
        [
          'id' => 'morning',
          'label' => 'Morning',
          'type' => 'workflow_type_test',
          'transitions' => [
            'drink_coffee' => [
              'label' => 'Drink Coffee',
              'from' => ['tired'],
              'to' => 'awake',
              'weight' => 0,
            ],
          ],
          'states' => [
            'awake' => [
              'label' => 'Awake',
              'weight' => -5,
            ],
            'tired' => [
              'label' => 'Tired',
              'weight' => -0,
            ],
          ],
        ],
        [],
      ],
    ];
  }

}
