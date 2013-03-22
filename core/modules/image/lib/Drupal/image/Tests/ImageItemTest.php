<?php

/**
 * @file
 * Contains \Drupal\image\Tests\ImageItemTest.
 */

namespace Drupal\image\Tests;

use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\Core\Entity\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests the new entity API for the image field type.
 */
class ImageItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file', 'image');

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Plugin\Core\Entity\File
   */
  protected $image;

  public static function getInfo() {
    return array(
      'name' => 'Image field item API',
      'description' => 'Tests using entity fields of the image field type.',
      'group' => 'Image',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->installSchema('file', array('file_managed', 'file_usage'));

    $field = array(
      'field_name' => 'image_test',
      'type' => 'image',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
    );
    field_create_field($field);
    $instance = array(
      'entity_type' => 'entity_test',
      'field_name' => 'image_test',
      'bundle' => 'entity_test',
      'widget' => array(
        'type' => 'image_image',
      ),
    );
    field_create_instance($instance);
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/druplicon.png', 'public://example.jpg');
    $this->image = entity_create('file', array(
      'uri' => 'public://example.jpg',
    ));
    $this->image->save();
  }

  /**
   * Tests using entity fields of the image field type.
   */
  public function testImageItem() {
    // Create a test entity with the image field set.
    $entity = entity_create('entity_test', array());
    $entity->image_test->fid = $this->image->id();
    $entity->image_test->alt = $alt = $this->randomName();
    $entity->image_test->title = $title = $this->randomName();
    $entity->name->value = $this->randomName();
    $entity->save();

    $entity = entity_load('entity_test', $entity->id());
    $this->assertTrue($entity->image_test instanceof FieldInterface, 'Field implements interface.');
    $this->assertTrue($entity->image_test[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->image_test->fid, $this->image->id());
    $this->assertEqual($entity->image_test->alt, $alt);
    $this->assertEqual($entity->image_test->title, $title);
    $info = image_get_info('public://example.jpg');
    $this->assertEqual($entity->image_test->width, $info['width']);
    $this->assertEqual($entity->image_test->height, $info['height']);
    $this->assertEqual($entity->image_test->entity->id(), $this->image->id());
    $this->assertEqual($entity->image_test->entity->uuid(), $this->image->uuid());

    // Make sure the computed entity reflects updates to the referenced file.
    file_unmanaged_copy(DRUPAL_ROOT . '/core/misc/feed.png', 'public://example-2.jpg');
    $image2 = entity_create('file', array(
      'uri' => 'public://example-2.jpg',
    ));
    $image2->save();

    $entity->image_test->fid = $image2->id();
    $entity->image_test->alt = $new_alt = $this->randomName();
    // The width and height is only updated when width is not set.
    $entity->image_test->width = NULL;
    $entity->save();
    $this->assertEqual($entity->image_test->entity->id(), $image2->id());
    $this->assertEqual($entity->image_test->entity->uri, $image2->uri);
    $info = image_get_info('public://example-2.jpg');
    $this->assertEqual($entity->image_test->width, $info['width']);
    $this->assertEqual($entity->image_test->height, $info['height']);
    $this->assertEqual($entity->image_test->alt, $new_alt);

    // Check that the image item can be set to the referenced file directly.
    $entity->image_test = $this->image;
    $this->assertEqual($entity->image_test->fid, $this->image->id());
  }

}
