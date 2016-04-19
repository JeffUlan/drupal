<?php

namespace Drupal\help_page_test;

/**
 * Provides controllers for testing the help block.
 */
class HelpPageTestController {

  /**
   * Provides a route with help.
   *
   * @return array
   *   A render array.
   */
  public function hasHelp() {
    return ['#markup' => 'A route with help.'];
  }

  /**
   * Provides a route with no help.
   *
   * @return array
   *   A render array.
   */
  public function noHelp() {
    return ['#markup' => 'A route without help.'];
  }

}
