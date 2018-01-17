<?php

namespace Drupal\Tests\Listeners;

use Drupal\Tests\Traits\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;

/**
 * Removes deprecations that we are yet to fix.
 *
 * @internal
 *   This class will be removed once all the deprecation notices have been
 *   fixed.
 */
trait DeprecationListenerTrait {
  use ExpectDeprecationTrait;

  protected function deprecationStartTest($test) {
    if ($test instanceof \PHPUnit_Framework_TestCase || $test instanceof TestCase) {
      if ($this->willBeIsolated($test)) {
        putenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE=' . tempnam(sys_get_temp_dir(), 'exdep'));
      }
    }
  }

  /**
   * Reacts to the end of a test.
   *
   * @param \PHPUnit\Framework\Test|\PHPUnit_Framework_Test $test
   *   The test object that has ended its test run.
   * @param float $time
   *   The time the test took.
   */
  protected function deprecationEndTest($test, $time) {
    /** @var \PHPUnit\Framework\Test $test */
    if ($file = getenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE')) {
      putenv('DRUPAL_EXPECTED_DEPRECATIONS_SERIALIZE');
      $expected_deprecations = file_get_contents($file);
      if ($expected_deprecations) {
        $test->expectedDeprecations(unserialize($expected_deprecations));
      }
    }
    if ($file = getenv('SYMFONY_DEPRECATIONS_SERIALIZE')) {
      $util_test_class = class_exists('PHPUnit_Util_Test') ? 'PHPUnit_Util_Test' : 'PHPUnit\Util\Test';
      $method = $test->getName(FALSE);
      if (strpos($method, 'testLegacy') === 0
        || strpos($method, 'provideLegacy') === 0
        || strpos($method, 'getLegacy') === 0
        || strpos(get_class($test), '\Legacy')
        || in_array('legacy', $util_test_class::getGroups(get_class($test), $method), TRUE)) {
        // This is a legacy test don't skip deprecations.
        return;
      }

      // Need to edit the file of deprecations to remove any skipped
      // deprecations.
      $deprecations = file_get_contents($file);
      $deprecations = $deprecations ? unserialize($deprecations) : [];
      $resave = FALSE;
      foreach ($deprecations as $key => $deprecation) {
        if (in_array($deprecation[1], static::getSkippedDeprecations())) {
          unset($deprecations[$key]);
          $resave = TRUE;
        }
      }
      if ($resave) {
        file_put_contents($file, serialize($deprecations));
      }
    }
  }

  /**
   * Determines if a test is isolated.
   *
   * @param \PHPUnit_Framework_TestCase|\PHPUnit\Framework\TestCase $test
   *   The test to check.
   *
   * @return bool
   *   TRUE if the isolated, FALSE if not.
   */
  private function willBeIsolated($test) {
    if ($test->isInIsolation()) {
      return FALSE;
    }

    $r = new \ReflectionProperty($test, 'runTestInSeparateProcess');
    $r->setAccessible(TRUE);

    return $r->getValue($test);
  }

