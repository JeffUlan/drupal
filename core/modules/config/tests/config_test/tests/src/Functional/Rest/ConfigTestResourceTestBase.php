<?php

namespace Drupal\Tests\config_test\Functional\Rest;

use Drupal\config_test\Entity\ConfigTest;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class ConfigTestResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test', 'config_test_rest'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'config_test';

  /**
   * @var \Drupal\config_test\ConfigTestInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['view config_test']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $config_test = ConfigTest::create([
      'id' => 'llama',
      'label' => 'Llama',
    ]);
    $config_test->save();

    return $config_test;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $normalization = [
      'uuid' => $this->entity->uuid(),
      'id' => 'llama',
      'weight' => 0,
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'label' => 'Llama',
      'style' => NULL,
      'size' => NULL,
      'size_value' => NULL,
      'protected_property' => NULL,
    ];

    return $normalization;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    switch ($method) {
      case 'GET':
        return "The 'view config_test' permission is required.";
      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

}
