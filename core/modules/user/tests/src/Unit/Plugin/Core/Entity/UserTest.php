<?php

namespace Drupal\Tests\user\Unit\Plugin\Core\Entity;

use Drupal\Tests\Core\Session\UserSessionTest;
use Drupal\user\RoleInterface;

/**
 * @coversDefaultClass \Drupal\user\Entity\User
 * @group user
 */
class UserTest extends UserSessionTest {

  /**
   * {@inheritdoc}
   */
  protected function createUserSession(array $rids = array(), $authenticated = FALSE) {
    $user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->setMethods(array('get', 'id'))
      ->getMock();
    $user->expects($this->any())
      ->method('id')
      // @todo Also test the uid = 1 handling.
      ->will($this->returnValue($authenticated ? 2 : 0));
    $roles = array();
    foreach ($rids as $rid) {
      $roles[] = (object) array(
        'target_id' => $rid,
      );
    }
    $user->expects($this->any())
      ->method('get')
      ->with('roles')
      ->will($this->returnValue($roles));
    return $user;
  }

  /**
   * Tests the method getRoles exclude or include locked roles based in param.
   *
   * @see \Drupal\user\Entity\User::getRoles()
   * @covers ::getRoles
   */
  public function testUserGetRoles() {
    // Anonymous user.
    $user = $this->createUserSession(array());
    $this->assertEquals(array(RoleInterface::ANONYMOUS_ID), $user->getRoles());
    $this->assertEquals(array(), $user->getRoles(TRUE));

    // Authenticated user.
    $user = $this->createUserSession(array(), TRUE);
    $this->assertEquals(array(RoleInterface::AUTHENTICATED_ID), $user->getRoles());
    $this->assertEquals(array(), $user->getRoles(TRUE));
  }

}
