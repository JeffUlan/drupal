<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests creating entity reference fields in the UI.
 *
 * @group entity
 */
class EntityReferenceFieldCreationTest extends BrowserTestBase {

  use EntityReferenceTestTrait;
  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'node', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that entity reference fields cannot target entity types without IDs.
   */
  public function testAddReferenceFieldTargetingEntityTypeWithoutId() {
    $this->drupalLogin($this->rootUser);
    $node_type = $this->drupalCreateContentType()->id();

    // Entity types without an ID key should not be presented as options when
    // creating an entity reference field in the UI.
    $this->fieldUIAddNewField("/admin/structure/types/manage/$node_type", 'test_reference_field', 'Test Field', 'entity_reference', [], [], FALSE);
    $this->assertSession()->optionNotExists('settings[target_type]', 'entity_test_no_id');

    // Trying to do it programmatically should raise an exception.
    $this->expectException('\Drupal\Core\Field\FieldException');
    $this->expectExceptionMessage('Entity type "entity_test_no_id" has no ID key and cannot be targeted by entity reference field "test_reference_field"');
    $this->createEntityReferenceField('node', $node_type, 'test_reference_field', 'Test Field', 'entity_test_no_id');
  }

}
