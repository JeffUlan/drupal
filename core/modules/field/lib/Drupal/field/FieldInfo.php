<?php

/**
 * @file
 * Contains \Drupal\field\FieldInfo.
 */

namespace Drupal\field;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\Field\FieldTypePluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides field and instance definitions for the current runtime environment.
 *
 * The preferred way to access definitions is through the getBundleInstances()
 * method, which keeps cache entries per bundle, storing both fields and
 * instances for a given bundle. Fields used in multiple bundles are duplicated
 * in several cache entries, and are merged into a single list in the memory
 * cache. Cache entries are loaded for bundles as a whole, optimizing memory
 * and CPU usage for the most common pattern of iterating over all instances of
 * a bundle rather than accessing a single instance.
 *
 * The getFields() and getInstances() methods, which return all existing field
 * and instance definitions, are kept mainly for backwards compatibility, and
 * should be avoided when possible, since they load and persist in memory a
 * potentially large array of information. In many cases, the lightweight
 * getFieldMap() method should be preferred.
 */
class FieldInfo {

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Stores a module manager to invoke hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The field type manager to define field.
   *
   * @var \Drupal\Core\Entity\Field\FieldTypePluginManager
   */
  protected $fieldTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Lightweight map of fields across entity types and bundles.
   *
   * @var array
   */
  protected $fieldMap;

  /**
   * List of $field structures keyed by ID. Includes deleted fields.
   *
   * @var array
   */
  protected $fieldsById = array();

  /**
   * Mapping of field names to the ID of the corresponding non-deleted field.
   *
   * @var array
   */
  protected $fieldIdsByName = array();

  /**
   * Whether $fieldsById contains all field definitions or a subset.
   *
   * @var bool
   */
  protected $loadedAllFields = FALSE;

  /**
   * Separately tracks requested field names or IDs that do not exist.
   *
   * @var array
   */
  protected $unknownFields = array();

  /**
   * Instance definitions by bundle.
   *
   * @var array
   */
  protected $bundleInstances = array();

  /**
   * Whether $bundleInstances contains all instances definitions or a subset.
   *
   * @var bool
   */
  protected $loadedAllInstances = FALSE;

  /**
   * Separately tracks requested bundles that are empty (or do not exist).
   *
   * @var array
   */
  protected $emptyBundles = array();

  /**
   * Extra fields by bundle.
   *
   * @var array
   */
  protected $bundleExtraFields = array();

