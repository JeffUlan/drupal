<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityType.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Exception\EntityTypeIdLengthException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides an implementation of an entity type and its metadata.
 *
 * @ingroup entity_api
 */
class EntityType implements EntityTypeInterface {

  use StringTranslationTrait;

  /**
   * Indicates whether entities should be statically cached.
   *
   * @var bool
   */
  protected $static_cache = TRUE;

  /**
   * Indicates whether the rendered output of entities should be cached.
   *
   * @var bool
   */
  protected $render_cache = TRUE;

  /**
   * Indicates if the persistent cache of field data should be used.
   *
   * @var bool
   */
  protected $persistent_cache = TRUE;

  /**
   * An array of entity keys.
   *
   * @var array
   */
  protected $entity_keys = array();

  /**
   * The unique identifier of this entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The name of the provider of this entity type.
   *
   * @var string
   */
  protected $provider;

  /**
   * The name of the entity type class.
   *
   * @var string
   */
  protected $class;

  /**
   * The name of the original entity type class.
   *
   * This is only set if the class name is changed.
   *
   * @var string
   */
  protected $originalClass;

  /**
   * An array of controllers.
   *
   * @var array
   */
  protected $controllers = array();

  /**
   * The name of the default administrative permission.
   *
   * @var string
   */
  protected $admin_permission;

  /**
   * The permission granularity level.
   *
   * The allowed values are respectively "entity_type" or "bundle".
   *
   * @var string
   */
  protected $permission_granularity = 'entity_type';

  /**
   * Indicates whether fields can be attached to entities of this type.
   *
   * @var bool
   */
  protected $fieldable = FALSE;

  /**
   * Link templates using the URI template syntax.
   *
   * @var array
   */
  protected $links = array();

  /**
   * The name of a callback that returns the label of the entity.
   *
   * @var string|null
   */
  protected $label_callback = NULL;

  /**
   * The name of the entity type which provides bundles.
   *
   * @var string
   */
  protected $bundle_entity_type = 'bundle';

  /**
   * The name of the entity type for which bundles are provided.
   *
   * @var string|null
   */
  protected $bundle_of = NULL;

  /**
   * The human-readable name of the entity bundles, e.g. Vocabulary.
   *
   * @var string|null
   */
  protected $bundle_label = NULL;

  /**
   * The name of the entity type's base table.
   *
   * @var string|null
   */
  protected $base_table = NULL;

  /**
   * The name of the entity type's revision data table.
   *
   * @var string|null
   */
  protected $revision_data_table = NULL;

  /**
   * The name of the entity type's revision table.
   *
   * @var string|null
   */
  protected $revision_table = NULL;

  /**
   * The name of the entity type's data table.
   *
   * @var string|null
   */
  protected $data_table = NULL;

  /**
   * Indicates whether entities of this type have multilingual support.
   *
   * @var bool
   */
  protected $translatable = FALSE;

  /**
   * The human-readable name of the type.
   *
   * @var string
   */
  protected $label = '';

  /**
   * A callable that can be used to provide the entity URI.
   *
   * @var callable|null
   */
  protected $uri_callback = NULL;

  /**
   * The machine name of the entity type group.
   */
  protected $group;

  /**
   * The human-readable name of the entity type group.
   */
  protected $group_label;

