<?php

/**
 * @file
 * Contains \Drupal\Core\Access\AccessManager.
 */

namespace Drupal\Core\Access;

use Drupal\Core\ParamConverter\ParamConverterManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Attaches access check services to routes and runs them on request.
 *
 * @see \Drupal\Tests\Core\Access\AccessManagerTest
 */
class AccessManager implements ContainerAwareInterface, AccessManagerInterface {

  use ContainerAwareTrait;

  /**
   * Array of registered access check service ids.
   *
   * @var array
   */
  protected $checkIds = array();

  /**
   * Array of access check objects keyed by service id.
   *
   * @var array
   */
  protected $checks;

  /**
   * Array of access check method names keyed by service ID.
   *
   * @var array
   */
  protected $checkMethods = array();

  /**
   * An array to map static requirement keys to service IDs.
   *
   * @var array
   */
  protected $staticRequirementMap;

  /**
   * An array to map dynamic requirement keys to service IDs.
   *
   * @var array
   */
  protected $dynamicRequirementMap;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The url generator.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The paramconverter manager.
   *
   * @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface
   */
  protected $paramConverterManager;

  /**
   * The access arguments resolver.
   *
   * @var \Drupal\Core\Access\AccessArgumentsResolverInterface
   */
  protected $argumentsResolver;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a AccessManager instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\ParamConverter\ParamConverterManagerInterface $paramconverter_manager
   *   The param converter manager.
   * @param \Drupal\Core\Access\AccessArgumentsResolverInterface $arguments_resolver
   *   The access arguments resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   */
  public function __construct(RouteProviderInterface $route_provider, UrlGeneratorInterface $url_generator, ParamConverterManagerInterface $paramconverter_manager, AccessArgumentsResolverInterface $arguments_resolver, RequestStack $requestStack) {
    $this->routeProvider = $route_provider;
    $this->urlGenerator = $url_generator;
    $this->paramConverterManager = $paramconverter_manager;
    $this->argumentsResolver = $arguments_resolver;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function addCheckService($service_id, $service_method, array $applies_checks = array()) {
    $this->checkIds[] = $service_id;
    $this->checkMethods[$service_id] = $service_method;
    foreach ($applies_checks as $applies_check) {
      $this->staticRequirementMap[$applies_check][] = $service_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setChecks(RouteCollection $routes) {
    $this->loadDynamicRequirementMap();
    foreach ($routes as $route) {
      if ($checks = $this->applies($route)) {
        $route->setOption('_access_checks', $checks);
      }
    }
  }

  /**
   * Determine which registered access checks apply to a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to get list of access checks for.
   *
   * @return array
   *   An array of service ids for the access checks that apply to passed
   *   route.
   */
  protected function applies(Route $route) {
    $checks = array();

    // Iterate through map requirements from appliesTo() on access checkers.
    // Only iterate through all checkIds if this is not used.
    foreach ($route->getRequirements() as $key => $value) {
      if (isset($this->staticRequirementMap[$key])) {
        foreach ($this->staticRequirementMap[$key] as $service_id) {
          $checks[] = $service_id;
        }
      }
    }
    // Finally, see if any dynamic access checkers apply.
    foreach ($this->dynamicRequirementMap as $service_id) {
      if ($this->checks[$service_id]->applies($route)) {
        $checks[] = $service_id;
      }
    }

    return $checks;
  }

  /**
   * {@inheritdoc}
   */
  public function checkNamedRoute($route_name, array $parameters = array(), AccountInterface $account, Request $route_request = NULL) {
    try {
      $route = $this->routeProvider->getRouteByName($route_name, $parameters);
      if (empty($route_request)) {
        // Create a cloned request with fresh attributes.
        $route_request = RequestHelper::duplicate($this->requestStack->getCurrentRequest(), $this->urlGenerator->generate($route_name, $parameters));
        $route_request->attributes->replace(array());

        // Populate $route_request->attributes with both raw and converted
        // parameters.
        $parameters += $route->getDefaults();
        $route_request->attributes->set('_raw_variables', new ParameterBag($parameters));
        $parameters[RouteObjectInterface::ROUTE_OBJECT] = $route;
        $route_request->attributes->add($this->paramConverterManager->convert($parameters, $route_request));
      }
      return $this->check($route, $route_request, $account);
    }
    catch (RouteNotFoundException $e) {
      return FALSE;
    }
    catch (ParamNotConvertedException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function check(Route $route, Request $request, AccountInterface $account) {
    $checks = $route->getOption('_access_checks') ?: array();
    $conjunction = $route->getOption('_access_mode') ?: static::ACCESS_MODE_ALL;

    if ($conjunction == static::ACCESS_MODE_ALL) {
      return $this->checkAll($checks, $route, $request, $account);
    }
    else {
      return $this->checkAny($checks, $route, $request, $account);
    }
  }

  /**
   * Checks access so that every checker should allow access.
   *
   * @param array $checks
   *   Contains the list of checks on the route definition.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return bool
   *  Returns TRUE if the user has access to the route, else FALSE.
   */
  protected function checkAll(array $checks, Route $route, Request $request, AccountInterface $account) {
    $access = FALSE;

    foreach ($checks as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      $service_access = $this->performCheck($service_id, $route, $request, $account);

      if ($service_access === AccessInterface::ALLOW) {
        $access = TRUE;
      }
      else {
        // On both KILL and DENY stop.
        $access = FALSE;
        break;
      }
    }

    return $access;
  }

  /**
   * Checks access so that at least one checker should allow access.
   *
   * @param array $checks
   *   Contains the list of checks on the route definition.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return bool
   *  Returns TRUE if the user has access to the route, else FALSE.
   */
  protected function checkAny(array $checks, $route, $request, AccountInterface $account) {
    // No checks == deny by default.
    $access = FALSE;

    foreach ($checks as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      $service_access = $this->performCheck($service_id, $route, $request, $account);

      if ($service_access === AccessInterface::ALLOW) {
        $access = TRUE;
      }
      if ($service_access === AccessInterface::KILL) {
        return FALSE;
      }
    }

    return $access;
  }

  /**
   * Performs the specified access check.
   *
   * @param string $service_id
   *   The access check service ID to use.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check access to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown when the access check returns an invalid value.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  protected function performCheck($service_id, $route, $request, $account) {
    $callable = array($this->checks[$service_id], $this->checkMethods[$service_id]);
    $arguments = $this->argumentsResolver->getArguments($callable, $route, $request, $account);
    $service_access = call_user_func_array($callable, $arguments);

    if (!in_array($service_access, array(AccessInterface::ALLOW, AccessInterface::DENY, AccessInterface::KILL), TRUE)) {
      throw new AccessException("Access error in $service_id. Access services can only return AccessInterface::ALLOW, AccessInterface::DENY, or AccessInterface::KILL constants.");
    }

    return $service_access;
  }

  /**
   * Lazy-loads access check services.
   *
   * @param string $service_id
   *   The service id of the access check service to load.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the service hasn't been registered in addCheckService().
   * @throws \Drupal\Core\Access\AccessException
   *   Thrown when the service doesn't implement the required interface.
   */
  protected function loadCheck($service_id) {
    if (!in_array($service_id, $this->checkIds)) {
      throw new \InvalidArgumentException(sprintf('No check has been registered for %s', $service_id));
    }

    $check = $this->container->get($service_id);

    if (!($check instanceof AccessInterface)) {
      throw new AccessException('All access checks must implement AccessInterface.');
    }
    if (!is_callable(array($check, $this->checkMethods[$service_id]))) {
      throw new AccessException(sprintf('Access check method %s in service %s must be callable.', $this->checkMethods[$service_id], $service_id));
    }

    $this->checks[$service_id] = $check;
  }

  /**
   * Compiles a mapping of requirement keys to access checker service IDs.
   */
  protected function loadDynamicRequirementMap() {
    if (isset($this->dynamicRequirementMap)) {
      return;
    }

    // Set them here, so we can use the isset() check above.
    $this->dynamicRequirementMap = array();

    foreach ($this->checkIds as $service_id) {
      if (empty($this->checks[$service_id])) {
        $this->loadCheck($service_id);
      }

      // Add the service ID to an array that will be iterated over.
      if ($this->checks[$service_id] instanceof AccessCheckInterface) {
        $this->dynamicRequirementMap[] = $service_id;
      }
    }
  }

}
