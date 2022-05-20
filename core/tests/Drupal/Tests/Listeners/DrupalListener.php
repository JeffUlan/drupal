<?php

namespace Drupal\Tests\Listeners;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\Test as UtilTest;
use Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait;
use Symfony\Bridge\PhpUnit\SymfonyTestsListener;

/**
 * Listens to PHPUnit test runs.
 *
 * This listener orchestrates error handlers to ensure deprecations are skipped
 * when \Drupal\Tests\Listeners\DeprecationListenerTrait::isDeprecationSkipped()
 * returns TRUE. It removes test listeners to ensure that when
 * \Symfony\Bridge\PhpUnit\DeprecationErrorHandler::shutdown() is run the error
 * handler is in the expected state.
 *
 * @internal
 */
class DrupalListener implements TestListener {

  use TestListenerDefaultImplementation;
  use DeprecationListenerTrait;
  use DrupalComponentTestListenerTrait;
  use DrupalStandardsListenerTrait;

  /**
   * The wrapped Symfony test listener.
   *
   * @var \Symfony\Bridge\PhpUnit\SymfonyTestsListener
   */
  private $symfonyListener;

  /**
   * Constructs the DrupalListener object.
   */
  public function __construct() {
    $this->symfonyListener = new SymfonyTestsListener();
  }

  /**
   * {@inheritdoc}
   */
  public function startTestSuite(TestSuite $suite): void {
    $this->symfonyListener->startTestSuite($suite);
    $this->registerErrorHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function addSkippedTest(Test $test, \Throwable $t, float $time): void {
    $this->symfonyListener->addSkippedTest($test, $t, $time);
  }

  /**
   * {@inheritdoc}
   */
  public function startTest(Test $test): void {
    // The Drupal error handler has to be registered prior to the Symfony error
    // handler that is registered in
    // \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait::startTest()
    // that handles expected deprecations.
    $this->registerErrorHandler();
    $this->symfonyListener->startTest($test);
    // Check for incorrect visibility of the $modules property.
    $class = new \ReflectionClass($test);
    if ($class->hasProperty('modules') && !$class->getProperty('modules')->isProtected()) {
      @trigger_error('The ' . get_class($test) . '::$modules property must be declared protected. See https://www.drupal.org/node/2909426', E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function endTest(Test $test, float $time): void {
    if (!SymfonyTestsListenerTrait::$previousErrorHandler) {
      $className = get_class($test);
      $groups = UtilTest::getGroups($className, $test->getName(FALSE));
      if (in_array('legacy', $groups, TRUE)) {
        // If the Symfony listener is not registered for legacy tests then
        // deprecations triggered by the DebugClassloader in
        // \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait::endTest()
        // are not correctly identified as occurring in legacy tests.
        $symfony_error_handler = set_error_handler([SymfonyTestsListenerTrait::class, 'handleError']);
      }
    }
    $this->deprecationEndTest($test, $time);
    $this->symfonyListener->endTest($test, $time);
    $this->componentEndTest($test, $time);
    $this->standardsEndTest($test, $time);
    if (isset($symfony_error_handler)) {
      // If this test listener has added the Symfony error handler then it needs
      // to be removed.
      restore_error_handler();
    }
    // The Drupal error handler has to be removed after the Symfony error
    // handler is potentially removed in
    // \Symfony\Bridge\PhpUnit\Legacy\SymfonyTestsListenerTrait::endTest().
    $this->removeErrorHandler();
  }

}
