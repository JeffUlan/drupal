<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit6;

use Drupal\Tests\Listeners\DeprecationListenerTrait;
use Drupal\Tests\Listeners\DrupalComponentTestListenerTrait;
use Drupal\Tests\Listeners\DrupalStandardsListenerTrait;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;

/**
 * Listens to PHPUnit test runs.
 *
 * @internal
 */
class DrupalListener implements TestListener {

  use TestListenerDefaultImplementation;
  use DeprecationListenerTrait;
  use DrupalComponentTestListenerTrait;
  use DrupalStandardsListenerTrait;

  /**
   * {@inheritdoc}
   */
  public function startTest(Test $test) {
    $this->deprecationStartTest($test);
  }

  /**
   * {@inheritdoc}
   */
  public function endTest(Test $test, $time) {
    $this->deprecationEndTest($test, $time);
    $this->componentEndTest($test, $time);
    $this->standardsEndTest($test, $time);
  }

}
