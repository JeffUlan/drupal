<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\ExceptionController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Page\HtmlPageRendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\Component\Utility\String;
use Symfony\Component\Debug\Exception\FlattenException;
use Drupal\Core\ContentNegotiation;
use Drupal\Core\Utility\Error;

/**
 * This controller handles HTTP errors generated by the routing system.
 */
class ExceptionController extends HtmlControllerBase implements ContainerAwareInterface {

  /**
   * The content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $negotiation;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The page rendering service.
   *
   * @var \Drupal\Core\Page\HtmlPageRendererInterface
   */
  protected $htmlPageRenderer;

  /**
   * The fragment rendering service.
   *
   * @var \Drupal\Core\Page\HtmlFragmentRendererInterface
   */
  protected $fragmentRenderer;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\ContentNegotiation $negotiation
   *   The content negotiation library to use to determine the correct response
   *   format.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Core\Page\HtmlPageRendererInterface $renderer
   *   The page renderer.
   * @param \Drupal\Core\Page\HtmlFragmentRendererInterface $fragment_renderer
   *   The fragment rendering service.
   */
  public function __construct(ContentNegotiation $negotiation, TranslationInterface $translation_manager, TitleResolverInterface $title_resolver, HtmlPageRendererInterface $renderer, $fragment_renderer) {
    parent::__construct($translation_manager, $title_resolver);
    $this->negotiation = $negotiation;
    $this->htmlPageRenderer = $renderer;
    $this->fragmentRenderer = $fragment_renderer;
  }

