<?php

/**
 * @file
 * Contains Drupal\rest\test\UpdateTest.
 */

namespace Drupal\rest\Tests;

use Drupal\rest\Tests\RESTTestBase;

/**
 * Tests resource updates on test entities.
 */
class UpdateTest extends RESTTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rest', 'entity_test');

  public static function getInfo() {
    return array(
      'name' => 'Update resource',
      'description' => 'Tests the update of resources.',
      'group' => 'REST',
    );
  }

  /**
   * Tests several valid and invalid partial update requests on test entities.
   */
  public function testPatchUpdate() {
    $serializer = drupal_container()->get('serializer');
    // @todo once EntityNG is implemented for other entity types test all other
    // entity types here as well.
    $entity_type = 'entity_test';

    $this->enableService('entity:' . $entity_type, 'PATCH');
    // Create a user account that has the required permissions to create
    // resources via the REST API.
    $account = $this->drupalCreateUser(array('restful patch entity:' . $entity_type));
    $this->drupalLogin($account);

    // Create an entity and save it to the database.
    $entity = $this->entityCreate($entity_type);
    $entity->save();

    // Create a second stub entity for overwriting a field.
    $patch_values['field_test_text'] = array(0 => array('value' => $this->randomString()));
    $patch_entity = entity_create($entity_type, $patch_values);
    // We don't want to overwrite the UUID.
    unset($patch_entity->uuid);
    $serialized = $serializer->serialize($patch_entity, 'drupal_jsonld');

    // Update the entity over the REST API.
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(204);

    // Re-load updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->field_test_text->value, $patch_entity->field_test_text->value, 'Field was successfully updated.');

    // Try to empty a field.
    $normalized = $serializer->normalize($patch_entity, 'drupal_jsonld');
    $normalized['field_test_text'] = array();
    $serialized = $serializer->encode($normalized, 'jsonld');

    // Update the entity over the REST API.
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(204);

    // Re-load updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertNull($entity->field_test_text->value, 'Test field has been cleared.');

    // Try to update a non-existing entity with ID 9999.
    $this->httpRequest('entity/' . $entity_type . '/9999', 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
    $loaded_entity = entity_load($entity_type, 9999, TRUE);
    $this->assertFalse($loaded_entity, 'Entity 9999 was not created.');

    // Try to update an entity without proper permissions.
    $this->drupalLogout();
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(403);

    // Try to update a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest('entity/' . $entity_type . '/' . $entity->id(), 'PATCH', $serialized, 'application/vnd.drupal.ld+json');
    $this->assertResponse(404);
  }
}
