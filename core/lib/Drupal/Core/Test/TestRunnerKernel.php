<?php

/**
 * @file
 * Contains \Drupal\Core\Test\TestRunnerKernel.
 */

namespace Drupal\Core\Test;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Installer\InstallerServiceProvider;
use Composer\Autoload\ClassLoader;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel for run-tests.sh.
 */
class TestRunnerKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  public static function createFromRequest(Request $request, ClassLoader $class_loader, $environment = 'test_runner', $allow_dumping = TRUE) {
    return parent::createFromRequest($request, $class_loader, $environment);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($environment, ClassLoader $class_loader) {
    parent::__construct($environment, $class_loader, FALSE);

    // Prime the module list and corresponding Extension objects.
    // @todo Remove System module. Needed because \Drupal\Core\Datetime\Date
    //   has a (needless) dependency on the 'date_format' entity, so calls to
    //   format_date()/format_interval() cause a plugin not found exception.
    $this->moduleList = array(
      'system' => 0,
      'simpletest' => 0,
    );
    $this->moduleData = array(
      'system' => new Extension('module', 'core/modules/system/system.info.yml', 'system.module'),
      'simpletest' => new Extension('module', 'core/modules/simpletest/simpletest.info.yml', 'simpletest.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function boot() {
    // Ensure that required Settings exist.
    if (!Settings::getAll()) {
      new Settings(array(
        'hash_salt' => 'run-tests',
      ));
    }

    // Remove Drupal's error/exception handlers; they are designed for HTML
    // and there is no storage nor a (watchdog) logger here.
    restore_error_handler();
    restore_exception_handler();

    // In addition, ensure that PHP errors are not hidden away in logs.
    ini_set('display_errors', TRUE);

    parent::boot();

    $this->getContainer()->get('module_handler')->loadAll();

    simpletest_classloader_register();
  }

  /**
   * {@inheritdoc}
   */
  public function discoverServiceProviders() {
    parent::discoverServiceProviders();
    // The test runner does not require an installed Drupal site to exist.
    // Therefore, its environment is identical to that of the early installer.
    $this->serviceProviderClasses['app']['Test'] = 'Drupal\Core\Installer\InstallerServiceProvider';
  }

}