  /**
   * Sets the Container associated with this Controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A ContainerInterface instance.
   *
   * @api
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }

  /**
   * Handles an exception on a request.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request that generated the exception.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function execute(FlattenException $exception, Request $request) {
    $method = 'on' . $exception->getStatusCode() . $this->negotiation->getContentType($request);

    if (method_exists($this, $method)) {
      return $this->$method($exception, $request);
    }

    return new Response('A fatal error occurred: ' . $exception->getMessage(), $exception->getStatusCode(), $exception->getHeaders());
  }

  /**
   * Processes a MethodNotAllowed exception into an HTTP 405 response.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function on405Html(FlattenException $exception, Request $request) {
    return new Response('Method Not Allowed', 405);
  }

  /**
   * Processes an AccessDenied exception into an HTTP 403 response.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function on403Html(FlattenException $exception, Request $request) {
    $system_path = $request->attributes->get('_system_path');
    watchdog('access denied', $system_path, NULL, WATCHDOG_WARNING);

    $system_config = $this->container->get('config.factory')->get('system.site');
    $path = $this->container->get('path.alias_manager')->getSystemPath($system_config->get('page.403'));
    if ($path && $path != $system_path) {
      // Keep old path for reference, and to allow forms to redirect to it.
      if (!$request->query->has('destination')) {
        $request->query->set('destination', $system_path);
      }

      $subrequest = Request::create($request->getBaseUrl() . '/' . $path, 'get', array('destination' => $system_path, '_exception_statuscode' => 403), $request->cookies->all(), array(), $request->server->all());

      // The active trail is being statically cached from the parent request to
      // the subrequest, like any other static.  Unfortunately that means the
      // data in it is incorrect and does not get regenerated correctly for
      // the subrequest.  In this instance, that even causes a fatal error in
      // some circumstances because menu_get_active_trail() ends up having
      // a missing localized_options value.  To work around that, reset the
      // menu static variables and let them be regenerated as needed.
      // @todo It is likely that there are other such statics that need to be
      //   reset that are not triggering test failures right now.  If found,
      //   add them here.
      // @todo Refactor the breadcrumb system so that it does not rely on static
      //   variables in the first place, which will eliminate the need for this
      //   hack.
      drupal_static_reset('menu_set_active_trail');
      menu_reset_static_cache();

      $response = $this->container->get('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
      $response->setStatusCode(403, 'Access denied');
    }
    else {
      $page_content = array(
        '#markup' => t('You are not authorized to access this page.'),
        '#title' => t('Access denied'),
      );

      $fragment = $this->createHtmlFragment($page_content, $request);
      $page = $this->fragmentRenderer->render($fragment, 403);
      $response = new Response($this->htmlPageRenderer->render($page), $page->getStatusCode());
      return $response;
    }

    return $response;
  }

  /**
   * Processes a NotFound exception into an HTTP 404 response.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function on404Html(FlattenException $exception, Request $request) {
    watchdog('page not found', String::checkPlain($request->attributes->get('_system_path')), NULL, WATCHDOG_WARNING);

    // Check for and return a fast 404 page if configured.
    $config = \Drupal::config('system.performance');

    $exclude_paths = $config->get('fast_404.exclude_paths');
    if ($config->get('fast_404.enabled') && $exclude_paths && !preg_match($exclude_paths, $request->getPathInfo())) {
      $fast_paths = $config->get('fast_404.paths');
      if ($fast_paths && preg_match($fast_paths, $request->getPathInfo())) {
        $fast_404_html = $config->get('fast_404.html');
        $fast_404_html = strtr($fast_404_html, array('@path' => String::checkPlain($request->getUri())));
        return new Response($fast_404_html, 404);
      }
    }

    $system_path = $request->attributes->get('_system_path');

    // Keep old path for reference, and to allow forms to redirect to it.
    if (!$request->query->has('destination')) {
      $request->query->set('destination', $system_path);
    }

    $path = $this->container->get('path.alias_manager')->getSystemPath(\Drupal::config('system.site')->get('page.404'));
    if ($path && $path != $system_path) {
      // @todo Um, how do I specify an override URL again? Totally not clear. Do
      //   that and sub-call the kernel rather than using meah().
      // @todo The create() method expects a slash-prefixed path, but we store a
      //   normal system path in the site_404 variable.

      $subrequest = Request::create($request->getBaseUrl() . '/' . $path, 'get', array('destination' => $system_path, '_exception_statuscode' => 403), $request->cookies->all(), array(), $request->server->all());

      // The active trail is being statically cached from the parent request to
      // the subrequest, like any other static.  Unfortunately that means the
      // data in it is incorrect and does not get regenerated correctly for
      // the subrequest.  In this instance, that even causes a fatal error in
      // some circumstances because menu_get_active_trail() ends up having
      // a missing localized_options value.  To work around that, reset the
      // menu static variables and let them be regenerated as needed.
      // @todo It is likely that there are other such statics that need to be
      //   reset that are not triggering test failures right now.  If found,
      //   add them here.
      // @todo Refactor the breadcrumb system so that it does not rely on static
      //   variables in the first place, which will eliminate the need for this
      //   hack.
      drupal_static_reset('menu_set_active_trail');
      menu_reset_static_cache();

      $response = $this->container->get('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
      $response->setStatusCode(404, 'Not Found');
    }
    else {
      $page_content = array(
        '#markup' => t('The requested page "@path" could not be found.', array('@path' => $request->getPathInfo())),
        '#title' => t('Page not found'),
      );

      $fragment = $this->createHtmlFragment($page_content, $request);
      $page = $this->fragmentRenderer->render($fragment, 404);
      $response = new Response($this->htmlPageRenderer->render($page), $page->getStatusCode());
      return $response;
    }

    return $response;
  }

  /**
   * Processes a generic exception into an HTTP 500 response.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   Metadata about the exception that was thrown.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function on500Html(FlattenException $exception, Request $request) {
    $error = $this->decodeException($exception);

    // Because the kernel doesn't run until full bootstrap, we know that
    // most subsystems are already initialized.

    $headers = array();

    // When running inside the testing framework, we relay the errors
    // to the tested site by the way of HTTP headers.
    if (DRUPAL_TEST_IN_CHILD_SITE && !headers_sent() && (!defined('SIMPLETEST_COLLECT_ERRORS') || SIMPLETEST_COLLECT_ERRORS)) {
      // $number does not use drupal_static as it should not be reset
      // as it uniquely identifies each PHP error.
      static $number = 0;
      $assertion = array(
        $error['!message'],
        $error['%type'],
        array(
          'function' => $error['%function'],
          'file' => $error['%file'],
          'line' => $error['%line'],
        ),
      );
      $headers['X-Drupal-Assertion-' . $number] = rawurlencode(serialize($assertion));
      $number++;
    }

    watchdog('php', '%type: !message in %function (line %line of %file).', $error, $error['severity_level']);

    // Display the message if the current error reporting level allows this type
    // of message to be displayed, and unconditionnaly in update.php.
    if (error_displayable($error)) {
      $class = 'error';

      // If error type is 'User notice' then treat it as debug information
      // instead of an error message, see dd().
      if ($error['%type'] == 'User notice') {
        $error['%type'] = 'Debug';
        $class = 'status';
      }

      drupal_set_message(t('%type: !message in %function (line %line of %file).', $error), $class);
    }

    $page_content = array(
      '#theme' => 'maintenance_page',
      '#content' => t('The website has encountered an error. Please try again later.'),
      '#page' => array(
        '#title' => t('Error'),
      ),
    );

    $output = drupal_render($page_content);
    $response = new Response($output);
    $response->setStatusCode(500, '500 Service unavailable (with message)');

    return $response;
  }

  /**
   * Processes an AccessDenied exception that occurred on a JSON request.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response object.
   */
  public function on403Json(FlattenException $exception, Request $request) {
    $response = new JsonResponse();
    $response->setStatusCode(403, 'Access Denied');
    return $response;
  }

