<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserEntityTest.
 */

namespace Drupal\user\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the user entity class.
 *
 * @group user
 * @see \Drupal\user\Entity\User
 */
class UserEntityTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field');

  /**
   * Tests some of the methods.
   *
   * @see \Drupal\user\Entity\User::getRoles()
   * @see \Drupal\user\Entity\User::addRole()
   * @see \Drupal\user\Entity\User::removeRole()
   */
  public function testUserMethods() {
    $role_storage = $this->container->get('entity.manager')->getStorage('user_role');
    $role_storage->create(array('id' => 'test_role_one'))->save();
    $role_storage->create(array('id' => 'test_role_two'))->save();
    $role_storage->create(array('id' => 'test_role_three'))->save();

    $values = array(
      'uid' => 1,
      'roles' => array('test_role_one'),
    );
    $user = User::create($values);

    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertFalse($user->hasRole('test_role_two'));
    $this->assertEqual(array(DRUPAL_AUTHENTICATED_RID, 'test_role_one'), $user->getRoles());

    $user->addRole('test_role_one');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertFalse($user->hasRole('test_role_two'));
    $this->assertEqual(array(DRUPAL_AUTHENTICATED_RID, 'test_role_one'), $user->getRoles());

    $user->addRole('test_role_two');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEqual(array(DRUPAL_AUTHENTICATED_RID, 'test_role_one', 'test_role_two'), $user->getRoles());

    $user->removeRole('test_role_three');
    $this->assertTrue($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEqual(array(DRUPAL_AUTHENTICATED_RID, 'test_role_one', 'test_role_two'), $user->getRoles());

    $user->removeRole('test_role_one');
    $this->assertFalse($user->hasRole('test_role_one'));
    $this->assertTrue($user->hasRole('test_role_two'));
    $this->assertEqual(array(DRUPAL_AUTHENTICATED_RID, 'test_role_two'), $user->getRoles());
  }

}
