<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\Access\ContentTranslationManageAccessCheckTest.
 */

namespace Drupal\content_translation\Tests\Access;

use Drupal\content_translation\Access\ContentTranslationManageAccessCheck;
use Drupal\Core\Access\AccessInterface;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests for content translation manage check.
 *
 * @coversDefaultClass \Drupal\content_translation\Access\ContentTranslationManageAccessCheck
 * @group content_translation
 */
class ContentTranslationManageAccessCheckTest extends UnitTestCase {

  /**
   * Tests the create access method.
   *
   * @covers ::access()
   */
  public function testCreateAccess() {
    // Set the mock translation handler.
    $translation_handler = $this->getMock('\Drupal\content_translation\ContentTranslationHandlerInterface');
    $translation_handler->expects($this->once())
      ->method('getTranslationAccess')
      ->will($this->returnValue(TRUE));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getController')
      ->withAnyParameters()
      ->will($this->returnValue($translation_handler));

    // Set our source and target languages.
    $source = 'en';
    $target = 'it';

    // Set the mock language manager.
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->at(0))
      ->method('getLanguages')
      ->will($this->returnValue(array('en' => array(), 'it' => array())));
    $language_manager->expects($this->at(1))
      ->method('getLanguage')
      ->with($this->equalTo($source))
      ->will($this->returnValue(new Language(array('id' => 'en'))));
    $language_manager->expects($this->at(2))
      ->method('getLanguage')
      ->with($this->equalTo($target))
      ->will($this->returnValue(new Language(array('id' => 'it'))));

    // Set the mock entity. We need to use ContentEntityBase for mocking due to
    // issues with phpunit and multiple interfaces.
    $entity = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->once())
      ->method('getEntityTypeId');
    $entity->expects($this->once())
      ->method('getTranslationLanguages')
      ->with()
      ->will($this->returnValue(array()));

    // Set the route requirements.
    $route = new Route('test_route');
    $route->setRequirement('_access_content_translation_manage', 'create');

    // Set the request attributes.
    $request = Request::create('node/1');
    $request->attributes->set('node', $entity);

    // Set the mock account.
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');

    // The access check under test.
    $check = new ContentTranslationManageAccessCheck($entity_manager, $language_manager);

    // The request params.
    $language = 'en';
    $entity_type_id = 'node';

    $this->assertEquals($check->access($route, $request, $account, $source, $target, $language, $entity_type_id), AccessInterface::ALLOW, "The access check matches");
  }

}
