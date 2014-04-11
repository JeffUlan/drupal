<?php

/**
 * @file
 * Contains Drupal\Core\Http\Plugin\SimpletestHttpRequestSubscriber
 */

namespace Drupal\Core\Http\Plugin;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * Subscribe to HTTP requests and override the User-Agent header if the request
 * is being dispatched from inside a simpletest.
 */
class SimpletestHttpRequestSubscriber implements SubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function getEvents() {
    return array(
      'before'   => array('onBeforeSendRequest'),
    );
  }

  /**
   * Event callback for the 'before' event
   */
  public function onBeforeSendRequest(BeforeEvent $event) {
    // If the database prefix is being used by SimpleTest to run the tests in a copied
    // database then set the user-agent header to the database prefix so that any
    // calls to other Drupal pages will run the SimpleTest prefixed database. The
    // user-agent is used to ensure that multiple testing sessions running at the
    // same time won't interfere with each other as they would if the database
    // prefix were stored statically in a file or database variable.
    if ($test_prefix = drupal_valid_test_ua()) {
      $event->getRequest()->setHeader('User-Agent', drupal_generate_test_ua($test_prefix));
    }
  }
}
