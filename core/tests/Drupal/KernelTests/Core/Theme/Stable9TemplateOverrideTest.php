<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Theme\Registry;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Stable 9's template overrides.
 *
 * @group Theme
 */
class Stable9TemplateOverrideTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user'];

  /**
   * An array of template names to skip, without the extension.
   *
   * @var string[]
   */
  protected $templatesToSkip = [
    // Registered as a template in the views_theme() function in views.module
    // but an actual template does not exist.
    'views-form-views-form',
  ];

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * A list of all core modules.
   *
   * @var string[]
   */
  protected $allModules;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->themeHandler = $this->container->get('theme_handler');

    $this->container->get('theme_installer')->install(['stable9']);

    $this->installAllModules();
  }

  /**
   * Installs all core modules.
   */
  protected function installAllModules() {
    // Enable all core modules.
    $all_modules = $this->container->get('extension.list.module')->getList();
    $all_modules = array_filter($all_modules, function ($module) {
      // Filter contrib, hidden, experimental, already enabled modules, and
      // modules in the Testing package.
      if ($module->origin !== 'core' || !empty($module->info['hidden']) || $module->status == TRUE || $module->info['package'] == 'Testing' || $module->info['package'] == 'Core (Experimental)') {
        return FALSE;
      }
      return TRUE;
    });
    $this->allModules = array_keys($all_modules);
    sort($this->allModules);

    $module_installer = $this->container->get('module_installer');
    $module_installer->install($this->allModules);

    $this->installConfig(['system', 'user']);
  }

  /**
   * Ensures that Stable 9 overrides all relevant core templates.
   */
  public function testStable9TemplateOverrides() {
    $registry = new Registry($this->root, \Drupal::cache(), \Drupal::lock(), \Drupal::moduleHandler(), $this->themeHandler, \Drupal::service('theme.initialization'), 'stable9');
    $registry->setThemeManager(\Drupal::theme());

    $registry_full = $registry->get();

    foreach ($registry_full as $hook => $info) {
      if (isset($info['template'])) {
        // Allow skipping templates.
        if (in_array($info['template'], $this->templatesToSkip)) {
          continue;
        }

        $this->assertEquals('core/themes/stable9', $info['theme path'], $info['template'] . '.html.twig overridden in Stable 9.');
      }
    }
  }

}
