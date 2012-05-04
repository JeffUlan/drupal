<?php

/**
 * @file
 *
 * Definition of Drupal\Core\UrlMatcher.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher as SymfonyUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * UrlMatcher matches URL based on a set of routes.
 */
class UrlMatcher implements UrlMatcherInterface {

  /**
   * The request context for this matcher.
   *
   * @var RequestContext
   */
  protected $context;

  /**
   * The request object for this matcher.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public function __construct() {
    // We will not actually use this object, but it's needed to conform to
    // the interface.
    $this->context = new RequestContext();
  }

  /**
   * Sets the request context.
   *
   * This method is just to satisfy the interface, and is largely vestigial.
   * The request context object does not contain the information we need, so
   * we will use the original request object.
   *
   * @param RequestContext $context
   *   The context
   *
   * @api
   */
  public function setContext(RequestContext $context) {
    $this->context = $context;
  }

  /**
   * Gets the request context.
   *
   * This method is just to satisfy the interface, and is largely vestigial.
   * The request context object does not contain the information we need, so
   * we will use the original request object.
   *
   * @return RequestContext
   *   The context
   */
  public function getContext() {
    return $this->context;
  }

  public function setRequest(Request $request) {
    $this->request = $request;
  }

  public function getRequest() {
    return $this->request;
  }

  /**
   * {@inheritDoc}
   *
   * @api
   */
  public function match($pathinfo) {
    if ($router_item = $this->matchDrupalItem($pathinfo)) {
      $ret = $this->convertDrupalItem($router_item);
      // Stash the router item in the attributes while we're transitioning.
      $ret['drupal_menu_item'] = $router_item;

      // Most legacy controllers (aka page callbacks) are in a separate file,
      // so we have to include that.
      if ($router_item['include_file']) {
        require_once DRUPAL_ROOT . '/' . $router_item['include_file'];
      }

      return $ret;
    }

    // This matcher doesn't differentiate by method, so don't bother with those
    // exceptions.
    throw new ResourceNotFoundException();
  }

  /**
   * Get a drupal menu item.
   *
   * @todo Make this return multiple possible candidates for the resolver to
   * consider.
   *
   * @param string $path
   *   The path being looked up by
   */
  protected function matchDrupalItem($path) {
    // For now we can just proxy our procedural method. At some point this will
    // become more complicated because we'll need to get back candidates for a
    // path and them resolve them based on things like method and scheme which
    // we currently can't do.
    return menu_get_item($path);
  }

  protected function convertDrupalItem($router_item) {
    $route = array(
      '_controller' => $router_item['page_callback']
    );

    // Place argument defaults on the route.
    // @todo: For some reason drush test runs have a serialized page_arguments
    // but HTTP requests are unserialized. Hack to get around this for now.
    // This might be because page arguments aren't unserialized in
    // menu_get_item() when the access is denied.
    !is_array($router_item['page_arguments']) ? $page_arguments = unserialize($router_item['page_arguments']) : $page_arguments = $router_item['page_arguments'];
    foreach ($page_arguments as $k => $v) {
      $route[$k] = $v;
    }
    return $route;
  }
}
