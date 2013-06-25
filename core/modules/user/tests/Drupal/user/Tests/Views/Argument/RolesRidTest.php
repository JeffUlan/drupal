<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\Argument\RolesRidTest.
 */

namespace Drupal\user\Tests\Views\Argument;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\views\argument\RolesRid;

/**
 * Tests the roles argument handler.
 *
 * @see \Drupal\user\Plugin\views\argument\RolesRid
 */
class RolesRidTest extends UnitTestCase {

  /**
   * Entity info used by the test.
   *
   * @var array
   */
  public static $entityInfo = array(
    'entity_keys' => array(
      'id' => 'id',
      'label' => 'label',
    ),
    'config_prefix' => 'user.role',
    'class' => 'Drupal\user\Plugin\Core\Entity\Role',
  );

  public static function getInfo() {
    return array(
      'name' => 'User: Roles Rid Argument',
      'description' => 'Tests the role argument handler.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the title_query method.
   *
   * @see \Drupal\user\Plugin\views\argument\RolesRid::title_query()
   */
  public function testTitleQuery() {
    $config = array(
      'user.role.test_rid_1' => array(
        'id' => 'test_rid_1',
        'label' => 'test rid 1'
      ),
      'user.role.test_rid_2' => array(
        'id' => 'test_rid_2',
        'label' => 'test <strong>rid 2</strong>',
      ),
    );
    $config_factory = $this->getConfigFactoryStub($config);
    $config_storage = $this->getConfigStorageStub($config);

    $entity_query_factory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    // Creates a stub role storage controller and replace the attachLoad()
    // method with an empty version, because attachLoad() calls
    // module_implements().
    $role_storage_controller = $this->getMock('Drupal\user\RoleStorageController', array('attachLoad'), array('user_role', static::$entityInfo, $config_factory, $config_storage, $entity_query_factory));


    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();

    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->with($this->equalTo('user_role'))
      ->will($this->returnValue(static::$entityInfo));

    $entity_manager
      ->expects($this->once())
      ->method('getStorageController')
      ->with($this->equalTo('user_role'))
      ->will($this->returnValue($role_storage_controller));

    // @todo \Drupal\Core\Entity\Entity::entityInfo() uses a global call to
    //   entity_get_info(), which in turn wraps \Drupal::entityManager(). Set
    //   the entity manager until this is fixed.
    $container = new ContainerBuilder();
    $container->set('plugin.manager.entity', $entity_manager);
    \Drupal::setContainer($container);

    $roles_rid_argument = new RolesRid($config, 'users_roles_rid', array(), $entity_manager);

    $roles_rid_argument->value = array();
    $titles = $roles_rid_argument->title_query();
    $this->assertEquals(array(), $titles);

    $roles_rid_argument->value = array('test_rid_1');
    $titles = $roles_rid_argument->title_query();
    $this->assertEquals(array('test rid 1'), $titles);

    $roles_rid_argument->value = array('test_rid_1', 'test_rid_2');
    $titles = $roles_rid_argument->title_query();
    $this->assertEquals(array('test rid 1', String::checkPlain('test <strong>rid 2</strong>')), $titles);
  }

}