  /**
   * Processes a NotFound exception that occurred on a JSON request.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response object.
   */
  public function on404Json(FlattenException $exception, Request $request) {
    $response = new JsonResponse();
    $response->setStatusCode(404, 'Not Found');
    return $response;
  }

  /**
   * Processes a MethodNotAllowed exception that occurred on a JSON request.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *   The flattened exception.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that triggered this exception.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response object.
   */
  public function on405Json(FlattenException $exception, Request $request) {
    $response = new JsonResponse();
    $response->setStatusCode(405, 'Method Not Allowed');
    return $response;
  }


  /**
   * This method is a temporary port of _drupal_decode_exception().
   *
   * @todo This should get refactored. FlattenException could use some
   *   improvement as well.
   *
   * @param \Symfony\Component\Debug\Exception\FlattenException $exception
   *  The flattened exception.
   *
   * @return array
   *   An array of string-substitution tokens for formatting a message about the
   *   exception.
   */
  protected function decodeException(FlattenException $exception) {
    $message = $exception->getMessage();

    $backtrace = $exception->getTrace();

    // This value is missing from the stack for some reason in the
    // FlattenException version of the backtrace.
    $backtrace[0]['line'] = $exception->getLine();

    // For database errors, we try to return the initial caller,
    // skipping internal functions of the database layer.
    if (strpos($exception->getClass(), 'DatabaseExceptionWrapper') !== FALSE) {
      // A DatabaseExceptionWrapper exception is actually just a courier for
      // the original PDOException.  It's the stack trace from that exception
      // that we care about.
      $backtrace = $exception->getPrevious()->getTrace();
      $backtrace[0]['line'] = $exception->getLine();

      // The first element in the stack is the call, the second element gives us the caller.
      // We skip calls that occurred in one of the classes of the database layer
      // or in one of its global functions.
      $db_functions = array('db_query',  'db_query_range');
      while (!empty($backtrace[1]) && ($caller = $backtrace[1]) &&
          ((strpos($caller['namespace'], 'Drupal\Core\Database') !== FALSE || strpos($caller['class'], 'PDO') !== FALSE)) ||
          in_array($caller['function'], $db_functions)) {
        // We remove that call.
        array_shift($backtrace);
      }
    }

    $caller = Error::getLastCaller($backtrace);

    return array(
      '%type' => $exception->getClass(),
      // The standard PHP exception handler considers that the exception message
      // is plain-text. We mimick this behavior here.
      '!message' => String::checkPlain($message),
      '%function' => $caller['function'],
      '%file' => $caller['file'],
      '%line' => $caller['line'],
      'severity_level' => WATCHDOG_ERROR,
    );
  }

}
