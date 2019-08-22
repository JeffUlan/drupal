<?php

namespace Drupal\Tests\jsonapi\Kernel\ResourceType;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;

/**
 * @coversDefaultClass \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 * @group jsonapi
 *
 * @internal
 */
class ResourceTypeRepositoryTest extends JsonapiKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'serialization',
    'system',
    'user',
  ];

  /**
   * The JSON:API resource type repository under test.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);
    NodeType::create([
      'type' => 'article',
    ])->save();
    NodeType::create([
      'type' => 'page',
    ])->save();
    NodeType::create([
      'type' => '42',
    ])->save();

    $this->resourceTypeRepository = $this->container->get('jsonapi.resource_type.repository');
  }

  /**
   * @covers ::all
   */
  public function testAll() {
    // Make sure that there are resources being created.
    $all = $this->resourceTypeRepository->all();
    $this->assertNotEmpty($all);
    array_walk($all, function (ResourceType $resource_type) {
      $this->assertNotEmpty($resource_type->getDeserializationTargetClass());
      $this->assertNotEmpty($resource_type->getEntityTypeId());
      $this->assertNotEmpty($resource_type->getTypeName());
    });
  }

  /**
   * @covers ::get
   * @dataProvider getProvider
   */
  public function testGet($entity_type_id, $bundle, $entity_class) {
    // Make sure that there are resources being created.
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    $this->assertInstanceOf(ResourceType::class, $resource_type);
    $this->assertSame($entity_class, $resource_type->getDeserializationTargetClass());
    $this->assertSame($entity_type_id, $resource_type->getEntityTypeId());
    $this->assertSame($bundle, $resource_type->getBundle());
    $this->assertSame($entity_type_id . '--' . $bundle, $resource_type->getTypeName());
  }

  /**
   * Data provider for testGet.
   *
   * @returns array
   *   The data for the test method.
   */
  public function getProvider() {
    return [
      ['node', 'article', 'Drupal\node\Entity\Node'],
      ['node', '42', 'Drupal\node\Entity\Node'],
      ['node_type', 'node_type', 'Drupal\node\Entity\NodeType'],
      ['menu', 'menu', 'Drupal\system\Entity\Menu'],
    ];
  }

  /**
   * Ensures that the ResourceTypeRepository's cache does not become stale.
   */
  public function testCaching() {
    $this->assertEmpty($this->resourceTypeRepository->get('node', 'article')->getRelatableResourceTypesByField('field_relationship'));
    $this->createEntityReferenceField('node', 'article', 'field_relationship', 'Related entity', 'node');
    $this->assertCount(3, $this->resourceTypeRepository->get('node', 'article')->getRelatableResourceTypesByField('field_relationship'));
    NodeType::create(['type' => 'camelids'])->save();
    $this->assertCount(4, $this->resourceTypeRepository->get('node', 'article')->getRelatableResourceTypesByField('field_relationship'));
  }

}
