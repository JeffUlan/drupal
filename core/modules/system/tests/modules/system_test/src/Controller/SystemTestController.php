<?php

/**
 * @file
 * Contains \Drupal\system_test\Controller\SystemTestController.
 */

namespace Drupal\system_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for system_test routes.
 */
class SystemTestController extends ControllerBase {

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The persistent lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $persistentLock;

  /**
   * Constructs the SystemTestController.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Lock\LockBackendInterface $persistent_lock
   *   The persistent lock service.
   */
  public function __construct(LockBackendInterface $lock, LockBackendInterface $persistent_lock) {
    $this->lock = $lock;
    $this->persistentLock = $persistent_lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('lock'), $container->get('lock.persistent'));
  }

  /**
   * Tests main content fallback.
   *
   * @return string
   *   The text to display.
   */
  public function mainContentFallback() {
    return ['#markup' => $this->t('Content to test main content fallback')];
  }

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
  public function drupalSetMessageTest() {
    // Set two messages.
    drupal_set_message('First message (removed).');
    drupal_set_message('Second message (not removed).');

    // Remove the first.
    unset($_SESSION['messages']['status'][0]);

    // Duplicate message check.
    drupal_set_message('Non Duplicated message', 'status', FALSE);
    drupal_set_message('Non Duplicated message', 'status', FALSE);

    drupal_set_message('Duplicated message', 'status', TRUE);
    drupal_set_message('Duplicated message', 'status', TRUE);
    return [];
  }

  /**
   * Controller to return $_GET['destination'] for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function getDestination(Request $request) {
    $response = new Response($request->query->get('destination'));
    return $response;
  }

  /**
   * Controller to return $_REQUEST['destination'] for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function requestDestination(Request $request) {
    $response = new Response($request->request->get('destination'));
    return $response;
  }

  /**
   * Try to acquire a named lock and report the outcome.
   */
  public function lockAcquire() {
    if ($this->lock->acquire('system_test_lock_acquire')) {
      $this->lock->release('system_test_lock_acquire');
      return ['#markup' => 'TRUE: Lock successfully acquired in \Drupal\system_test\Controller\SystemTestController::lockAcquire()'];
    }
    else {
      return ['#markup' => 'FALSE: Lock not acquired in \Drupal\system_test\Controller\SystemTestController::lockAcquire()'];
    }
  }

  /**
   * Try to acquire a specific lock, and then exit.
   */
  public function lockExit() {
    if ($this->lock->acquire('system_test_lock_exit', 900)) {
      echo 'TRUE: Lock successfully acquired in \Drupal\system_test\Controller\SystemTestController::lockExit()';
      // The shut-down function should release the lock.
      exit();
    }
    else {
      return ['#markup' => 'FALSE: Lock not acquired in system_test_lock_exit()'];
    }
  }

  /**
   * Creates a lock that will persist across requests.
   *
   * @param string $lock_name
   *   The name of the persistent lock to acquire.
   *
   * @return string
   *   The text to display.
   */
  public function lockPersist($lock_name) {
    if ($this->persistentLock->acquire($lock_name)) {
      return ['#markup' => 'TRUE: Lock successfully acquired in SystemTestController::lockPersist()'];
    }
    else {
      return ['#markup' => 'FALSE: Lock not acquired in SystemTestController::lockPersist()'];
    }
  }

  /**
   * Set cache tag on on the returned render array.
   */
  public function system_test_cache_tags_page() {
    $build['main'] = array(
      '#cache' => array('tags' => array('system_test_cache_tags_page')),
      '#pre_render' => array(
        '\Drupal\system_test\Controller\SystemTestController::preRenderCacheTags',
      ),
      'message' => array(
        '#markup' => 'Cache tags page example',
      ),
    );
    return $build;
  }

  /**
   * Sets a cache tag on an element to help test #pre_render and cache tags.
   */
  public static function preRenderCacheTags($elements) {
    $elements['#cache']['tags'][] = 'pre_render';
    return $elements;
  }

  /**
   * Initialize authorize.php during testing.
   *
   * @see system_authorized_init().
   */
  public function authorizeInit($page_title) {
    $authorize_url = Url::fromUri('base:core/authorize.php', array('absolute' => TRUE))->toString();
    system_authorized_init('system_test_authorize_run', drupal_get_path('module', 'system_test') . '/system_test.module', array(), $page_title);
    return new RedirectResponse($authorize_url);
  }

  /**
   * Sets a header.
   */
  public function setHeader(Request $request) {
    $query = $request->query->all();
    $response = new Response();
    $response->headers->set($query['name'], $query['value']);
    $response->setContent($this->t('The following header was set: %name: %value', array('%name' => $query['name'], '%value' => $query['value'])));

    return $response;
  }

  /**
   * A simple page callback which adds a register shutdown function.
   */
  public function shutdownFunctions($arg1, $arg2) {
    drupal_register_shutdown_function('_system_test_first_shutdown_function', $arg1, $arg2);
    // If using PHP-FPM then fastcgi_finish_request() will have been fired
    // preventing further output to the browser which means that the escaping of
    // the exception message can not be tested.
    // @see _drupal_shutdown_function()
    // @see \Drupal\system\Tests\System\ShutdownFunctionsTest
    if (function_exists('fastcgi_finish_request')) {
      return ['#markup' => 'The function fastcgi_finish_request exists when serving the request.'];
    }
    return [];
  }

  /**
   * Returns the title for system_test.info.yml's configure route.
   *
   * @param string $foo
   *   Any string for the {foo} slug.
   *
   * @return string
   */
  public function configureTitle($foo) {
    return 'Bar.' . $foo;
  }

}