  /**
   * Constructs a new EntityType.
   *
   * @param array $definition
   *   An array of values from the annotation.
   *
   * @throws \Drupal\Core\Entity\Exception\EntityTypeIdLengthException
   *   Thrown when attempting to instantiate an entity type with too long ID.
   */
  public function __construct($definition) {
    // Throw an exception if the entity type ID is longer than 32 characters.
    if (Unicode::strlen($definition['id']) > static::ID_MAX_LENGTH) {
      throw new EntityTypeIdLengthException(String::format(
        'Attempt to create an entity type with an ID longer than @max characters: @id.', array(
          '@max' => static::ID_MAX_LENGTH,
          '@id' => $definition['id'],
        )
      ));
    }

    foreach ($definition as $property => $value) {
      $this->{$property} = $value;
    }

    // Ensure defaults.
    $this->entity_keys += array(
      'revision' => '',
      'bundle' => ''
    );
    $this->controllers += array(
      'access' => 'Drupal\Core\Entity\EntityAccessControlHandler',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get($property) {
    return isset($this->{$property}) ? $this->{$property} : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    $this->{$property} = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isStaticallyCacheable() {
    return $this->static_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderCacheable() {
    return $this->render_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function isPersistentlyCacheable() {
    return $this->persistent_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys() {
    return $this->entity_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey($key) {
    $keys = $this->getKeys();
    return isset($keys[$key]) ? $keys[$key] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasKey($key) {
    $keys = $this->getKeys();
    return !empty($keys[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalClass() {
    return $this->originalClass ?: $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    if (!$this->originalClass && $this->class) {
      // If the original class is currently not set, set it to the current
      // class, assume that is the original class name.
      $this->originalClass = $this->class;
    }
    $this->class = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubclassOf($class) {
    return is_subclass_of($this->getClass(), $class);
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerClasses() {
    return $this->controllers;
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerClass($controller_type, $nested = FALSE) {
    if ($this->hasControllerClass($controller_type, $nested)) {
      $controllers = $this->getControllerClasses();
      return $nested ? $controllers[$controller_type][$nested] : $controllers[$controller_type];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setControllerClass($controller_type, $value) {
    $this->controllers[$controller_type] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasControllerClass($controller_type, $nested = FALSE) {
    $controllers = $this->getControllerClasses();
    if (!isset($controllers[$controller_type]) || ($nested && !isset($controllers[$controller_type][$nested]))) {
      return FALSE;
    }
    $controller = $controllers[$controller_type];
    if ($nested) {
      $controller = $controller[$nested];
    }
    return class_exists($controller);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return $this->getControllerClass('storage');
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageClass($class) {
    $this->controllers['storage'] = $class;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation) {
    return $this->getControllerClass('form', $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function setFormClass($operation, $class) {
    $this->controllers['form'][$operation] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFormClasses() {
    return !empty($this->controllers['form']);
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilderClass() {
    return $this->getControllerClass('list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function setListBuilderClass($class) {
    $this->controllers['list_builder'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasListBuilderClass() {
    return $this->hasControllerClass('list_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilderClass() {
    return $this->getControllerClass('view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function setViewBuilderClass($class) {
    $this->controllers['view_builder'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasViewBuilderClass() {
    return $this->hasControllerClass('view_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlClass() {
    return $this->getControllerClass('access');
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessClass($class) {
    $this->controllers['access'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->admin_permission ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionGranularity() {
    return $this->permission_granularity;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldable() {
    return $this->fieldable;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTemplates() {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTemplate($key) {
    $links = $this->getLinkTemplates();
    return isset($links[$key]) ? $links[$key] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLinkTemplate($key) {
    $links = $this->getLinkTemplates();
    return isset($links[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkTemplate($key, $route_name) {
    $this->links[$key] = $route_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelCallback() {
    return $this->label_callback;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelCallback($callback) {
    $this->label_callback = $callback;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLabelCallback() {
    return isset($this->label_callback);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleEntityType() {
    return $this->bundle_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleOf() {
    return $this->bundle_of;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    return $this->bundle_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return $this->base_table;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return !empty($this->translatable);
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionable() {
    // Entity types are revisionable if a revision key has been specified.
    return $this->hasKey('revision');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDataTable() {
    return $this->revision_data_table;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionTable() {
    return $this->revision_table;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTable() {
    return $this->data_table;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getLowercaseLabel() {
    return Unicode::strtolower($this->getLabel());
  }

  /**
   * {@inheritdoc}
   */
  public function getUriCallback() {
    return $this->uri_callback;
  }

  /**
   * {@inheritdoc}
   */
  public function setUriCallback($callback) {
    $this->uri_callback = $callback;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }


  /**
   * {@inheritdoc}
   */
  public function getGroupLabel() {
    return !empty($this->group_label) ? $this->group_label : $this->t('Other', array(), array('context' => 'Entity type group'));
  }

}