  /**
   * Constructs this FieldInfo object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The configuration factory object to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for invoking hooks.
   * @param \Drupal\Core\Entity\Field\FieldTypePluginManager $field_type_manager
   *   The 'field type' plugin manager.
   */
  public function __construct(CacheBackendInterface $cache_backend, ConfigFactory $config, ModuleHandlerInterface $module_handler, FieldTypePluginManager $field_type_manager) {
    $this->cacheBackend = $cache_backend;
    $this->moduleHandler = $module_handler;
    $this->config = $config;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * Clears the "static" and persistent caches.
   */
  public function flush() {
    $this->fieldMap = NULL;

    $this->fieldsById = array();
    $this->fieldIdsByName = array();
    $this->loadedAllFields = FALSE;
    $this->unknownFields = array();

    $this->bundleInstances = array();
    $this->loadedAllInstances = FALSE;
    $this->emptyBundles = array();

    $this->bundleExtraFields = array();

    $this->cacheBackend->deleteTags(array('field_info' => TRUE));
  }

  /**
   * Collects a lightweight map of fields across bundles.
   *
   * @return
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears.
   */
  public function getFieldMap() {
    // Read from the "static" cache.
    if ($this->fieldMap !== NULL) {
      return $this->fieldMap;
    }

    // Read from persistent cache.
    if ($cached = $this->cacheBackend->get('field_info:field_map')) {
      $map = $cached->data;

      // Save in "static" cache.
      $this->fieldMap = $map;

      return $map;
    }

    $map = array();

    // Get active fields.
    foreach (config_get_storage_names_with_prefix('field.field.') as $config_id) {
      $field_config = $this->config->get($config_id)->get();
      if ($field_config['active']) {
        $fields[$field_config['uuid']] = $field_config;
      }
    }
    // Get field instances.
    foreach (config_get_storage_names_with_prefix('field.instance.') as $config_id) {
      $instance_config = $this->config->get($config_id)->get();
      $field_uuid = $instance_config['field_uuid'];
      // Filter out instances of inactive fields, and instances on unknown
      // entity types.
      if (isset($fields[$field_uuid])) {
        $field = $fields[$field_uuid];
        $map[$instance_config['entity_type']][$field['name']]['bundles'][] = $instance_config['bundle'];
        $map[$instance_config['entity_type']][$field['name']]['type'] = $field['type'];
      }
    }

    // Save in "static" and persistent caches.
    $this->fieldMap = $map;
    $this->cacheBackend->set('field_info:field_map', $map, CacheBackendInterface::CACHE_PERMANENT, array('field_info' => TRUE));

    return $map;
  }

  /**
   * Returns all active fields, including deleted ones.
   *
   * @return
   *   An array of field definitions, keyed by field ID.
   */
  public function getFields() {
    // Read from the "static" cache.
    if ($this->loadedAllFields) {
      return $this->fieldsById;
    }

    // Read from persistent cache.
    if ($cached = $this->cacheBackend->get('field_info:fields')) {
      $this->fieldsById = $cached->data;
    }
    else {
      // Collect and prepare fields.
      foreach (field_read_fields(array(), array('include_deleted' => TRUE)) as $field) {
        $this->fieldsById[$field['uuid']] = $this->prepareField($field);
      }

      // Store in persistent cache.
      $this->cacheBackend->set('field_info:fields', $this->fieldsById, CacheBackendInterface::CACHE_PERMANENT, array('field_info' => TRUE));
    }

    // Fill the name/ID map.
    foreach ($this->fieldsById as $field) {
      if (!$field['deleted']) {
        $this->fieldIdsByName[$field->entity_type][$field->name] = $field['uuid'];
      }
    }

    $this->loadedAllFields = TRUE;

    return $this->fieldsById;
  }

  /**
   * Retrieves all active, non-deleted instances definitions.
   *
   * @param $entity_type
   *   (optional) The entity type.
   *
   * @return
   *   If $entity_type is not set, all instances keyed by entity type and bundle
   *   name. If $entity_type is set, all instances for that entity type, keyed
   *   by bundle name.
   */
  public function getInstances($entity_type = NULL) {
    // If the full list is not present in "static" cache yet.
    if (!$this->loadedAllInstances) {

      // Read from persistent cache.
      if ($cached = $this->cacheBackend->get('field_info:instances')) {
        $this->bundleInstances = $cached->data;
      }
      else {
        // Collect and prepare instances.

        // We also need to populate the static field cache, since it will not
        // be set by subsequent getBundleInstances() calls.
        $this->getFields();

        foreach (field_read_instances() as $instance) {
          $field = $this->getField($instance['entity_type'], $instance['field_name']);
          $instance = $this->prepareInstance($instance, $field['type']);
          $this->bundleInstances[$instance['entity_type']][$instance['bundle']][$instance['field_name']] = $instance;
        }

        // Store in persistent cache.
        $this->cacheBackend->set('field_info:instances', $this->bundleInstances, CacheBackendInterface::CACHE_PERMANENT, array('field_info' => TRUE));
      }

      $this->loadedAllInstances = TRUE;
    }

    if (isset($entity_type)) {
      return isset($this->bundleInstances[$entity_type]) ? $this->bundleInstances[$entity_type] : array();
    }
    else {
      return $this->bundleInstances;
    }
  }

  /**
   * Returns a field definition from a field name.
   *
   * This method only retrieves active, non-deleted fields.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   *
   * @return
   *   The field definition, or NULL if no field was found.
   */
  public function getField($entity_type, $field_name) {
    // Read from the "static" cache.
    if (isset($this->fieldIdsByName[$entity_type][$field_name])) {
      $field_id = $this->fieldIdsByName[$entity_type][$field_name];
      return $this->fieldsById[$field_id];
    }
    if (isset($this->unknownFields[$entity_type][$field_name])) {
      return;
    }

    // Do not check the (large) persistent cache, but read the definition.

    // Cache miss: read from definition.
    if ($field = entity_load('field_entity', $entity_type . '.' . $field_name)) {
      $field = $this->prepareField($field);

      // Save in the "static" cache.
      $this->fieldsById[$field['uuid']] = $field;
      $this->fieldIdsByName[$entity_type][$field_name] = $field['uuid'];

      return $field;
    }
    else {
      $this->unknownFields[$entity_type][$field_name] = TRUE;
    }
  }

  /**
   * Returns a field definition from a field ID.
   *
   * This method only retrieves active fields, deleted or not.
   *
   * @param $field_id
   *   The field ID.
   *
   * @return
   *   The field definition, or NULL if no field was found.
   */
  public function getFieldById($field_id) {
    // Read from the "static" cache.
    if (isset($this->fieldsById[$field_id])) {
      return $this->fieldsById[$field_id];
    }
    if (isset($this->unknownFields[$field_id])) {
      return;
    }

    // No persistent cache, fields are only persistently cached as part of a
    // bundle.

    // Cache miss: read from definition.
    if ($fields = field_read_fields(array('uuid' => $field_id), array('include_deleted' => TRUE))) {
      $field = current($fields);
      $field = $this->prepareField($field);

      // Store in the static cache.
      $this->fieldsById[$field['uuid']] = $field;
      if (!$field['deleted']) {
        $this->fieldIdsByName[$field->entity_type][$field->name] = $field['uuid'];
      }

      return $field;
    }
    else {
      $this->unknownFields[$field_id] = TRUE;
    }
  }

  /**
   * Retrieves the instances for a bundle.
   *
   * The function also populates the corresponding field definitions in the
   * "static" cache.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The bundle name.
   *
   * @return
   *   The array of instance definitions, keyed by field name.
   */
  public function getBundleInstances($entity_type, $bundle) {
    // Read from the "static" cache.
    if (isset($this->bundleInstances[$entity_type][$bundle])) {
      return $this->bundleInstances[$entity_type][$bundle];
    }
    if (isset($this->emptyBundles[$entity_type][$bundle])) {
      return array();
    }

    // Read from the persistent cache. We read fields first, since
    // unserializing the cached instance objects tries to access the field
    // definitions.
    if ($cached_fields = $this->cacheBackend->get("field_info:bundle:fields:$entity_type:$bundle")) {
      $fields = $cached_fields->data;

      // Extract the field definitions and save them in the "static" cache.
      foreach ($fields as $field) {
        if (!isset($this->fieldsById[$field['uuid']])) {
          $this->fieldsById[$field['uuid']] = $field;
          if (!$field['deleted']) {
            $this->fieldIdsByName[$field->entity_type][$field->name] = $field['uuid'];
          }
        }
      }

      // We can now unserialize the instances.
      $cached_instances = $this->cacheBackend->get("field_info:bundle:instances:$entity_type:$bundle");
      $instances = $cached_instances->data;

      // Store the instance definitions in the "static" cache'. Empty (or
      // non-existent) bundles are stored separately, so that they do not
      // pollute the global list returned by getInstances().
      if ($instances) {
        $this->bundleInstances[$entity_type][$bundle] = $instances;
      }
      else {
        $this->emptyBundles[$entity_type][$bundle] = TRUE;
      }
      return $instances;
    }

    // Cache miss: collect from the definitions.
    $field_map = $this->getFieldMap();
    $instances = array();
    $fields = array();

    // Do not return anything for unknown entity types.
    if (entity_get_info($entity_type) && !empty($field_map[$entity_type])) {

      // Collect names of fields and instances involved in the bundle, using the
      // field map. The field map is already filtered to active, non-deleted
      // fields and instances, so those are kept out of the persistent caches.
      $config_ids = array();
      foreach ($field_map[$entity_type] as $field_name => $field_data) {
        if (in_array($bundle, $field_data['bundles'])) {
          $config_ids["$entity_type.$field_name"] = "$entity_type.$bundle.$field_name";
        }
      }

      // Load and prepare the corresponding fields and instances entities.
      if ($config_ids) {
        // Place the fields in our global "static".
        $loaded_fields = entity_load_multiple('field_entity', array_keys($config_ids));
        foreach ($loaded_fields as $field) {
          if (!isset($this->fieldsById[$field['uuid']])) {
            $field = $this->prepareField($field);

            $this->fieldsById[$field['uuid']] = $field;
            $this->fieldIdsByName[$field->entity_type][$field->name] = $field['uuid'];
          }

          $fields[] = $this->fieldsById[$field['uuid']];
        }

        // Then collect the instances.
        $loaded_instances = entity_load_multiple('field_instance', array_values($config_ids));
        foreach ($loaded_instances as $instance) {
          $field = $instance->getField();

          $instance = $this->prepareInstance($instance, $field['type']);
          $instances[$field['field_name']] = $instance;
        }
      }
    }

    // Store in the 'static' cache'. Empty (or non-existent) bundles are stored
    // separately, so that they do not pollute the global list returned by
    // getInstances().
    if ($instances) {
      $this->bundleInstances[$entity_type][$bundle] = $instances;
    }
    else {
      $this->emptyBundles[$entity_type][$bundle] = TRUE;
    }

    // Store in the persistent cache. Fields and instances are cached in
    // separate entries because they need to be unserialized separately.
    $this->cacheBackend->set("field_info:bundle:fields:$entity_type:$bundle", $fields, CacheBackendInterface::CACHE_PERMANENT, array('field_info' => TRUE));
    $this->cacheBackend->set("field_info:bundle:instances:$entity_type:$bundle", $instances, CacheBackendInterface::CACHE_PERMANENT, array('field_info' => TRUE));

    return $instances;
  }

  /**
   * Returns an array of instance data for a specific field and bundle.
   *
   * @param string $entity_type
   *   The entity type for the instance.
   * @param string $bundle
   *   The bundle name for the instance.
   * @param string $field_name
   *   The field name for the instance.
   *
   * @return array
   *   An associative array of instance data for the specific field and bundle;
   *   NULL if the instance does not exist.
   */
  function getInstance($entity_type, $bundle, $field_name) {
    $info = $this->getBundleInstances($entity_type, $bundle);
    if (isset($info[$field_name])) {
      return $info[$field_name];
    }
  }

  /**
   * Retrieves the "extra fields" for a bundle.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The bundle name.
   *
   * @return
   *   The array of extra fields.
   */
  public function getBundleExtraFields($entity_type, $bundle) {
    // Read from the "static" cache.
    if (isset($this->bundleExtraFields[$entity_type][$bundle])) {
      return $this->bundleExtraFields[$entity_type][$bundle];
    }

    // Read from the persistent cache.
    if ($cached = $this->cacheBackend->get("field_info:bundle_extra:$entity_type:$bundle")) {
      $this->bundleExtraFields[$entity_type][$bundle] = $cached->data;
      return $this->bundleExtraFields[$entity_type][$bundle];
    }

    // Cache miss: read from hook_field_extra_fields(). Note: given the current
    // shape of the hook, we have no other way than collecting extra fields on
    // all bundles.
    $extra = $this->moduleHandler->invokeAll('field_extra_fields');
    drupal_alter('field_extra_fields', $extra);
    $info = isset($extra[$entity_type][$bundle]) ? $extra[$entity_type][$bundle] : array();
    $info += array('form' => array(), 'display' => array());

    // Store in the 'static' and persistent caches.
    $this->bundleExtraFields[$entity_type][$bundle] = $info;
    $this->cacheBackend->set("field_info:bundle_extra:$entity_type:$bundle", $info, CacheBackendInterface::CACHE_PERMANENT, array('field_info' => TRUE));

    return $this->bundleExtraFields[$entity_type][$bundle];
  }

  /**
   * Prepares a field definition for the current run-time context.
   *
   * @param $field
   *   The raw field structure as read from the database.
   *
   * @return
   *   The field definition completed for the current runtime context.
   */
  public function prepareField($field) {
    // Make sure all expected field settings are present.
    $field['settings'] += $this->fieldTypeManager->getDefaultSettings($field['type']);

    return $field;
  }

  /**
   * Prepares an instance definition for the current run-time context.
   *
   * @param $instance
   *   The raw instance structure as read from the database.
   * @param $field_type
   *   The field type.
   *
   * @return
   *   The field instance array completed for the current runtime context.
   */
  public function prepareInstance($instance, $field_type) {
    // Make sure all expected instance settings are present.
    $instance['settings'] += $this->fieldTypeManager->getDefaultInstanceSettings($field_type);

    return $instance;
  }

}
