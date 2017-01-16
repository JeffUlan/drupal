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
  public static $modules = [
    'workflows',
    'content_moderation',
    'workflow_type_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
   * Test cases for ::testPermissions
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
          'transitions' => [
            'publish' => [
              'label' => 'Publish',
              'from' => ['draft'],
              'to' => 'published',
              'weight' => 0,
            ],
            'unpublish' => [
              'label' => 'Unpublish',
              'from' => ['published'],
              'to' => 'draft',
              'weight' => 0,
            ],
          ],
          'states' => [
            'published' => [
              'label' => 'Published',
            ],
            'draft' => [
              'label' => 'Draft',
            ],
          ],
        ],
        [
          'use simple_workflow transition publish' => [
            'title' => 'Use <em class="placeholder">Publish</em> transition from <em class="placeholder">Simple Workflow</em> workflow.',
          ],
          'use simple_workflow transition unpublish' => [
            'title' => 'Use <em class="placeholder">Unpublish</em> transition from <em class="placeholder">Simple Workflow</em> workflow.',
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
            'tired' => [
              'label' => 'Tired',
            ],
            'awake' => [
              'label' => 'Awake',
            ],
          ],
        ],
        []
      ],
    ];
  }

}
