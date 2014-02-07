<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditIntegrationTest.
 */

namespace Drupal\editor\Tests;

use Drupal\Core\Language\Language;
use Drupal\edit\EditorSelector;
use Drupal\edit\MetadataGenerator;
use Drupal\edit\Plugin\InPlaceEditorManager;
use Drupal\edit\Tests\EditTestBase;
use Drupal\edit_test\MockEditEntityFieldAccessCheck;
use Drupal\editor\EditorController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Edit module integration (Editor module's inline editing support).
 */
class EditIntegrationTest extends EditTestBase {

  /**
   * The manager for editor plug-ins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  /**
   * The metadata generator object to be tested.
   *
   * @var \Drupal\edit\MetadataGeneratorInterface.php
   */
  protected $metadataGenerator;

  /**
   * The editor selector object to be used by the metadata generator object.
   *
   * @var \Drupal\edit\EditorSelectorInterface
   */
  protected $editorSelector;

  /**
   * The access checker object to be used by the metadata generator object.
   *
   * @var \Drupal\edit\Access\EditEntityFieldAccessCheckInterface
   */
  protected $accessChecker;

  /**
   * The name of the field ued for tests.
   *
   * @var string
   */
  protected $field_name;

  public static function getInfo() {
    return array(
      'name' => 'In-place text editors (Edit module integration)',
      'description' => 'Tests Edit module integration (Editor module\'s inline editing support).',
      'group' => 'Text Editor',
    );
  }

  public function setUp() {
    parent::setUp();

    // Install the Filter module.
    $this->installSchema('system', 'url_alias');
    $this->enableModules(array('user', 'filter'));

    // Enable the Text Editor and Text Editor Test module.
    $this->enableModules(array('editor', 'editor_test'));

    // Create a field.
    $this->field_name = 'field_textarea';
    $this->createFieldWithInstance(
      $this->field_name, 'text', 1, 'Long text field',
      // Instance settings.
      array('text_processing' => 1),
      // Widget type & settings.
      'text_textarea',
      array('size' => 42),
      // 'default' formatter type & settings.
      'text_default',
      array()
    );

    // Create text format.
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
    ));
    $full_html_format->save();

    // Associate text editor with text format.
    $editor = entity_create('editor', array(
      'format' => $full_html_format->format,
      'editor' => 'unicorn',
    ));
    $editor->save();
  }

  /**
   * Returns the in-place editor that Edit selects.
   */
  protected function getSelectedEditor($entity_id, $field_name, $view_mode = 'default') {
    $entity = entity_load('entity_test', $entity_id, TRUE);
    $items = $entity->getTranslation(Language::LANGCODE_NOT_SPECIFIED)->get($field_name);
    $options = entity_get_display('entity_test', 'entity_test', $view_mode)->getComponent($field_name);
    return $this->editorSelector->getEditor($options['type'], $items);
  }

  /**
   * Tests editor selection when the Editor module is present.
   *
   * Tests a textual field, with text processing, with cardinality 1 and >1,
   * always with a ProcessedTextEditor plug-in present, but with varying text
   * format compatibility.
   */
  public function testEditorSelection() {
    $this->editorManager = new InPlaceEditorManager($this->container->get('container.namespaces'));
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));

    // Create an entity with values for this text field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->field_name}->value = 'Hello, world!';
    $this->entity->{$this->field_name}->format = 'filtered_html';
    $this->entity->save();

    // Editor selection w/ cardinality 1, text format w/o associated text editor.
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $this->field_name), "With cardinality 1, and the filtered_html text format, the 'form' editor is selected.");

    // Editor selection w/ cardinality 1, text format w/ associated text editor.
    $this->entity->{$this->field_name}->format = 'full_html';
    $this->entity->save();
    $this->assertEqual('editor', $this->getSelectedEditor($this->entity->id(), $this->field_name), "With cardinality 1, and the full_html text format, the 'editor' editor is selected.");

    // Editor selection with text processing, cardinality >1
    $this->field_textarea_field->cardinality = 2;
    $this->field_textarea_field->save();
    $this->assertEqual('form', $this->getSelectedEditor($this->entity->id(), $this->field_name), "With cardinality >1, and both items using the full_html text format, the 'form' editor is selected.");
  }

  /**
   * Tests (custom) metadata when the formatted text editor is used.
   */
  public function testMetadata() {
    $this->editorManager = new InPlaceEditorManager($this->container->get('container.namespaces'));
    $this->accessChecker = new MockEditEntityFieldAccessCheck();
    $this->editorSelector = new EditorSelector($this->editorManager, $this->container->get('plugin.manager.field.formatter'));
    $this->metadataGenerator = new MetadataGenerator($this->accessChecker, $this->editorSelector, $this->editorManager);

    // Create an entity with values for the field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->field_name}->value = 'Test';
    $this->entity->{$this->field_name}->format = 'full_html';
    $this->entity->save();
    $entity = entity_load('entity_test', $this->entity->id());

    // Verify metadata.
    $items = $entity->getTranslation(Language::LANGCODE_NOT_SPECIFIED)->get($this->field_name);
    $metadata = $this->metadataGenerator->generateFieldMetadata($items, 'default');
    $expected = array(
      'access' => TRUE,
      'label' => 'Long text field',
      'editor' => 'editor',
      'aria' => 'Entity entity_test 1, field Long text field',
      'custom' => array(
        'format' => 'full_html',
        'formatHasTransformations' => FALSE,
      ),
    );
    $this->assertEqual($expected, $metadata, 'The correct metadata (including custom metadata) is generated.');
  }

  /**
   * Tests GetUntransformedTextCommand AJAX command.
   */
  public function testGetUntransformedTextCommand() {
    // Create an entity with values for the field.
    $this->entity = entity_create('entity_test');
    $this->entity->{$this->field_name}->value = 'Test';
    $this->entity->{$this->field_name}->format = 'full_html';
    $this->entity->save();
    $entity = entity_load('entity_test', $this->entity->id());

    // Verify AJAX response.
    $controller = new EditorController();
    $request = new Request();
    $response = $controller->getUntransformedText($entity, $this->field_name, Language::LANGCODE_NOT_SPECIFIED, 'default');
    $expected = array(
      array(
        'command' => 'editorGetUntransformedText',
        'data' => 'Test',
      )
    );
    $this->assertEqual(drupal_json_encode($expected), $response->prepare($request)->getContent(), 'The GetUntransformedTextCommand AJAX command works correctly.');
  }

}