  /**
   * A list of deprecations to ignore whilst fixes are put in place.
   *
   * @return string[]
   *   A list of deprecations to ignore.
   *
   * @internal
   */
  public static function getSkippedDeprecations() {
    return [
      'As of 3.1 an Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface is used to resolve arguments. In 4.0 the $argumentResolver becomes the Symfony\Component\HttpKernel\Controller\ArgumentResolver if no other is provided instead of using the $resolver argument.',
      'Symfony\Component\HttpKernel\Controller\ControllerResolver::getArguments is deprecated as of 3.1 and will be removed in 4.0. Implement the Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface and inject it in the HttpKernel instead.',
      'The Twig_Node::getLine method is deprecated since version 1.27 and will be removed in 2.0. Use getTemplateLine() instead.',
      'The Twig_Environment::getCacheFilename method is deprecated since version 1.22 and will be removed in Twig 2.0.',
      'Install profile will be a mandatory parameter in Drupal 9.0.',
      'Setting the strict option of the Choice constraint to false is deprecated since version 3.2 and will be removed in 4.0.',
      'The revision_user revision metadata key is not set.',
      'The revision_created revision metadata key is not set.',
      'The revision_log_message revision metadata key is not set.',
      'The "entity.query" service relies on the deprecated "Drupal\Core\Entity\Query\QueryFactory" class. It should either be deprecated or its implementation upgraded.',
      'MigrateCckField is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateField instead.',
      'MigrateCckFieldPluginManager is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateFieldPluginManager instead.',
      'MigrateCckFieldPluginManagerInterface is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateFieldPluginManagerInterface instead.',
      'The "plugin.manager.migrate.cckfield" service is deprecated. You should use the \'plugin.manager.migrate.field\' service instead. See https://www.drupal.org/node/2751897',
      'The Drupal\migrate\Plugin\migrate\process\Iterator is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.0. Instead, use Drupal\migrate\Plugin\migrate\process\SubProcess',
      'Drupal\system\Tests\Update\DbUpdatesTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Use \Drupal\FunctionalTests\Update\DbUpdatesTrait instead. See https://www.drupal.org/node/2896640.',
      'Using "null" for the value of node "count" of "Drupal\Core\Template\TwigNodeTrans" is deprecated since version 1.25 and will be removed in 2.0.',
      'Using "null" for the value of node "options" of "Drupal\Core\Template\TwigNodeTrans" is deprecated since version 1.25 and will be removed in 2.0.',
      'Using "null" for the value of node "plural" of "Drupal\Core\Template\TwigNodeTrans" is deprecated since version 1.25 and will be removed in 2.0.',
      'The Behat\Mink\Selector\SelectorsHandler::xpathLiteral method is deprecated as of 1.7 and will be removed in 2.0. Use \Behat\Mink\Selector\Xpath\Escaper::escapeLiteral instead when building Xpath or pass the unescaped value when using the named selector.',
      'Passing an escaped locator to the named selector is deprecated as of 1.7 and will be removed in 2.0. Pass the raw value instead.',
      'Providing settings under \'handler_settings\' is deprecated and will be removed before 9.0.0. Move the settings in the root of the configuration array. See https://www.drupal.org/node/2870971.',
      'AssertLegacyTrait::getRawContent() is scheduled for removal in Drupal 9.0.0. Use $this->getSession()->getPage()->getContent() instead.',
      'AssertLegacyTrait::getAllOptions() is scheduled for removal in Drupal 9.0.0. Use $element->findAll(\'xpath\', \'option\') instead.',
      'assertNoCacheTag() is deprecated and scheduled for removal in Drupal 9.0.0. Use $this->assertSession()->responseHeaderNotContains() instead. See https://www.drupal.org/node/2864029.',
      'assertNoPattern() is deprecated and scheduled for removal in Drupal 9.0.0. Use $this->assertSession()->responseNotMatches($pattern) instead. See https://www.drupal.org/node/2864262.',
      'The $published parameter is deprecated since version 8.3.x and will be removed in 9.0.0.',
      'The Drupal\config\Tests\AssertConfigEntityImportTrait is deprecated in Drupal 8.4.1 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\config\Traits\AssertConfigEntityImportTrait. See https://www.drupal.org/node/2916197.',
      'Drupal\system\Tests\Menu\AssertBreadcrumbTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait',
      '\Drupal\Tests\node\Functional\AssertButtonsTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\node\Functional\AssertButtonsTrait',
      'Drupal\system\Tests\Menu\AssertMenuActiveTrailTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\system\Functional\Menu\AssertMenuActiveTrailTrait',
      'Drupal\taxonomy\Tests\TaxonomyTranslationTestTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\taxonomy\Functional\TaxonomyTranslationTestTrait',
      'Drupal\basic_auth\Tests\BasicAuthTestTrait is deprecated in Drupal 8.3.0 and will be removed before Drupal 9.0.0. Use \Drupal\Tests\basic_auth\Traits\BasicAuthTestTrait instead. See https://www.drupal.org/node/2862800.',
      'Drupal\taxonomy\Tests\TaxonomyTestTrait is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait',
      'Using UTF-8 route patterns without setting the "utf8" option is deprecated since Symfony 3.2 and will throw a LogicException in 4.0. Turn on the "utf8" route option for pattern "/system-test/Ȅchȏ/meφΩ/{text}".',
      'Using UTF-8 route patterns without setting the "utf8" option is deprecated since Symfony 3.2 and will throw a LogicException in 4.0. Turn on the "utf8" route option for pattern "/somewhere/{item}/over/the/קainbow".',
      'Using UTF-8 route patterns without setting the "utf8" option is deprecated since Symfony 3.2 and will throw a LogicException in 4.0. Turn on the "utf8" route option for pattern "/place/meφω".',
      'Using UTF-8 route patterns without setting the "utf8" option is deprecated since Symfony 3.2 and will throw a LogicException in 4.0. Turn on the "utf8" route option for pattern "/PLACE/meφω".',
      'Passing a Session object to the ExpectationException constructor is deprecated as of Mink 1.7. Pass the driver instead.',
      'The Drupal\editor\Plugin\EditorBase::settingsFormValidate method is deprecated since version 8.3.x and will be removed in 9.0.0.',
      'CckFile is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\file\Plugin\migrate\process\d6\FieldFile instead.',
      'The Drupal\migrate\Plugin\migrate\process\Migration is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use Drupal\migrate\Plugin\migrate\process\MigrationLookup',
      'LinkField is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\link\Plugin\migrate\field\d7\LinkField instead.',
      'LinkField is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\link\Plugin\migrate\field\d6\LinkField instead.',
      'CckFieldPluginBase is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase instead.',
      'MigrateCckFieldInterface is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateField instead.',
      'Drupal\system\Plugin\views\field\BulkForm is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\views\Plugin\views\field\BulkForm instead. See https://www.drupal.org/node/2916716.',
      'The numeric plugin for watchdog.wid field is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Must use standard plugin instead. See https://www.drupal.org/node/2876378.',
      'The numeric plugin for watchdog.uid field is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Must use standard plugin instead. See https://www.drupal.org/node/2876378.',
      'The in_operator plugin for watchdog.type filter is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Must use dblog_types plugin instead. See https://www.drupal.org/node/2876378.',
      'Using an instance of "Twig_Filter_Function" for filter "testfilter" is deprecated since version 1.21. Use Twig_SimpleFilter instead.',
      'The Twig_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.',
      'Using an instance of "Twig_Function_Function" for function "testfunc" is deprecated since version 1.21. Use Twig_SimpleFunction instead.',
      'The Twig_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.',
      'The Twig_Filter_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFilter instead.',
      'The Twig_Filter class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFilter instead.',
      'The Twig_Function_Function class is deprecated since version 1.12 and will be removed in 2.0. Use Twig_SimpleFunction instead.',
      'Referencing the "twig_extension_test.test_extension" extension by its name (defined by getName()) is deprecated since 1.26 and will be removed in Twig 2.0. Use the Fully Qualified Extension Class Name instead.',
      'Passing in arguments the legacy way is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Provide the right parameter names in the method, similar to controllers. See https://www.drupal.org/node/2894819',
      'DateField is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\migrate\field\DateField instead.',
      'The Drupal\taxonomy\Entity\Term::getVocabularyId method is deprecated since version 8.4.0 and will be removed before 9.0.0. Use Drupal\taxonomy\Entity\Term::bundle() instead to get the vocabulary ID.',
      'The Drupal\editor\Plugin\EditorBase::settingsFormSubmit method is deprecated since version 8.3.x and will be removed in 9.0.0.',
      'CommentVariable is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\node\Plugin\migrate\source\d6\NodeType instead.',
      'CommentType is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\node\Plugin\migrate\source\d7\NodeType instead.',
      'CommentVariablePerCommentType is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\node\Plugin\migrate\source\d6\NodeType instead.',
      'The Drupal\config_translation\Plugin\migrate\source\d6\I18nProfileField is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use Drupal\config_translation\Plugin\migrate\source\d6\ProfileFieldTranslation',
      'The Drupal\migrate_drupal\Plugin\migrate\source\d6\i18nVariable is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use Drupal\migrate_drupal\Plugin\migrate\source\d6\VariableTranslation',
      'Implicit cacheability metadata bubbling (onto the global render context) in normalizers is deprecated since Drupal 8.5.0 and will be removed in Drupal 9.0.0. Use the "cacheability" serialization context instead, for explicit cacheability metadata bubbling. See https://www.drupal.org/node/2918937',
      'Automatically creating the first item for computed fields is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\TypedData\ComputedItemListTrait instead.',
      '"\Drupal\Core\Entity\ContentEntityStorageBase::doLoadRevisionFieldItems()" is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. "\Drupal\Core\Entity\ContentEntityStorageBase::doLoadMultipleRevisionsFieldItems()" should be implemented instead. See https://www.drupal.org/node/2924915.',
      'Passing a single revision ID to "\Drupal\Core\Entity\Sql\SqlContentEntityStorage::buildQuery()" is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. An array of revision IDs should be given instead. See https://www.drupal.org/node/2924915.',
      'drupal_set_message() is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Messenger\MessengerInterface::addMessage() instead. See https://www.drupal.org/node/2774931',
      'drupal_get_message() is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Messenger\MessengerInterface::all() or \Drupal\Core\Messenger\MessengerInterface::messagesByType() instead. See https://www.drupal.org/node/2774931',
      'Adding or retrieving messages prior to the container being initialized was deprecated in Drupal 8.5.0 and this functionality will be removed before Drupal 9.0.0. Please report this usage at https://www.drupal.org/node/2928994.',
      'The "serializer.normalizer.file_entity.hal" normalizer service is deprecated: it is obsolete, it only remains available for backwards compatibility.',
      'Drupal\comment\Plugin\Action\PublishComment is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\PublishAction instead. See https://www.drupal.org/node/2919303.',
      'Drupal\comment\Plugin\Action\SaveComment is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\SaveAction instead. See https://www.drupal.org/node/2919303.',
      'Drupal\comment\Plugin\Action\UnpublishComment is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\UnpublishAction instead. See https://www.drupal.org/node/2919303.',
      'Drupal\node\Plugin\Action\PublishNode is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\PublishAction instead. See https://www.drupal.org/node/2919303.',
      'Drupal\node\Plugin\Action\SaveNode is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\SaveAction instead. See https://www.drupal.org/node/2919303.',
      'Drupal\node\Plugin\Action\UnpublishNode is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\Core\Action\Plugin\Action\UnpublishAction instead. See https://www.drupal.org/node/2919303.',
      "The 'rest.entity.entity_test.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test.GET' route instead.",
      "The 'rest.entity.entity_test.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test.GET' route instead.",
      "The 'rest.entity.entity_test.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test.GET' route instead.",
      "The 'rest.entity.action.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.action.GET' route instead.",
      "The 'rest.entity.action.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.action.GET' route instead.",
      "The 'rest.entity.action.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.action.GET' route instead.",
      "The 'rest.entity.base_field_override.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.base_field_override.GET' route instead.",
      "The 'rest.entity.base_field_override.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.base_field_override.GET' route instead.",
      "The 'rest.entity.base_field_override.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.base_field_override.GET' route instead.",
      "The 'rest.entity.block_content_type.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block_content_type.GET' route instead.",
      "The 'rest.entity.block_content_type.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block_content_type.GET' route instead.",
      "The 'rest.entity.block_content_type.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block_content_type.GET' route instead.",
      "The 'rest.entity.block_content.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block_content.GET' route instead.",
      "The 'rest.entity.block_content.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block_content.GET' route instead.",
      "The 'rest.entity.block_content.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block_content.GET' route instead.",
      "The 'rest.entity.block.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block.GET' route instead.",
      "The 'rest.entity.block.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block.GET' route instead.",
      "The 'rest.entity.block.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.block.GET' route instead.",
      "The 'rest.entity.comment_type.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.comment_type.GET' route instead.",
      "The 'rest.entity.comment_type.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.comment_type.GET' route instead.",
      "The 'rest.entity.comment_type.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.comment_type.GET' route instead.",
      "The 'rest.entity.comment.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.comment.GET' route instead.",
      "The 'rest.entity.comment.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.comment.GET' route instead.",
      "The 'rest.entity.comment.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.comment.GET' route instead.",
      "The 'rest.entity.config_test.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.config_test.GET' route instead.",
      "The 'rest.entity.config_test.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.config_test.GET' route instead.",
      "The 'rest.entity.config_test.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.config_test.GET' route instead.",
      "The 'rest.entity.configurable_language.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.configurable_language.GET' route instead.",
      "The 'rest.entity.configurable_language.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.configurable_language.GET' route instead.",
      "The 'rest.entity.configurable_language.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.configurable_language.GET' route instead.",
      "The 'rest.entity.contact_form.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.contact_form.GET' route instead.",
      "The 'rest.entity.contact_form.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.contact_form.GET' route instead.",
      "The 'rest.entity.contact_form.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.contact_form.GET' route instead.",
      "The 'rest.entity.language_content_settings.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.language_content_settings.GET' route instead.",
      "The 'rest.entity.language_content_settings.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.language_content_settings.GET' route instead.",
      "The 'rest.entity.language_content_settings.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.language_content_settings.GET' route instead.",
      "The 'rest.entity.date_format.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.date_format.GET' route instead.",
      "The 'rest.entity.date_format.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.date_format.GET' route instead.",
      "The 'rest.entity.date_format.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.date_format.GET' route instead.",
      "The 'rest.entity.editor.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.editor.GET' route instead.",
      "The 'rest.entity.editor.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.editor.GET' route instead.",
      "The 'rest.entity.editor.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.editor.GET' route instead.",
      "The 'rest.entity.entity_form_display.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_form_display.GET' route instead.",
      "The 'rest.entity.entity_form_display.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_form_display.GET' route instead.",
      "The 'rest.entity.entity_form_display.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_form_display.GET' route instead.",
      "The 'rest.entity.entity_form_mode.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_form_mode.GET' route instead.",
      "The 'rest.entity.entity_form_mode.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_form_mode.GET' route instead.",
      "The 'rest.entity.entity_form_mode.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_form_mode.GET' route instead.",
      "The 'rest.entity.entity_test_bundle.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test_bundle.GET' route instead.",
      "The 'rest.entity.entity_test_bundle.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test_bundle.GET' route instead.",
      "The 'rest.entity.entity_test_bundle.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test_bundle.GET' route instead.",
      "The 'rest.entity.entity_test_label.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test_label.GET' route instead.",
      "The 'rest.entity.entity_test_label.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test_label.GET' route instead.",
      "The 'rest.entity.entity_test_label.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_test_label.GET' route instead.",
      "The 'rest.entity.entity_view_display.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_view_display.GET' route instead.",
      "The 'rest.entity.entity_view_display.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_view_display.GET' route instead.",
      "The 'rest.entity.entity_view_display.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_view_display.GET' route instead.",
      "The 'rest.entity.entity_view_mode.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_view_mode.GET' route instead.",
      "The 'rest.entity.entity_view_mode.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_view_mode.GET' route instead.",
      "The 'rest.entity.entity_view_mode.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.entity_view_mode.GET' route instead.",
      "The 'rest.entity.aggregator_feed.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.aggregator_feed.GET' route instead.",
      "The 'rest.entity.aggregator_feed.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.aggregator_feed.GET' route instead.",
      "The 'rest.entity.aggregator_feed.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.aggregator_feed.GET' route instead.",
      "The 'rest.entity.field_config.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.field_config.GET' route instead.",
      "The 'rest.entity.field_config.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.field_config.GET' route instead.",
      "The 'rest.entity.field_config.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.field_config.GET' route instead.",
      "The 'rest.entity.field_storage_config.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.field_storage_config.GET' route instead.",
      "The 'rest.entity.field_storage_config.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.field_storage_config.GET' route instead.",
      "The 'rest.entity.field_storage_config.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.field_storage_config.GET' route instead.",
      "The 'rest.entity.file.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.file.GET' route instead.",
      "The 'rest.entity.file.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.file.GET' route instead.",
      "The 'rest.entity.file.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.file.GET' route instead.",
      "The 'rest.entity.filter_format.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.filter_format.GET' route instead.",
      "The 'rest.entity.filter_format.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.filter_format.GET' route instead.",
      "The 'rest.entity.filter_format.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.filter_format.GET' route instead.",
      "The 'rest.entity.image_style.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.image_style.GET' route instead.",
      "The 'rest.entity.image_style.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.image_style.GET' route instead.",
      "The 'rest.entity.image_style.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.image_style.GET' route instead.",
      "The 'rest.entity.aggregator_item.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.aggregator_item.GET' route instead.",
      "The 'rest.entity.aggregator_item.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.aggregator_item.GET' route instead.",
      "The 'rest.entity.aggregator_item.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.aggregator_item.GET' route instead.",
      "The 'rest.entity.media_type.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.media_type.GET' route instead.",
      "The 'rest.entity.media_type.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.media_type.GET' route instead.",
      "The 'rest.entity.media_type.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.media_type.GET' route instead.",
      "The 'rest.entity.media.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.media.GET' route instead.",
      "The 'rest.entity.media.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.media.GET' route instead.",
      "The 'rest.entity.media.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.media.GET' route instead.",
      "The 'rest.entity.menu_link_content.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.menu_link_content.GET' route instead.",
      "The 'rest.entity.menu_link_content.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.menu_link_content.GET' route instead.",
      "The 'rest.entity.menu_link_content.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.menu_link_content.GET' route instead.",
      "The 'rest.entity.menu.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.menu.GET' route instead.",
      "The 'rest.entity.menu.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.menu.GET' route instead.",
      "The 'rest.entity.menu.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.menu.GET' route instead.",
      "The 'rest.entity.node_type.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.node_type.GET' route instead.",
      "The 'rest.entity.node_type.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.node_type.GET' route instead.",
      "The 'rest.entity.node_type.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.node_type.GET' route instead.",
      "The 'rest.entity.node.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.node.GET' route instead.",
      "The 'rest.entity.node.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.node.GET' route instead.",
      "The 'rest.entity.node.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.node.GET' route instead.",
      "The 'rest.entity.rdf_mapping.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.rdf_mapping.GET' route instead.",
      "The 'rest.entity.rdf_mapping.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.rdf_mapping.GET' route instead.",
      "The 'rest.entity.rdf_mapping.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.rdf_mapping.GET' route instead.",
      "The 'rest.entity.responsive_image_style.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.responsive_image_style.GET' route instead.",
      "The 'rest.entity.responsive_image_style.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.responsive_image_style.GET' route instead.",
      "The 'rest.entity.responsive_image_style.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.responsive_image_style.GET' route instead.",
      "The 'rest.entity.rest_resource_config.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.rest_resource_config.GET' route instead.",
      "The 'rest.entity.rest_resource_config.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.rest_resource_config.GET' route instead.",
      "The 'rest.entity.rest_resource_config.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.rest_resource_config.GET' route instead.",
      "The 'rest.entity.user_role.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.user_role.GET' route instead.",
      "The 'rest.entity.user_role.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.user_role.GET' route instead.",
      "The 'rest.entity.user_role.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.user_role.GET' route instead.",
      "The 'rest.entity.search_page.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.search_page.GET' route instead.",
      "The 'rest.entity.search_page.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.search_page.GET' route instead.",
      "The 'rest.entity.search_page.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.search_page.GET' route instead.",
      "The 'rest.entity.shortcut_set.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.shortcut_set.GET' route instead.",
      "The 'rest.entity.shortcut_set.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.shortcut_set.GET' route instead.",
      "The 'rest.entity.shortcut_set.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.shortcut_set.GET' route instead.",
      "The 'rest.entity.shortcut.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.shortcut.GET' route instead.",
      "The 'rest.entity.shortcut.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.shortcut.GET' route instead.",
      "The 'rest.entity.shortcut.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.shortcut.GET' route instead.",
      "The 'rest.entity.taxonomy_term.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.taxonomy_term.GET' route instead.",
      "The 'rest.entity.taxonomy_term.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.taxonomy_term.GET' route instead.",
      "The 'rest.entity.taxonomy_term.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.taxonomy_term.GET' route instead.",
      "The 'rest.entity.tour.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.tour.GET' route instead.",
      "The 'rest.entity.tour.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.tour.GET' route instead.",
      "The 'rest.entity.tour.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.tour.GET' route instead.",
      "The 'rest.entity.user.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.user.GET' route instead.",
      "The 'rest.entity.user.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.user.GET' route instead.",
      "The 'rest.entity.user.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.user.GET' route instead.",
      "The 'rest.entity.view.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.view.GET' route instead.",
      "The 'rest.entity.view.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.view.GET' route instead.",
      "The 'rest.entity.view.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.view.GET' route instead.",
      "The 'rest.entity.taxonomy_vocabulary.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.taxonomy_vocabulary.GET' route instead.",
      "The 'rest.entity.taxonomy_vocabulary.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.taxonomy_vocabulary.GET' route instead.",
      "The 'rest.entity.taxonomy_vocabulary.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.taxonomy_vocabulary.GET' route instead.",
      "The 'rest.entity.workflow.GET.json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.workflow.GET' route instead.",
      "The 'rest.entity.workflow.GET.xml' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.workflow.GET' route instead.",
      "The 'rest.entity.workflow.GET.hal_json' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'rest.entity.workflow.GET' route instead.",
    ];
  }

}
