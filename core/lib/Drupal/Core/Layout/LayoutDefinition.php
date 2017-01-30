<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\PluginDefinition;

/**
 * Provides an implementation of a layout definition and its metadata.
 *
 * @internal
 *   The layout system is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class LayoutDefinition extends PluginDefinition implements PluginDefinitionInterface, DerivablePluginDefinitionInterface {

  /**
   * The name of the deriver of this layout definition, if any.
   *
   * @var string|null
   */
  protected $deriver;

  /**
   * The dependencies of this layout definition.
   *
   * @todo Make protected after https://www.drupal.org/node/2821191.
   *
   * @var array
   */
  public $config_dependencies;

  /**
   * The human-readable name.
   *
   * @var string
   */
  protected $label;

  /**
   * An optional description for advanced layouts.
   *
   * @var string
   */
  protected $description;

  /**
   * The human-readable category.
   *
   * @var string
   */
  protected $category;

  /**
   * The template file to render this layout (relative to the 'path' given).
   *
   * @var string|null
   */
  protected $template;

  /**
   * The path to the template.
   *
   * @var string
   */
  protected $templatePath;

  /**
   * The theme hook used to render this layout.
   *
   * @var string|null
   */
  protected $theme_hook;

  /**
   * Path (relative to the module or theme) to resources like icon or template.
   *
   * @var string
   */
  protected $path;

  /**
   * The asset library.
   *
   * @var string|null
   */
  protected $library;

  /**
   * The path to the preview image.
   *
   * @var string
   */
  protected $icon;

  /**
   * An associative array of regions in this layout.
   *
   * The key of the array is the machine name of the region, and the value is
   * an associative array with the following keys:
   * - label: (string) The human-readable name of the region.
   *
   * Any remaining keys may have special meaning for the given layout plugin,
   * but are undefined here.
   *
   * @var array
   */
  protected $regions = [];

  /**
   * The default region.
   *
   * @var string
   */
  protected $default_region;

  /**
   * Any additional properties and values.
   *
   * @var array
   */
  protected $additional = [];

  /**
   * LayoutDefinition constructor.
   *
   * @param array $definition
   *   An array of values from the annotation.
   */
  public function __construct(array $definition) {
    foreach ($definition as $property => $value) {
      $this->set($property, $value);
    }
  }

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   The value for that property, or NULL if the property does not exist.
   */
  public function get($property) {
    if (property_exists($this, $property)) {
      $value = isset($this->{$property}) ? $this->{$property} : NULL;
    }
    else {
      $value = isset($this->additional[$property]) ? $this->additional[$property] : NULL;
    }
    return $value;
  }

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function set($property, $value) {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->additional[$property] = $value;
    }
    return $this;
  }

  /**
   * Gets the human-readable name of the layout definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The human-readable name of the layout definition.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Sets the human-readable name of the layout definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the layout definition.
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * Gets the description of the layout definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description of the layout definition.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets the description of the layout definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The description of the layout definition.
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * Gets the human-readable category of the layout definition.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The human-readable category of the layout definition.
   */
  public function getCategory() {
    return $this->category;
  }

  /**
   * Sets the human-readable category of the layout definition.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $category
   *   The human-readable category of the layout definition.
   *
   * @return $this
   */
  public function setCategory($category) {
    $this->category = $category;
    return $this;
  }

  /**
   * Gets the template name.
   *
   * @return string|null
   *   The template name, if it exists.
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * Sets the template name.
   *
   * @param string|null $template
   *   The template name.
   *
   * @return $this
   */
  public function setTemplate($template) {
    $this->template = $template;
    return $this;
  }

  /**
   * Gets the template path.
   *
   * @return string
   *   The template path.
   */
  public function getTemplatePath() {
    return $this->templatePath;
  }

  /**
   * Sets the template path.
   *
   * @param string $template_path
   *   The template path.
   *
   * @return $this
   */
  public function setTemplatePath($template_path) {
    $this->templatePath = $template_path;
    return $this;
  }

  /**
   * Gets the theme hook.
   *
   * @return string|null
   *   The theme hook, if it exists.
   */
  public function getThemeHook() {
    return $this->theme_hook;
  }

  /**
   * Sets the theme hook.
   *
   * @param string $theme_hook
   *   The theme hook.
   *
   * @return $this
   */
  public function setThemeHook($theme_hook) {
    $this->theme_hook = $theme_hook;
    return $this;
  }

  /**
   * Gets the base path for this layout definition.
   *
   * @return string
   *   The base path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Sets the base path for this layout definition.
   *
   * @param string $path
   *   The base path.
   *
   * @return $this
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * Gets the asset library for this layout definition.
   *
   * @return string|null
   *   The asset library, if it exists.
   */
  public function getLibrary() {
    return $this->library;
  }

  /**
   * Sets the asset library for this layout definition.
   *
   * @param string|null $library
   *   The asset library.
   *
   * @return $this
   */
  public function setLibrary($library) {
    $this->library = $library;
    return $this;
  }

  /**
   * Gets the icon path for this layout definition.
   *
   * @return string|null
   *   The icon path, if it exists.
   */
  public function getIconPath() {
    return $this->icon;
  }

  /**
   * Sets the icon path for this layout definition.
   *
   * @param string|null $icon
   *   The icon path.
   *
   * @return $this
   */
  public function setIconPath($icon) {
    $this->icon = $icon;
    return $this;
  }

  /**
   * Gets the regions for this layout definition.
   *
   * @return array[]
   *    The layout regions. The keys of the array are the machine names of the
   *    regions, and the values are an associative array with the following
   *    keys:
   *     - label: (string) The human-readable name of the region.
   *    Any remaining keys may have special meaning for the given layout plugin,
   *    but are undefined here.
   */
  public function getRegions() {
    return $this->regions;
  }

  /**
   * Sets the regions for this layout definition.
   *
   * @param array[] $regions
   *   An array of regions, see ::getRegions() for the format.
   *
   * @return $this
   */
  public function setRegions(array $regions) {
    $this->regions = $regions;
    return $this;
  }

  /**
   * Gets the machine-readable region names.
   *
   * @return string[]
   *   An array of machine-readable region names.
   */
  public function getRegionNames() {
    return array_keys($this->getRegions());
  }

  /**
   * Gets the human-readable region labels.
   *
   * @return string[]
   *   An array of human-readable region labels.
   */
  public function getRegionLabels() {
    $regions = $this->getRegions();
    return array_combine(array_keys($regions), array_column($regions, 'label'));
  }

  /**
   * Gets the default region.
   *
   * @return string
   *   The machine-readable name of the default region.
   */
  public function getDefaultRegion() {
    return $this->default_region;
  }

  /**
   * Sets the default region.
   *
   * @param string $default_region
   *   The machine-readable name of the default region.
   *
   * @return $this
   */
  public function setDefaultRegion($default_region) {
    $this->default_region = $default_region;
    return $this;
  }

  /**
   * Gets the config dependencies of this layout definition.
   *
   * @return array
   *   An array of config dependencies.
   *
   * @see \Drupal\Core\Plugin\PluginDependencyTrait::calculatePluginDependencies()
   */
  public function getConfigDependencies() {
    return $this->config_dependencies;
  }

  /**
   * Sets the config dependencies of this layout definition.
   *
   * @param array $config_dependencies
   *   An array of config dependencies.
   *
   * @return $this
   */
  public function setConfigDependencies(array $config_dependencies) {
    $this->config_dependencies = $config_dependencies;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeriver() {
    return $this->deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeriver($deriver) {
    $this->deriver = $deriver;
    return $this;
  }

}
