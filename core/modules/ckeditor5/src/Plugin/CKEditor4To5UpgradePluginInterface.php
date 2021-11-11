<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\filter\FilterFormatInterface;

/**
 * Defines an interface for CKEditor 4 to 5 upgrade plugins.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginManager
 * @see \Drupal\ckeditor5\Annotation\CKEditor4To5Upgrade
 * @see plugin_api
 */
interface CKEditor4To5UpgradePluginInterface extends PluginInspectionInterface {

  /**
   * Maps a CKEditor 4 button to the CKEditor 5 equivalent, if it exists.
   *
   * Generated by inspecting all \Drupal\ckeditor\CKEditorPluginButtonsInterface
   * implementations.
   *
   * @param string $cke4_button
   *   A valid CKEditor 4 button name.
   *
   * @return string|null
   *   The equivalent CKEditor 5 toolbar item, or NULL if no equivalent exists.
   *   In either case, the button name must be added to the annotation.
   *
   * @throws \OutOfBoundsException
   *   Thrown when this plugin does not know whether an equivalent exists.
   *
   * @see \Drupal\ckeditor\CKEditorPluginButtonsInterface
   * @see \Drupal\ckeditor5\Annotation\CKEditor4To5Upgrade
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button): ?string;

  /**
   * Maps CKEditor 4 settings to the CKEditor 5 equivalent, if needed.
   *
   * Not every CKEditor 5 plugin has settings; some CKEditor 5 plugins may have
   * settings that the CKEditor 4 equivalent did not and vice versa. Therefore
   * the complete CKEditor 4 settings are provided, and any CKEditor 5 setting
   * can be set.
   *
   * @param string $cke4_plugin_id
   *   The CKEditor 4 plugin whose settings need to be mapped.
   * @param array $cke4_plugin_settings
   *   The settings for this CKEditor 4 plugin.
   *
   * @return array|null
   *   NULL if not needed, otherwise an array with a single key-value pair:
   *   - key: the plugin ID of the equivalent CKEditor 5 plugin
   *   - value: the equivalent settings
   *   In either case, the button name must be added to the annotation.
   *
   * @throws \OutOfBoundsException
   *   Thrown when this plugin does not know whether an equivalent exists.
   *
   * @see \Drupal\ckeditor\CKEditorPluginConfigurableInterface
   * @see \Drupal\ckeditor5\Annotation\CKEditor4To5Upgrade
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array;

  /**
   * Computes elements subset configuration for CKEditor 5 plugin.
   *
   * Every CKEditor 5 plugin that implements the elements subset interface must
   * implement this as well, to ensure a smooth upgrade path.
   *
   * @param string $cke5_plugin_id
   *   The CKEditor 5 plugin whose subset configuration needs to be computed.
   * @param \Drupal\filter\FilterFormatInterface $text_format
   *   The text format based on whose restrictions this should be computed.
   *
   * @return array|null
   *   NULL if not needed, otherwise a configuration array (which can itself be
   *   a subset of the default configuration of this CKEditor 5 plugin: perhaps
   *   only some of the configuration values determine the subset).
   *
   * @throws \OutOfBoundsException
   *   Thrown when no upgrade path exists.
   * @throws \LogicException
   *   Thrown when a plugin claims to provide an upgrade path but does not.
   *
   * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface
   * @see \Drupal\ckeditor5\Annotation\CKEditor4To5Upgrade
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array;

}
