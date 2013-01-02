<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedDataManager.
 */

namespace Drupal\Core\TypedData;

use InvalidArgumentException;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\HookDiscovery;

/**
 * Manages data type plugins.
 */
class TypedDataManager extends PluginManagerBase {

  /**
   * An array of typed data property prototypes.
   *
   * @var array
   */
  protected $prototypes = array();

  public function __construct() {
    $this->discovery = new CacheDecorator(new HookDiscovery('data_type_info'), 'typed_data:types');
    $this->factory = new TypedDataFactory($this->discovery);
  }

  /**
   * Implements Drupal\Component\Plugin\PluginManagerInterface::createInstance().
   *
   * @param string $plugin_id
   *   The id of a plugin, i.e. the data type.
   * @param array $configuration
   *   The plugin configuration, i.e. the data definition.
   * @param string $name
   *   (optional) If a property or list item is to be created, the name of the
   *   property or the delta of the list item.
   * @param mixed $parent
   *   (optional) If a property or list item is to be created, the parent typed
   *   data object implementing either the ListInterface or the
   *   ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  public function createInstance($plugin_id, array $configuration, $name = NULL, $parent = NULL) {
    return $this->factory->createInstance($plugin_id, $configuration, $name, $parent);
  }

  /**
   * Creates a new typed data object wrapping the passed value.
   *
   * @param array $definition
   *   The data definition array with the following array keys and values:
   *   - type: The data type of the data to wrap. Required.
   *   - label: A human readable label.
   *   - description: A human readable description.
   *   - list: Whether the data is multi-valued, i.e. a list of data items.
   *     Defaults to FALSE.
   *   - computed: A boolean specifying whether the data value is computed by
   *     the object, e.g. depending on some other values.
   *   - read-only: A boolean specifying whether the data is read-only. Defaults
   *     to TRUE for computed properties, to FALSE otherwise.
   *   - class: If set and 'list' is FALSE, the class to use for creating the
   *     typed data object; otherwise the default class of the data type will be
   *     used.
   *   - list class: If set and 'list' is TRUE, the class to use for creating
   *     the typed data object; otherwise the default list class of the data
   *     type will be used.
   *   - settings: An array of settings, as required by the used 'class'. See
   *     the documentation of the class for supported or required settings.
   *   - list settings: An array of settings as required by the used
   *     'list class'. See the documentation of the list class for support or
   *     required settings.
   *   - constraints: An array of type specific value constraints, e.g. for data
   *     of type 'entity' the 'entity type' and 'bundle' may be specified. See
   *     the documentation of the data type 'class' for supported constraints.
   *   - required: A boolean specifying whether a non-NULL value is mandatory.
   *   Further keys may be supported in certain usages, e.g. for further keys
   *   supported for entity field definitions see
   *   Drupal\Core\Entity\StorageControllerInterface::getPropertyDefinitions().
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   * @param string $name
   *   (optional) If a property or list item is to be created, the name of the
   *   property or the delta of the list item.
   * @param mixed $parent
   *   (optional) If a property or list item is to be created, the parent typed
   *   data object implementing either the ListInterface or the
   *   ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *
   * @see typed_data()
   * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
   * @see \Drupal\Core\TypedData\Type\Integer
   * @see \Drupal\Core\TypedData\Type\Float
   * @see \Drupal\Core\TypedData\Type\String
   * @see \Drupal\Core\TypedData\Type\Boolean
   * @see \Drupal\Core\TypedData\Type\Duration
   * @see \Drupal\Core\TypedData\Type\Date
   * @see \Drupal\Core\TypedData\Type\Uri
   * @see \Drupal\Core\TypedData\Type\Binary
   * @see \Drupal\Core\Entity\Field\EntityWrapper
   */
  public function create(array $definition, $value = NULL, $name = NULL, $parent = NULL) {
    $wrapper = $this->factory->createInstance($definition['type'], $definition, $name, $parent);
    if (isset($value)) {
      $wrapper->setValue($value);
    }
    return $wrapper;
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginManagerInterface::getInstance().
   *
   * @param array $options
   *   An array of options with the following keys:
   *   - object: The parent typed data object, implementing the
   *     ContextAwareInterface and either the ListInterface or the
   *     ComplexDataInterface.
   *   - property: The name of the property to instantiate, or the delta of the
   *     the list item to instantiate.
   *   - value: The value to set. If set, it has to match one of the supported
   *     data type formats as documented by the data type classes.
   *
   * @throws \InvalidArgumentException
   *   If the given property is not known, or the passed object does not
   *   implement the ListInterface or the ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The new property instance.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
   */
  public function getInstance(array $options) {
    return $this->getPropertyInstance($options['object'], $options['property'], $options['value']);
  }

  /**
   * Get a typed data instance for a property of a given typed data object.
   *
   * This method will use prototyping for fast and efficient instantiation of
   * many property objects with the same property path; e.g.,
   * when multiple comments are used comment_body.0.value needs to be
   * instantiated very often.
   * Prototyping is done by the root object's data type and the given
   * property path, i.e. all property instances having the same property path
   * and inheriting from the same data type are prototyped.
   *
   * @param \Drupal\Core\TypedData\ContextAwareInterface $object
   *   The parent typed data object, implementing the ContextAwareInterface and
   *   either the ListInterface or the ComplexDataInterface.
   * @param string $property_name
   *   The name of the property to instantiate, or the delta of an list item.
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type formats as documented by the data type classes.
   *
   * @throws \InvalidArgumentException
   *   If the given property is not known, or the passed object does not
   *   implement the ListInterface or the ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The new property instance.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function getPropertyInstance(ContextAwareInterface $object, $property_name, $value = NULL) {
    $key = $object->getRoot()->getType() . ':' . $object->getPropertyPath() . '.';
    // If we are creating list items, we always use 0 in the key as all list
    // items look the same.
    $key .= is_numeric($property_name) ? 0 : $property_name;

    // Make sure we have a prototype. Then, clone the prototype and set object
    // specific values, i.e. the value and the context.
    if (!isset($this->prototypes[$key])) {
      if ($object instanceof ComplexDataInterface) {
        $definition = $object->getPropertyDefinition($property_name);
      }
      elseif ($object instanceof ListInterface) {
        $definition = $object->getItemDefinition();
      }
      else {
        throw new InvalidArgumentException("The passed object has to either implement the ComplexDataInterface or the ListInterface.");
      }
      // Make sure we have got a valid definition.
      if (!$definition) {
        throw new InvalidArgumentException('Property ' . check_plain($property_name) . ' is unknown.');
      }

      $this->prototypes[$key] = $this->create($definition, NULL, $property_name, $object);
    }

    $property = clone $this->prototypes[$key];
    // Update the parent relationship if necessary.
    if ($property instanceof ContextAwareInterface) {
      $property->setContext($property_name, $object);
    }
    // Set the passed data value.
    if (isset($value)) {
      $property->setValue($value);
    }
    return $property;
  }
}
